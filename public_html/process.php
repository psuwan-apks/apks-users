<?php
// Display all error for PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    // Session is not active, start it
    session_start();
}

const DS = DIRECTORY_SEPARATOR;
$dir_root = realpath(dirname(__FILE__, 2));
$dir_app = $dir_root . DS . 'app';
defined('APPLICATION_PATH') || define('APPLICATION_PATH', $dir_app);
require_once APPLICATION_PATH . DS . 'config' . DS . 'config.php';

global $config;
require_once $config['PATH_TO_LIB'] . 'functions.php';

$cmd2Process = get("CMD2PROCESS");

switch ($cmd2Process):

        // Default CMD2PROCESS
    default:
        echo json_encode(['status' => 'error', 'message' => 'No command to process']);
        break;

    case "REQUEST_SAVE":
        header('Content-Type: application/json');

        // Extract posted fields from equipment request form
        $reqUuid = trim($_POST['request_refkey'] ?? '');

        $custName  = trim($_POST['customer_name'] ?? '');
        $custAddr  = trim($_POST['customer_address'] ?? '');
        $custPhone = trim($_POST['customer_phone'] ?? '');
        $custFax   = trim($_POST['customer_fax'] ?? '');
        $custEmail = trim($_POST['customer_email'] ?? '');

        $methodType   = strtoupper(trim($_POST['request_method_type'] ?? 'OTHER'));
        $methodDetail = trim($_POST['request_method_detail'] ?? '');
        $labNo        = trim($_POST['request_lab_no'] ?? '');
        $labUuidStr   = trim($_POST['request_lab_refkey'] ?? '');
        $objType      = strtoupper(trim($_POST['request_objective_type'] ?? 'OTHER'));
        $objDetail    = trim($_POST['request_objective_detail'] ?? '');
        $langCode     = strtoupper(trim($_POST['request_report_language'] ?? 'TH'));
        $translation  = (int)($_POST['request_translation_needed'] ?? 0);
        $delivery     = strtoupper(trim($_POST['request_report_delivery'] ?? 'POSTAL'));
        // Manual created_at (optional)
        $createdAtRaw = trim($_POST['request_created_at'] ?? '');
        $createdAt = null;
        if ($createdAtRaw !== '') {
            $normalized = str_replace('T', ' ', $createdAtRaw);
            $ts = strtotime($normalized);
            if ($ts !== false) { $createdAt = date('Y-m-d H:i:s', $ts); }
        }

        $eqName        = trim($_POST['equipment_name'] ?? '');
        $eqQty         = (int)($_POST['equipment_quantity'] ?? 1);
        $eqBrand       = trim($_POST['equipment_brand'] ?? '');
        $eqModel       = trim($_POST['equipment_model'] ?? '');
        $eqSerial      = trim($_POST['equipment_serial_no'] ?? '');
        $eqAccessories = trim($_POST['equipment_accessories'] ?? '');
        $eqParams      = trim($_POST['equipment_parameters'] ?? '');

        // Report details (optional, for bilingual output on reports)
        $reportThName    = trim($_POST['report_name_thai'] ?? '');
        $reportThAddress = trim($_POST['report_address_thai'] ?? '');
        $reportEnName    = trim($_POST['report_name_eng'] ?? '');
        $reportEnAddress = trim($_POST['report_address_eng'] ?? '');

        // Minimal validation
        if ($custName === '') {
            $resp = ['success' => false, 'message' => 'Customer name is required'];
            log_event('REQUEST_SAVE', 'failure', 'Missing customer_name', null, ['payload' => $_POST, 'response' => $resp]);
            echo json_encode($resp);
            break;
        }

        if ($eqName === '') {
            $resp = ['success' => false, 'message' => 'Equipment name is required'];
            log_event('REQUEST_SAVE', 'failure', 'Missing equipment_name', null, ['payload' => $_POST, 'response' => $resp]);
            echo json_encode($resp);
            break;
        }

        try {
            $pdo = db_connected();
            // Detect if request_lab_refkey column exists to avoid SQL errors on legacy DBs
            $hasLabRefColumn = false;
            try {
                $stmtCol = $pdo->prepare(
                    "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS\n                     WHERE TABLE_SCHEMA = DATABASE()\n                       AND TABLE_NAME = 'tbl4dss_calibration_requests'\n                       AND COLUMN_NAME = 'request_lab_refkey'"
                );
                $stmtCol->execute();
                $hasLabRefColumn = ((int)$stmtCol->fetchColumn()) > 0;
            } catch (Throwable $e) {
                $hasLabRefColumn = false;
            }
            $pdo->beginTransaction();

            // Resolve or create customer_refkey (optional if no name/email provided)
            $customerRef = null;
            if ($custEmail !== '' || $custName !== '') {
                if ($custEmail !== '') {
                    $stmt = $pdo->prepare("SELECT customer_refkey FROM tbl4dss_customers WHERE customer_email = :email LIMIT 1");
                    $stmt->execute([':email' => $custEmail]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($row) {
                        $customerRef = $row['customer_refkey'];
                    }
                }
                if (!$customerRef && $custName !== '') {
                    $stmt = $pdo->prepare("SELECT customer_refkey FROM tbl4dss_customers WHERE customer_name = :name LIMIT 1");
                    $stmt->execute([':name' => $custName]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($row) {
                        $customerRef = $row['customer_refkey'];
                    }
                }

                if (!$customerRef) {
                    // Insert new customer
                    $newCustRef = generateUuidV4Binary(true);
                    $stmt = $pdo->prepare("INSERT INTO tbl4dss_customers (
                                                customer_refkey, customer_name, customer_address, customer_phone, customer_fax, customer_email, customer_is_active, customer_created_at
                                            ) VALUES (
                                                :ref, :name, :addr, :phone, :fax, :email, 1, NOW()
                                            )");
                    $stmt->execute([
                        ':ref' => $newCustRef,
                        ':name' => $custName,
                        ':addr' => $custAddr,
                        ':phone' => $custPhone,
                        ':fax' => $custFax,
                        ':email' => $custEmail
                    ]);
                    $customerRef = $newCustRef;
                }
            }

            // Insert or update request
            $retUuid = '';
            if ($reqUuid !== '' || (isset($_POST['request_hex']) && is_string($_POST['request_hex']) && ctype_xdigit($_POST['request_hex']) && strlen($_POST['request_hex']) === 32)) {
                // Update existing request
                // If hex is provided, use it directly for precise matching
                $updated = false;
                $hex = $_POST['request_hex'] ?? '';
                if (is_string($hex) && ctype_xdigit($hex) && strlen($hex) === 32) {
                    $reqBin = hex2bin($hex);
                    $sqlUpd = "UPDATE tbl4dss_calibration_requests\n                                 SET request_customer_refkey = :cust,\n                                     request_method_type = :mtype,\n                                     request_method_detail = :mdetail,\n                                     request_lab_no = :labno";
                    if ($hasLabRefColumn) { $sqlUpd .= ", request_lab_refkey = :labref"; }
                    $sqlUpd .= ", request_objective_type = :otype,\n                                     request_objective_detail = :odetail,\n                                     request_report_language = :lang,\n                                     request_translation_needed = :trans,\n                                     request_report_delivery = :delivery\n                               WHERE request_refkey = :ref";
                    $stmt = $pdo->prepare($sqlUpd);
                    $params = [
                        ':cust' => $customerRef,
                        ':mtype' => $methodType,
                        ':mdetail' => $methodDetail,
                        ':labno' => ($labNo !== '' ? $labNo : null),
                        ':otype' => $objType,
                        ':odetail' => $objDetail,
                        ':lang' => $langCode,
                        ':trans' => $translation,
                        ':delivery' => $delivery,
                        ':ref' => $reqBin
                    ];
                    if ($hasLabRefColumn) { $params[':labref'] = ($labUuidStr !== '' ? uuidToBinary($labUuidStr, true) : null); }
                    $stmt->execute($params);
                    if ($stmt->rowCount() > 0) {
                        $stmtDel = $pdo->prepare("DELETE FROM tbl4dss_equipments WHERE equipment_request_refkey = :ref");
                        $stmtDel->execute([':ref' => $reqBin]);

                        $stmtEq = $pdo->prepare("INSERT INTO tbl4dss_equipments (
                                                    equipment_refkey, equipment_request_refkey, equipment_name, equipment_quantity,
                                                    equipment_brand, equipment_model, equipment_serial_no,
                                                    equipment_accessories, equipment_parameters, equipment_created_at
                                                 ) VALUES (
                                                    :eqref, :ref, :name, :qty, :brand, :model, :serial, :acc, :params, NOW()
                                                 )");
                        $stmtEq->execute([
                            ':eqref' => generateUuidV4Binary(true),
                            ':ref' => $reqBin,
                            ':name' => $eqName,
                            ':qty' => max(1, $eqQty),
                            ':brand' => $eqBrand,
                            ':model' => $eqModel,
                            ':serial' => $eqSerial,
                            ':acc' => $eqAccessories,
                            ':params' => $eqParams
                        ]);
                        $updated = true;

                        // Upsert calibration report details for this request
                        $stmtRepSel = $pdo->prepare("SELECT report_refkey FROM tbl4dss_calibration_reports WHERE report_request_refkey = :ref LIMIT 1");
                        $stmtRepSel->execute([':ref' => $reqBin]);
                        $existingRep = $stmtRepSel->fetch(PDO::FETCH_ASSOC);
                        if ($existingRep) {
                            $stmtRepUpd = $pdo->prepare("UPDATE tbl4dss_calibration_reports
                                                            SET report_th_name = :th_name,
                                                                report_th_address = :th_addr,
                                                                report_en_name = :en_name,
                                                                report_en_address = :en_addr,
                                                                report_updated_at = NOW()
                                                          WHERE report_request_refkey = :ref");
                            $stmtRepUpd->execute([
                                ':th_name' => ($reportThName !== '' ? $reportThName : null),
                                ':th_addr' => ($reportThAddress !== '' ? $reportThAddress : null),
                                ':en_name' => ($reportEnName !== '' ? $reportEnName : null),
                                ':en_addr' => ($reportEnAddress !== '' ? $reportEnAddress : null),
                                ':ref' => $reqBin
                            ]);
                        } else {
                            $stmtRepIns = $pdo->prepare("INSERT INTO tbl4dss_calibration_reports (
                                                                report_refkey, report_request_refkey,
                                                                report_th_name, report_th_address,
                                                                report_en_name, report_en_address,
                                                                report_created_at
                                                            ) VALUES (
                                                                :refkey, :req,
                                                                :th_name, :th_addr,
                                                                :en_name, :en_addr,
                                                                NOW()
                                                            )");
                            $stmtRepIns->execute([
                                ':refkey' => generateUuidV4Binary(true),
                                ':req' => $reqBin,
                                ':th_name' => ($reportThName !== '' ? $reportThName : null),
                                ':th_addr' => ($reportThAddress !== '' ? $reportThAddress : null),
                                ':en_name' => ($reportEnName !== '' ? $reportEnName : null),
                                ':en_addr' => ($reportEnAddress !== '' ? $reportEnAddress : null)
                            ]);
                        }
                    }
                }
                // Otherwise try swapped then raw based on UUID string
                if (!$updated && $reqUuid !== '') {
                    foreach ([true, false] as $swap) {
                        $reqBin = uuidToBinary($reqUuid, $swap);
                        $sqlUpd = "UPDATE tbl4dss_calibration_requests\n                                     SET request_customer_refkey = :cust,\n                                         request_method_type = :mtype,\n                                         request_method_detail = :mdetail,\n                                         request_lab_no = :labno";
                        if ($hasLabRefColumn) { $sqlUpd .= ", request_lab_refkey = :labref"; }
                        $sqlUpd .= ", request_objective_type = :otype,\n                                         request_objective_detail = :odetail,\n                                         request_report_language = :lang,\n                                         request_translation_needed = :trans,\n                                         request_report_delivery = :delivery\n                                   WHERE request_refkey = :ref";
                        $stmt = $pdo->prepare($sqlUpd);
                        $params = [
                            ':cust' => $customerRef,
                            ':mtype' => $methodType,
                            ':mdetail' => $methodDetail,
                            ':labno' => ($labNo !== '' ? $labNo : null),
                            ':otype' => $objType,
                            ':odetail' => $objDetail,
                            ':lang' => $langCode,
                            ':trans' => $translation,
                            ':delivery' => $delivery,
                            ':ref' => $reqBin
                        ];
                        if ($hasLabRefColumn) { $params[':labref'] = ($labUuidStr !== '' ? uuidToBinary($labUuidStr, true) : null); }
                        $stmt->execute($params);
                        if ($stmt->rowCount() > 0) {
                            $stmtDel = $pdo->prepare("DELETE FROM tbl4dss_equipments WHERE equipment_request_refkey = :ref");
                            $stmtDel->execute([':ref' => $reqBin]);

                            $stmtEq = $pdo->prepare("INSERT INTO tbl4dss_equipments (
                                                        equipment_refkey, equipment_request_refkey, equipment_name, equipment_quantity,
                                                        equipment_brand, equipment_model, equipment_serial_no,
                                                        equipment_accessories, equipment_parameters, equipment_created_at
                                                     ) VALUES (
                                                        :eqref, :ref, :name, :qty, :brand, :model, :serial, :acc, :params, NOW()
                                                     )");
                            $stmtEq->execute([
                                ':eqref' => generateUuidV4Binary(true),
                                ':ref' => $reqBin,
                                ':name' => $eqName,
                                ':qty' => max(1, $eqQty),
                                ':brand' => $eqBrand,
                                ':model' => $eqModel,
                                ':serial' => $eqSerial,
                                ':acc' => $eqAccessories,
                                ':params' => $eqParams
                            ]);
                            $updated = true;
                            break;
                        }
                    }
                }
                if (!$updated) {
                    throw new RuntimeException('Request not found');
                }
                // Prefer echoing back the UUID string if provided; otherwise derive from hex
                $retUuid = $reqUuid !== '' ? $reqUuid : binaryToUuid(hex2bin($hex), true);
            } else {
                // Insert new request
                $reqRef = generateUuidV4Binary(true);
                $cols = "request_refkey, request_customer_refkey,\n                         request_method_type, request_method_detail, request_lab_no";
                $vals = ":ref, :cust, :mtype, :mdetail, :labno";
                if ($hasLabRefColumn) { $cols .= ", request_lab_refkey"; $vals .= ", :labref"; }
                $cols .= ", request_objective_type, request_objective_detail,\n                          request_report_language, request_translation_needed, request_report_delivery,\n                          request_created_at";
                $vals .= ", :otype, :odetail, :lang, :trans, :delivery, :created_at";
                $stmt = $pdo->prepare("INSERT INTO tbl4dss_calibration_requests ($cols) VALUES ($vals)");
                $params = [
                    ':ref' => $reqRef,
                    ':cust' => $customerRef,
                    ':mtype' => $methodType,
                    ':mdetail' => $methodDetail,
                    ':labno' => ($labNo !== '' ? $labNo : null),
                    ':otype' => $objType,
                    ':odetail' => $objDetail,
                    ':lang' => $langCode,
                    ':trans' => $translation,
                    ':delivery' => $delivery,
                    ':created_at' => ($createdAt ?: date('Y-m-d H:i:s'))
                ];
                if ($hasLabRefColumn) { $params[':labref'] = ($labUuidStr !== '' ? uuidToBinary($labUuidStr, true) : null); }
                $stmt->execute($params);

                $stmtEq = $pdo->prepare("INSERT INTO tbl4dss_equipments (
                                            equipment_refkey, equipment_request_refkey, equipment_name, equipment_quantity,
                                            equipment_brand, equipment_model, equipment_serial_no,
                                            equipment_accessories, equipment_parameters, equipment_created_at
                                         ) VALUES (
                                            :eqref, :ref, :name, :qty, :brand, :model, :serial, :acc, :params, NOW()
                                         )");
                $stmtEq->execute([
                    ':eqref' => generateUuidV4Binary(true),
                    ':ref' => $reqRef,
                    ':name' => $eqName,
                    ':qty' => max(1, $eqQty),
                    ':brand' => $eqBrand,
                    ':model' => $eqModel,
                    ':serial' => $eqSerial,
                    ':acc' => $eqAccessories,
                    ':params' => $eqParams
                ]);

                $retUuid = binaryToUuid($reqRef, true);

                // Insert calibration report details
                $stmtRepIns = $pdo->prepare("INSERT INTO tbl4dss_calibration_reports (
                                                report_refkey, report_request_refkey,
                                                report_th_name, report_th_address,
                                                report_en_name, report_en_address,
                                                report_created_at
                                            ) VALUES (
                                                :refkey, :req,
                                                :th_name, :th_addr,
                                                :en_name, :en_addr,
                                                NOW()
                                            )");
                $stmtRepIns->execute([
                    ':refkey' => generateUuidV4Binary(true),
                    ':req' => $reqRef,
                    ':th_name' => ($reportThName !== '' ? $reportThName : null),
                    ':th_addr' => ($reportThAddress !== '' ? $reportThAddress : null),
                    ':en_name' => ($reportEnName !== '' ? $reportEnName : null),
                    ':en_addr' => ($reportEnAddress !== '' ? $reportEnAddress : null)
                ]);
            }

            $pdo->commit();

            $resp = ['success' => true, 'message' => 'Request saved', 'request_refkey' => $retUuid];
            log_event('REQUEST_SAVE', 'success', 'Request saved', null, ['response' => $resp]);
            echo json_encode($resp);
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $resp = ['success' => false, 'message' => $e->getMessage()];
            log_event('REQUEST_SAVE', 'failure', $e->getMessage(), null, ['payload' => $_POST, 'response' => $resp]);
            echo json_encode($resp);
        }
        break;

    case "REQUEST_FILE_UPLOAD":
        header('Content-Type: application/json');
        try {
            if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
                echo json_encode(['success' => false, 'message' => 'No file uploaded']);
                break;
            }
            $file = $_FILES['file'];
            if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => 'Upload error: ' . (int)$file['error']]);
                break;
            }
            $reqHex = trim($_POST['request_hex'] ?? '');
            $reqUuid = trim($_POST['request_refkey'] ?? '');
            $fileCategory = strtolower(trim($_POST['file_category'] ?? ''));
            $allowedCategories = ['request', 'work'];
            $categorySub = in_array($fileCategory, $allowedCategories, true) ? $fileCategory : '';
            $sub = '';
            $reqBin = null;
            if ($reqHex !== '' && ctype_xdigit($reqHex) && strlen($reqHex) === 32) {
                $sub = strtolower($reqHex);
                $reqBin = hex2bin($reqHex);
            } else if ($reqUuid !== '') {
                $sub = preg_replace('/[^A-Za-z0-9]/', '', $reqUuid);
                foreach ([true, false] as $swap) {
                    $b = uuidToBinary($reqUuid, $swap);
                    if ($b) { $reqBin = $b; break; }
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Missing request identifier']);
                break;
            }
            $base = $dir_root . DS . 'public' . DS . 'uploads' . DS . 'requests';
            $targetDir = $base . DS . $sub . ($categorySub ? (DS . $categorySub) : '');
            if (!is_dir($targetDir)) {
                if (!is_dir($base)) { @mkdir($base, 0777, true); }
                // Ensure subdir exists first
                $subDir = $base . DS . $sub;
                if (!is_dir($subDir)) { @mkdir($subDir, 0777, true); }
                @mkdir($targetDir, 0777, true);
            }
            if (!is_dir($targetDir)) {
                echo json_encode(['success' => false, 'message' => 'Failed to prepare upload directory']);
                break;
            }
            // Determine reference string for naming (prefer lab number)
            $refForName = $sub;
            try {
                if ($reqBin) {
                    $pdo = db_connected();
                    $stmt = $pdo->prepare("SELECT request_lab_no FROM tbl4dss_calibration_requests WHERE request_refkey = :ref LIMIT 1");
                    $stmt->execute([':ref' => $reqBin]);
                    $labno = $stmt->fetchColumn();
                    if ($labno) { $refForName = preg_replace('/[^A-Za-z0-9_-]+/', '_', $labno); }
                }
            } catch (Throwable $e2) { /* ignore */ }

            // Count existing files to compute next index
            $existing = [];
            foreach (scandir($targetDir) as $f) {
                if ($f === '.' || $f === '..') continue;
                $full = $targetDir . DS . $f;
                if (is_file($full)) { $existing[] = $f; }
            }
            $nextIndex = count($existing) + 1;

            $orig = basename($file['name'] ?? 'file');
            $safe = preg_replace('/[^A-Za-z0-9._-]+/', '_', $orig);
            $ext = pathinfo($safe, PATHINFO_EXTENSION);
            $stored = $refForName . '_' . $nextIndex . ($ext ? ('.' . $ext) : '');
            $dest = $targetDir . DS . $stored;
            if (!move_uploaded_file($file['tmp_name'], $dest)) {
                echo json_encode(['success' => false, 'message' => 'Failed to save file']);
                break;
            }
            // Recount total after save
            $total = 0;
            foreach (scandir($targetDir) as $f2) {
                if ($f2 === '.' || $f2 === '..') continue;
                $full2 = $targetDir . DS . $f2;
                if (is_file($full2)) { $total++; }
            }
            $url = '/uploads/requests/' . $sub . '/' . ($categorySub ? ($categorySub . '/') : '') . $stored;
            echo json_encode(['success' => true, 'file_url' => $url, 'file_name' => $stored, 'file_index' => $nextIndex, 'file_total' => $total, 'file_category' => ($categorySub ?: null)]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case "REQUEST_DELETE":
        header('Content-Type: application/json');
        try {
            $reqHex = trim($_POST['request_hex'] ?? '');
            $reqUuid = trim($_POST['request_refkey'] ?? '');
            $reqBin = null;
            if ($reqHex !== '' && ctype_xdigit($reqHex) && strlen($reqHex) === 32) {
                $reqBin = hex2bin($reqHex);
            } else if ($reqUuid !== '') {
                // Try both UUID byte orders
                foreach ([true, false] as $swap) {
                    $b = uuidToBinary($reqUuid, $swap);
                    if ($b) { $reqBin = $b; break; }
                }
            }
            if (!$reqBin) {
                echo json_encode(['success' => false, 'message' => 'Missing request identifier']);
                break;
            }

            $pdo = db_connected();
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("DELETE FROM tbl4dss_calibration_requests WHERE request_refkey = :ref");
            $stmt->execute([':ref' => $reqBin]);

            $deleted = ($stmt->rowCount() > 0);
            $pdo->commit();

            // Remove uploaded files directory if present
            $sub = '';
            if ($reqHex !== '' && ctype_xdigit($reqHex) && strlen($reqHex) === 32) {
                $sub = strtolower($reqHex);
            } else if ($reqUuid !== '') {
                $sub = preg_replace('/[^A-Za-z0-9]/', '', $reqUuid);
            }
            if ($sub !== '') {
                $base = $dir_root . DS . 'public' . DS . 'uploads' . DS . 'requests';
                $targetDir = $base . DS . $sub;
                if (is_dir($targetDir)) {
                    // Remove nested category dirs first
                    foreach (['request','work'] as $cat) {
                        $catDir = $targetDir . DS . $cat;
                        if (is_dir($catDir)) {
                            $files = glob($catDir . DS . '*');
                            foreach ($files as $f) { if (is_file($f)) { @unlink($f); } }
                            @rmdir($catDir);
                        }
                    }
                    // Remove any files in root of request dir
                    $files = glob($targetDir . DS . '*');
                    foreach ($files as $f) { if (is_file($f)) { @unlink($f); } }
                    @rmdir($targetDir);
                }
            }

            echo json_encode(['success' => $deleted]);
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case "REQUEST_FILE_DELETE":
        header('Content-Type: application/json');
        try {
            $reqHex = trim($_POST['request_hex'] ?? '');
            $reqUuid = trim($_POST['request_refkey'] ?? '');
            $fileName = trim($_POST['file_name'] ?? '');
            $fileCategory = strtolower(trim($_POST['file_category'] ?? ''));
            $allowedCategories = ['request', 'work'];
            $categorySub = in_array($fileCategory, $allowedCategories, true) ? $fileCategory : '';
            if ($fileName === '' || !preg_match('/^[A-Za-z0-9._-]+$/', $fileName)) {
                echo json_encode(['success' => false, 'message' => 'Invalid file name']);
                break;
            }
            $sub = '';
            if ($reqHex !== '' && ctype_xdigit($reqHex) && strlen($reqHex) === 32) {
                $sub = strtolower($reqHex);
            } else if ($reqUuid !== '') {
                $sub = preg_replace('/[^A-Za-z0-9]/', '', $reqUuid);
            } else {
                echo json_encode(['success' => false, 'message' => 'Missing request identifier']);
                break;
            }
            $base = $dir_root . DS . 'public' . DS . 'uploads' . DS . 'requests';
            $targetDir = $base . DS . $sub . ($categorySub ? (DS . $categorySub) : '');
            $full = $targetDir . DS . $fileName;
            if (!is_file($full)) {
                echo json_encode(['success' => false, 'message' => 'File not found']);
                break;
            }
            $ok = @unlink($full);
            // Recount remaining files
            $total = 0;
            if (is_dir($targetDir)) {
                foreach (scandir($targetDir) as $f2) {
                    if ($f2 === '.' || $f2 === '..') continue;
                    $full2 = $targetDir . DS . $f2;
                    if (is_file($full2)) { $total++; }
                }
            }
            echo json_encode(['success' => (bool)$ok, 'file_total' => $total]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case "REQUEST_FILE_LIST":
        header('Content-Type: application/json');
        try {
            $reqHex = trim($_POST['request_hex'] ?? '');
            $reqUuid = trim($_POST['request_refkey'] ?? '');
            $fileCategory = strtolower(trim($_POST['file_category'] ?? ''));
            $allowedCategories = ['request', 'work'];
            $categorySub = in_array($fileCategory, $allowedCategories, true) ? $fileCategory : '';
            $sub = '';
            if ($reqHex !== '' && ctype_xdigit($reqHex) && strlen($reqHex) === 32) {
                $sub = strtolower($reqHex);
            } else if ($reqUuid !== '') {
                $sub = preg_replace('/[^A-Za-z0-9]/', '', $reqUuid);
            } else {
                echo json_encode(['success' => false, 'message' => 'Missing request identifier']);
                break;
            }
            $base = $dir_root . DS . 'public' . DS . 'uploads' . DS . 'requests';
            $targetDir = $base . DS . $sub . ($categorySub ? (DS . $categorySub) : '');
            $files = [];
            if (is_dir($targetDir)) {
                foreach (scandir($targetDir) as $f) {
                    if ($f === '.' || $f === '..') continue;
                    $full = $targetDir . DS . $f;
                    if (is_file($full)) {
                        $files[] = [
                            'name' => $f,
                            'url'  => '/uploads/requests/' . $sub . '/' . ($categorySub ? ($categorySub . '/') : '') . $f
                        ];
                    }
                }
            }
            // Sort by natural order using name
            usort($files, function($a, $b) { return strnatcasecmp($a['name'], $b['name']); });
            echo json_encode(['success' => true, 'files' => $files]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case "REQUEST_ASSIGN":
        header('Content-Type: application/json');

        // Extract identifiers
        $reqUuid = trim($_POST['request_refkey'] ?? '');
        $reqHex  = $_POST['request_hex'] ?? '';
        $userUuid = trim($_POST['assignee_uuid'] ?? '');
        $userHex  = $_POST['assignee_hex'] ?? '';

        if (($reqUuid === '' && (!is_string($reqHex) || !ctype_xdigit($reqHex) || strlen($reqHex) !== 32)) ||
            ($userUuid === '' && (!is_string($userHex) || !ctype_xdigit($userHex) || strlen($userHex) !== 32))
        ) {
            echo json_encode(['success' => false, 'message' => 'Missing request or assignee identifier']);
            break;
        }

        try {
            $pdo = db_connected();
            $pdo->beginTransaction();

            // Resolve request binary
            $reqBin = null;
            if (is_string($reqHex) && ctype_xdigit($reqHex) && strlen($reqHex) === 32) {
                $reqBin = hex2bin($reqHex);
            } else if ($reqUuid !== '') {
                foreach ([true, false] as $swap) {
                    $b = uuidToBinary($reqUuid, $swap);
                    $stmt = $pdo->prepare("SELECT 1 FROM tbl4dss_calibration_requests WHERE request_refkey = :ref LIMIT 1");
                    $stmt->execute([':ref' => $b]);
                    if ($stmt->fetch()) {
                        $reqBin = $b;
                        break;
                    }
                }
            }
            if (!$reqBin) {
                throw new RuntimeException('Request not found');
            }

            // Resolve user binary
            $userBin = null;
            if (is_string($userHex) && ctype_xdigit($userHex) && strlen($userHex) === 32) {
                $userBin = hex2bin($userHex);
            } else if ($userUuid !== '') {
                foreach ([true, false] as $swap) {
                    $b = uuidToBinary($userUuid, $swap);
                    $stmt = $pdo->prepare("SELECT 1 FROM tbl4dss_users WHERE user_refkey = :ref LIMIT 1");
                    $stmt->execute([':ref' => $b]);
                    if ($stmt->fetch()) {
                        $userBin = $b;
                        break;
                    }
                }
            }
            if (!$userBin) {
                throw new RuntimeException('Assignee user not found');
            }

            // Inactivate previous active assignments (single active assignment policy)
            $stmtInact = $pdo->prepare("UPDATE tbl4dss_lab_assignments SET assign_is_active = 0, assign_completed_at = assign_completed_at
                                         WHERE assign_request_refkey = :ref AND assign_is_active = 1");
            $stmtInact->execute([':ref' => $reqBin]);

            // Insert new assignment
            $assignRef = generateUuidV4Binary(true);
            $stmtIns = $pdo->prepare("INSERT INTO tbl4dss_lab_assignments (
                                        assign_refkey, assign_request_refkey, assign_user_refkey,
                                        assign_assigned_at, assign_is_active
                                      ) VALUES (
                                        :ref, :req, :usr, NOW(), 1
                                      )");
            $stmtIns->execute([':ref' => $assignRef, ':req' => $reqBin, ':usr' => $userBin]);

            // Update request status to IN_PROGRESS
            $stmtUpd = $pdo->prepare("UPDATE tbl4dss_calibration_requests SET request_status = 'IN_PROGRESS' WHERE request_refkey = :ref");
            $stmtUpd->execute([':ref' => $reqBin]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Assignment saved']);
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case "REQUEST_STATUS_UPDATE":
        header('Content-Type: application/json');

        // Inputs: request_refkey (UUID string) or request_hex, new_status, note
        $reqUuid   = trim($_POST['request_refkey'] ?? '');
        $reqHex    = trim($_POST['request_hex'] ?? '');
        $newStatus = strtoupper(trim($_POST['new_status'] ?? ''));
        $note      = trim($_POST['note'] ?? '');
        // Manual timestamp (optional)
        $statusTsRaw = trim($_POST['status_timestamp'] ?? '');
        $statusTs = null;
        if ($statusTsRaw !== '') {
            $normalized = str_replace('T', ' ', $statusTsRaw);
            $ts = strtotime($normalized);
            if ($ts !== false) { $statusTs = date('Y-m-d H:i:s', $ts); }
        }

        $allowed = ['PENDING', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED'];
        if (!in_array($newStatus, $allowed, true)) {
            echo json_encode(['success' => false, 'message' => 'Invalid status value']);
            break;
        }

        try {
            $pdo = db_connected();
            $pdo->beginTransaction();

            // Resolve request binary
            $reqBin = null;
            if (is_string($reqHex) && ctype_xdigit($reqHex) && strlen($reqHex) === 32) {
                $reqBin = hex2bin($reqHex);
            } else if ($reqUuid !== '') {
                foreach ([true, false] as $swap) {
                    $b = uuidToBinary($reqUuid, $swap);
                    $stmt = $pdo->prepare("SELECT 1 FROM tbl4dss_calibration_requests WHERE request_refkey = :ref LIMIT 1");
                    $stmt->execute([':ref' => $b]);
                    if ($stmt->fetch()) {
                        $reqBin = $b;
                        break;
                    }
                }
            }
            if (!$reqBin) {
                throw new RuntimeException('Request not found');
            }

            // Current user (performed_by)
            if (!isset($_SESSION['USER']['user_refkey_bin'])) {
                throw new RuntimeException('Not authenticated');
            }
            $userBin = base64_decode($_SESSION['USER']['user_refkey_bin']);

            // Update main request status
            $stmt = $pdo->prepare("UPDATE tbl4dss_calibration_requests SET request_status = :st, request_updated_at = :ts WHERE request_refkey = :ref");
            $stmt->execute([':st' => $newStatus, ':ts' => ($statusTs ?: date('Y-m-d H:i:s')), ':ref' => $reqBin]);
            if ($stmt->rowCount() === 0) {
                throw new RuntimeException('Request update failed');
            }

            // Insert tracking entry
            $trackRef = generateUuidV4Binary(true);
            $stmt = $pdo->prepare("INSERT INTO tbl4dss_request_track (
                                        track_refkey, track_request_refkey, track_status, track_note, track_performed_by, track_source, track_created_at
                                    ) VALUES (
                                        :ref, :req, :st, :note, :user, 'MANUAL', :ts
                                    )");
            $stmt->execute([
                ':ref' => $trackRef,
                ':req' => $reqBin,
                ':st'  => $newStatus,
                ':note' => $note,
                ':user' => $userBin,
                ':ts' => ($statusTs ?: date('Y-m-d H:i:s'))
            ]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Status updated']);
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case "LAB_SAVE":
        header('Content-Type: application/json');

        $labUuidStr = trim($_POST['lab_refkey'] ?? '');
        $labCode    = trim($_POST['lab_code'] ?? '');
        $labName    = trim($_POST['lab_name'] ?? '');
        $labDesc    = trim($_POST['lab_description'] ?? '');
        $labActive  = isset($_POST['lab_is_active']) ? (int)$_POST['lab_is_active'] : 1;

        if ($labCode === '' || $labName === '') {
            echo json_encode(['success' => false, 'message' => 'Lab code and name are required']);
            break;
        }

        try {
            $pdo = db_connected();
            $pdo->beginTransaction();

            $updated = false;
            // Support update by hex if provided
            $labHex = $_POST['lab_hex'] ?? '';
            if (is_string($labHex) && ctype_xdigit($labHex) && strlen($labHex) === 32) {
                $labBin = hex2bin($labHex);
                $stmt = $pdo->prepare("UPDATE tbl4dss_labs
                                          SET lab_code = :code,
                                              lab_name = :name,
                                              lab_description = :desc,
                                              lab_is_active = :active,
                                              lab_updated_at = NOW()
                                        WHERE lab_refkey = :ref");
                $stmt->execute([
                    ':code' => $labCode,
                    ':name' => $labName,
                    ':desc' => ($labDesc !== '' ? $labDesc : null),
                    ':active' => ($labActive ? 1 : 0),
                    ':ref' => $labBin
                ]);
                $updated = $stmt->rowCount() > 0;
            }

            if (!$updated && $labUuidStr !== '') {
                foreach ([true, false] as $swap) {
                    $labBin = uuidToBinary($labUuidStr, $swap);
                    $stmt = $pdo->prepare("UPDATE tbl4dss_labs
                                              SET lab_code = :code,
                                                  lab_name = :name,
                                                  lab_description = :desc,
                                                  lab_is_active = :active,
                                                  lab_updated_at = NOW()
                                            WHERE lab_refkey = :ref");
                    $stmt->execute([
                        ':code' => $labCode,
                        ':name' => $labName,
                        ':desc' => ($labDesc !== '' ? $labDesc : null),
                        ':active' => ($labActive ? 1 : 0),
                        ':ref' => $labBin
                    ]);
                    if ($stmt->rowCount() > 0) { $updated = true; break; }
                }
            }

            if (!$updated) {
                $labRef = generateUuidV4Binary(true);
                $stmt = $pdo->prepare("INSERT INTO tbl4dss_labs (
                                            lab_refkey, lab_code, lab_name, lab_description, lab_is_active, lab_created_at
                                        ) VALUES (
                                            :ref, :code, :name, :desc, :active, NOW()
                                        )");
                $stmt->execute([
                    ':ref' => $labRef,
                    ':code' => $labCode,
                    ':name' => $labName,
                    ':desc' => ($labDesc !== '' ? $labDesc : null),
                    ':active' => ($labActive ? 1 : 0)
                ]);
                $retUuid = binaryToUuid($labRef, true);
            } else {
                // If updated, prefer echoing back provided UUID string if present; otherwise derive from hex
                $retUuid = $labUuidStr !== '' ? $labUuidStr : (is_string($labHex) && ctype_xdigit($labHex) && strlen($labHex) === 32 ? binaryToUuid(hex2bin($labHex), true) : '');
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Lab saved', 'lab_refkey' => $retUuid]);
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case "LABS_INIT":
        header('Content-Type: application/json');
        try {
            $pdo = db_connected();
            $sql = "CREATE TABLE IF NOT EXISTS tbl4dss_labs (
                lab_refkey BINARY(16) NOT NULL PRIMARY KEY,
                lab_code VARCHAR(50) NOT NULL UNIQUE,
                lab_name VARCHAR(255) NOT NULL,
                lab_description TEXT NULL,
                lab_is_active BOOLEAN NOT NULL DEFAULT TRUE,
                lab_created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                lab_updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_lab_is_active (lab_is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $pdo->exec($sql);
            echo json_encode(['success' => true, 'message' => 'Labs table ensured']);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case "ROLE_SAVE":
        header('Content-Type: application/json');

        $roleUuidStr = trim($_POST['role_refkey'] ?? '');
        $roleName    = trim($_POST['role_name'] ?? '');
        $roleDesc    = trim($_POST['role_description'] ?? '');

        if ($roleName === '') {
            echo json_encode(['success' => false, 'message' => 'Role name is required']);
            break;
        }

        try {
            $pdo = db_connected();
            $pdo->beginTransaction();

            $updated = false;
            // Support update by hex if provided
            $roleHex = $_POST['role_hex'] ?? '';
            if (is_string($roleHex) && ctype_xdigit($roleHex) && strlen($roleHex) === 32) {
                $roleBin = hex2bin($roleHex);
                $stmt = $pdo->prepare("UPDATE tbl4dss_roles
                                          SET role_name = :name,
                                              role_description = :desc,
                                              updated_at = NOW()
                                        WHERE role_id = :ref");
                $stmt->execute([
                    ':name' => $roleName,
                    ':desc' => ($roleDesc !== '' ? $roleDesc : null),
                    ':ref' => $roleBin
                ]);
                $updated = $stmt->rowCount() > 0;
            }

            if (!$updated && $roleUuidStr !== '') {
                foreach ([true, false] as $swap) {
                    $roleBin = uuidToBinary($roleUuidStr, $swap);
                    $stmt = $pdo->prepare("UPDATE tbl4dss_roles
                                              SET role_name = :name,
                                                  role_description = :desc,
                                                  updated_at = NOW()
                                            WHERE role_id = :ref");
                    $stmt->execute([
                        ':name' => $roleName,
                        ':desc' => ($roleDesc !== '' ? $roleDesc : null),
                        ':ref' => $roleBin
                    ]);
                    if ($stmt->rowCount() > 0) { $updated = true; break; }
                }
            }

            if (!$updated) {
                $roleRef = generateUuidV4Binary(true);
                $stmt = $pdo->prepare("INSERT INTO tbl4dss_roles (
                                            role_id, role_name, role_description, created_at
                                        ) VALUES (
                                            :ref, :name, :desc, NOW()
                                        )");
                $stmt->execute([
                    ':ref' => $roleRef,
                    ':name' => $roleName,
                    ':desc' => ($roleDesc !== '' ? $roleDesc : null)
                ]);
                $retUuid = binaryToUuid($roleRef, true);
            } else {
                $retUuid = $roleUuidStr !== '' ? $roleUuidStr : (is_string($roleHex) && ctype_xdigit($roleHex) && strlen($roleHex) === 32 ? binaryToUuid(hex2bin($roleHex), true) : '');
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Role saved', 'role_refkey' => $retUuid]);
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case "ROLES_INIT":
        header('Content-Type: application/json');
        try {
            $pdo = db_connected();
            $sql = "CREATE TABLE IF NOT EXISTS tbl4dss_roles (
                role_id BINARY(16) NOT NULL PRIMARY KEY,
                role_name VARCHAR(50) NOT NULL UNIQUE,
                role_description VARCHAR(255),
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $pdo->exec($sql);
            echo json_encode(['success' => true, 'message' => 'Roles table ensured']);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case "LANGUAGE_SET":
        if (session_status() == PHP_SESSION_NONE) {
            // Session is not active, start it
            session_start();
        }
        // Get the JSON payload
        $data = json_decode(file_get_contents("php://input"), true);

        // Validate and update the session language
        if (isset($data['language']) && in_array($data['language'], ['en', 'th'])) {
            $_SESSION['LANGUAGE'] = $data['language'];
            echo json_encode(['success' => true]);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid language']);
        }
        break;

    case "USER_REGISTER":
        header('Content-Type: application/json');

        // Basic POST extraction (fields already validated client-side)
        $username = $_POST["USERNAME"] ?? '';
        $email    = $_POST["EMAIL"] ?? '';
        $password = $_POST["USERPASS"] ?? '';
        $first    = $_POST["NAMEFIRST"] ?? '';
        $last     = $_POST["NAMELAST"] ?? '';

        if ($username === '' || $email === '' || $password === '') {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            break;
        }

        try {
            $pdo = db_connected();
            $pdo->beginTransaction();

            // Generate a single user_refkey for both tables
            $userRef = generateUuidV4Binary(true);

            // Prepare users insert payload
            $user_array = [
                ["column" => "user_refkey",   "value" => $userRef],
                ["column" => "user_username",  "value" => $username],
                ["column" => "user_email",     "value" => $email],
                ["column" => "user_password",  "value" => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])],
                ["column" => "user_created_at", "value" => date("Y-m-d H:i:s")],
            ];

            // Insert into tbl4dss_users
            $okUser = insertRecord($pdo, 'tbl4dss_users', $user_array);
            if (!$okUser) {
                throw new RuntimeException('Failed to insert user record');
            }

            // Prepare profiles insert payload
            $profile_array = [
                ["column" => "profile_user_refkey", "value" => $userRef],
                ["column" => "profile_firstname",   "value" => $first],
                ["column" => "profile_lastname",    "value" => $last],
            ];

            // Insert into tbl4dss_profiles
            $okProfile = insertRecord($pdo, 'tbl4dss_profiles', $profile_array);
            if (!$okProfile) {
                throw new RuntimeException('Failed to insert profile record');
            }

            $pdo->commit();

            $response = [
                'success' => true,
                'message' => 'Registration successful',
                'user_refkey' => binaryToUuid($userRef, true)
            ];

            // Log success with returned response
            log_event('USER_REGISTER', 'success', 'User registered', $username, [
                'email' => $email,
                'user_refkey' => $response['user_refkey'],
                'response' => $response
            ]);

            echo json_encode($response);
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            // Handle duplicate key or other DB errors
            $code = method_exists($e, 'getCode') ? $e->getCode() : 0;
            $msg  = $e->getMessage();
            $response = ['success' => false, 'message' => $msg, 'code' => $code];

            // Log failure with returned response
            log_event('USER_REGISTER', 'failure', $msg, $username, [
                'email' => $email,
                'response' => $response
            ]);

            echo json_encode($response);
        }
        break;

    case "USER_LOGIN":
        header('Content-Type: application/json');

        $username = $_POST['USERNAME'] ?? '';
        $password = $_POST['USERPASS'] ?? '';

        if ($username === '' || $password === '') {
            $resp = ['success' => false, 'message' => 'Missing username or password'];
            log_event('USER_LOGIN', 'failure', 'Missing username or password', $username, ['response' => $resp]);
            echo json_encode($resp);
            break;
        }

        try {
            $pdo = db_connected();
            $sql = "SELECT 
                        u.user_refkey,
                        u.user_username,
                        u.user_password,
                        u.user_email,
                        u.user_is_active,
                        u.user_role_id,
                        BIN_TO_UUID(u.user_refkey,1) AS user_uuid,
                        BIN_TO_UUID(u.user_role_id,1) AS role_uuid,
                        r.role_name,
                        p.profile_firstname,
                        p.profile_lastname
                    FROM tbl4dss_users u
                    LEFT JOIN tbl4dss_profiles p ON p.profile_user_refkey = u.user_refkey
                    LEFT JOIN tbl4dss_roles r ON r.role_id = u.user_role_id
                    WHERE u.user_username = :username
                    LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $resp = ['success' => false, 'message' => 'Invalid credentials'];
                log_event('USER_LOGIN', 'failure', 'Invalid credentials', $username, ['response' => $resp]);
                echo json_encode($resp);
                break;
            }

            if ((int)$user['user_is_active'] !== 1) {
                $resp = ['success' => false, 'message' => 'User account is inactive'];
                log_event('USER_LOGIN', 'failure', 'User account is inactive', $username, ['response' => $resp]);
                echo json_encode($resp);
                break;
            }

            if (!password_verify($password, $user['user_password'])) {
                $resp = ['success' => false, 'message' => 'Invalid credentials'];
                log_event('USER_LOGIN', 'failure', 'Invalid credentials', $username, ['response' => $resp]);
                echo json_encode($resp);
                break;
            }

            // Build user payload
            $userPayload = [
                'user_uuid' => $user['user_uuid'] ?? null,
                'user_refkey_bin' => base64_encode($user['user_refkey']),
                'username' => $user['user_username'],
                'email' => $user['user_email'],
                'is_active' => (int)$user['user_is_active'],
                'role' => [
                    'role_uuid' => $user['role_uuid'] ?? null,
                    'role_name' => $user['role_name'] ?? null
                ],
                'profile' => [
                    'first_name' => $user['profile_firstname'] ?? null,
                    'last_name' => $user['profile_lastname'] ?? null
                ]
            ];

            // Set session for downstream pages
            $_SESSION['USER'] = [
                'user_refkey_bin' => $userPayload['user_refkey_bin'],
                'user_uuid' => $userPayload['user_uuid'],
                'username' => $userPayload['username'],
                'email' => $userPayload['email'],
                'role_uuid' => $userPayload['role']['role_uuid'],
                'role_name' => $userPayload['role']['role_name'],
                'first_name' => $userPayload['profile']['first_name'],
                'last_name' => $userPayload['profile']['last_name']
            ];

            $resp = ['success' => true, 'message' => 'Login successful', 'user' => $userPayload];
            log_event('USER_LOGIN', 'success', 'Login successful', $username, ['response' => $resp]);
            echo json_encode($resp);
        } catch (Throwable $e) {
            $resp = ['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()];
            log_event('USER_LOGIN', 'failure', $e->getMessage(), $username, ['response' => $resp]);
            http_response_code(500);
            echo json_encode($resp);
        }
        break;

    case "USER_STATUS_TOGGLE":
        header('Content-Type: application/json');

        $uuid = $_POST['user_refkey'] ?? '';
        $active = isset($_POST['user_is_active']) ? (int)$_POST['user_is_active'] : null;
        $username = $_POST['username'] ?? '';

        if ($uuid === '' || $active === null) {
            echo json_encode(['success' => false, 'message' => 'Missing parameters']);
            break;
        }

        try {
            $pdo = db_connected();
            $bin = uuidToBinary($uuid, true);
            $stmt = $pdo->prepare("UPDATE tbl4dss_users SET user_is_active = :active, user_updated_at = NOW() WHERE user_refkey = :ref");
            $stmt->execute([':active' => $active, ':ref' => $bin]);
            $ok = $stmt->rowCount() > 0;

            if (!$ok) {
                throw new RuntimeException('User not found');
            }

            $response = [
                'success' => true,
                'message' => 'Status updated',
                'user_refkey' => $uuid,
                'user_is_active' => $active
            ];

            log_event('USER_STATUS_TOGGLE', 'success', 'Status updated', $username, [
                'user_refkey' => $uuid,
                'user_is_active' => $active,
                'response' => $response
            ]);

            echo json_encode($response);
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            $response = ['success' => false, 'message' => $msg];

            log_event('USER_STATUS_TOGGLE', 'failure', $msg, $username, [
                'user_refkey' => $uuid,
                'user_is_active' => $active,
                'response' => $response
            ]);

            echo json_encode($response);
        }
        break;

    case "USER_ROLE_UPDATE":
        header('Content-Type: application/json');

        $userUuid = trim($_POST['user_refkey'] ?? '');
        $roleUuid = trim($_POST['role_uuid'] ?? '');
        $roleHex  = trim($_POST['role_hex'] ?? '');
        $username = $_POST['username'] ?? '';

        if ($userUuid === '' || ($roleUuid === '' && $roleHex === '')) {
            echo json_encode(['success' => false, 'message' => 'Missing parameters']);
            break;
        }

        try {
            $pdo = db_connected();

            // Resolve user binary
            $userBin = null;
            foreach ([true, false] as $swap) {
                $b = uuidToBinary($userUuid, $swap);
                $stmt = $pdo->prepare("SELECT 1 FROM tbl4dss_users WHERE user_refkey = :ref LIMIT 1");
                $stmt->execute([':ref' => $b]);
                if ($stmt->fetch()) { $userBin = $b; break; }
            }
            if (!$userBin) { throw new RuntimeException('User not found'); }

            // Resolve role binary (allow uuid or hex)
            $roleBin = null;
            if ($roleHex !== '' && ctype_xdigit($roleHex) && strlen($roleHex) === 32) {
                $roleBin = hex2bin($roleHex);
            } else if ($roleUuid !== '') {
                foreach ([true, false] as $swap) {
                    $b = uuidToBinary($roleUuid, $swap);
                    $stmt = $pdo->prepare("SELECT 1 FROM tbl4dss_roles WHERE role_id = :ref LIMIT 1");
                    $stmt->execute([':ref' => $b]);
                    if ($stmt->fetch()) { $roleBin = $b; break; }
                }
            }
            if (!$roleBin) { throw new RuntimeException('Role not found'); }

            // Update user role
            $stmt = $pdo->prepare("UPDATE tbl4dss_users SET user_role_id = :role, user_updated_at = NOW() WHERE user_refkey = :ref");
            $stmt->execute([':role' => $roleBin, ':ref' => $userBin]);
            if ($stmt->rowCount() === 0) { throw new RuntimeException('Role update failed'); }

            $resp = [
                'success' => true,
                'message' => 'User role updated',
                'user_refkey' => $userUuid,
                'role_uuid' => $roleUuid !== '' ? $roleUuid : binaryToUuid($roleBin, true)
            ];

            log_event('USER_ROLE_UPDATE', 'success', 'User role updated', $username, ['payload' => $_POST, 'response' => $resp]);
            echo json_encode($resp);
        } catch (Throwable $e) {
            $resp = ['success' => false, 'message' => $e->getMessage()];
            log_event('USER_ROLE_UPDATE', 'failure', $e->getMessage(), $username, ['payload' => $_POST, 'response' => $resp]);
            echo json_encode($resp);
        }
        break;

    case "ACTIONS_INIT":
        header('Content-Type: application/json');
        try {
            $pdo = db_connected();
            // Create actions table
            $pdo->exec("CREATE TABLE IF NOT EXISTS tbl4dss_actions (
                action_id BINARY(16) NOT NULL PRIMARY KEY,
                action_page VARCHAR(50) NOT NULL,
                action_name VARCHAR(100) NOT NULL,
                UNIQUE KEY uniq_action_page_name (action_page, action_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            // Role-actions link
            $pdo->exec("CREATE TABLE IF NOT EXISTS tbl4dss_role_actions (
                ra_role_id BINARY(16) NOT NULL,
                ra_action_id BINARY(16) NOT NULL,
                PRIMARY KEY (ra_role_id, ra_action_id),
                CONSTRAINT fk_ra_role FOREIGN KEY (ra_role_id)
                    REFERENCES tbl4dss_roles(role_id) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT fk_ra_action FOREIGN KEY (ra_action_id)
                    REFERENCES tbl4dss_actions(action_id) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            // User-actions link
            $pdo->exec("CREATE TABLE IF NOT EXISTS tbl4dss_user_actions (
                ua_user_refkey BINARY(16) NOT NULL,
                ua_action_id BINARY(16) NOT NULL,
                PRIMARY KEY (ua_user_refkey, ua_action_id),
                CONSTRAINT fk_ua_user FOREIGN KEY (ua_user_refkey)
                    REFERENCES tbl4dss_users(user_refkey) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT fk_ua_action FOREIGN KEY (ua_action_id)
                    REFERENCES tbl4dss_actions(action_id) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            echo json_encode(['success' => true, 'message' => 'Actions tables created/ensured']);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case "ACTIONS_SCAN":
        header('Content-Type: application/json');
        try {
            $pdo = db_connected();

            $modelsDir = APPLICATION_PATH . DIRECTORY_SEPARATOR . 'model' . DIRECTORY_SEPARATOR;
            $files = array_filter(scandir($modelsDir), function($f){ return preg_match('/\.php$/', $f); });
            $actions = [];
            foreach ($files as $file) {
                if ($file === 'guest.php') { continue; }
                $page = pathinfo($file, PATHINFO_FILENAME);
                $content = file_get_contents($modelsDir . $file);
                if ($content === false) { continue; }
                // Extract case labels: case "action-name":
                if (preg_match_all('/case\s+[\"\']([^\"\']+)[\"\']\s*:/', $content, $m)) {
                    foreach ($m[1] as $act) {
                        $actions[] = [$page, $act];
                    }
                }
            }

            // Upsert actions
            $inserted = 0; $skipped = 0;
            foreach ($actions as [$page,$act]) {
                // Check exists
                $stmt = $pdo->prepare("SELECT action_id FROM tbl4dss_actions WHERE action_page = :p AND action_name = :a LIMIT 1");
                $stmt->execute([':p'=>$page, ':a'=>$act]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) { $skipped++; continue; }
                $id = generateUuidV4Binary(true);
                $stmt = $pdo->prepare("INSERT INTO tbl4dss_actions (action_id, action_page, action_name) VALUES (:id,:p,:a)");
                $stmt->execute([':id'=>$id, ':p'=>$page, ':a'=>$act]);
                $inserted++;
            }

            echo json_encode(['success' => true, 'inserted' => $inserted, 'scanned' => count($actions)]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case "ROLE_ACTIONS_GET":
        header('Content-Type: application/json');
        try {
            $pdo = db_connected();
            $roleUuid = trim($_GET['role_uuid'] ?? '');
            $roleHex  = trim($_GET['role_hex'] ?? '');
            $roleBin = null;
            if ($roleHex !== '' && ctype_xdigit($roleHex) && strlen($roleHex)===32) { $roleBin = hex2bin($roleHex); }
            else if ($roleUuid !== '') { foreach ([true,false] as $swap){ $b=uuidToBinary($roleUuid,$swap); $st=$pdo->prepare("SELECT 1 FROM tbl4dss_roles WHERE role_id=:r LIMIT 1"); $st->execute([':r'=>$b]); if($st->fetch()){ $roleBin=$b; break; } } }
            if (!$roleBin) { throw new RuntimeException('Role not found'); }
            $stmt = $pdo->prepare("SELECT a.action_page, a.action_name FROM tbl4dss_role_actions ra JOIN tbl4dss_actions a ON a.action_id = ra.ra_action_id WHERE ra.ra_role_id = :r ORDER BY a.action_page, a.action_name");
            $stmt->execute([':r'=>$roleBin]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success'=>true,'actions'=>$rows]);
        } catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
        break;

    case "USER_ACTIONS_GET":
        header('Content-Type: application/json');
        try {
            $pdo = db_connected();
            $userUuid = trim($_GET['user_refkey'] ?? '');
            $userBin = null;
            foreach ([true,false] as $swap){ $b=uuidToBinary($userUuid,$swap); $st=$pdo->prepare("SELECT 1 FROM tbl4dss_users WHERE user_refkey=:u LIMIT 1"); $st->execute([':u'=>$b]); if($st->fetch()){ $userBin=$b; break; } }
            if (!$userBin) { throw new RuntimeException('User not found'); }
            $stmt = $pdo->prepare("SELECT a.action_page, a.action_name FROM tbl4dss_user_actions ua JOIN tbl4dss_actions a ON a.action_id = ua.ua_action_id WHERE ua.ua_user_refkey = :u ORDER BY a.action_page, a.action_name");
            $stmt->execute([':u'=>$userBin]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success'=>true,'actions'=>$rows]);
        } catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
        break;

    case "ROLE_ACTIONS_SAVE":
        header('Content-Type: application/json');
        try {
            $pdo = db_connected();
            $pdo->beginTransaction();
            // Support both x-www-form-urlencoded and application/json bodies
            $ct = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
            $isJson = stripos($ct, 'application/json') !== false;
            $payload = [];
            if ($isJson) {
                $raw = file_get_contents('php://input');
                $payload = json_decode($raw, true) ?: [];
            }
            $roleUuid = trim(($isJson ? ($payload['role_uuid'] ?? '') : ($_POST['role_uuid'] ?? '')));
            $roleHex  = trim(($isJson ? ($payload['role_hex'] ?? '') : ($_POST['role_hex'] ?? '')));
            $keysJson = $isJson ? ($payload['action_keys'] ?? []) : ($_POST['action_keys'] ?? '[]');
            $keys = is_string($keysJson) ? json_decode($keysJson, true) : (is_array($keysJson) ? $keysJson : []);
            if (!is_array($keys)) { $keys = []; }
            // Resolve role
            $roleBin = null;
            if ($roleHex !== '' && ctype_xdigit($roleHex) && strlen($roleHex)===32) { $roleBin = hex2bin($roleHex); }
            else if ($roleUuid !== '') { foreach ([true,false] as $swap){ $b=uuidToBinary($roleUuid,$swap); $st=$pdo->prepare("SELECT 1 FROM tbl4dss_roles WHERE role_id=:r LIMIT 1"); $st->execute([':r'=>$b]); if($st->fetch()){ $roleBin=$b; break; } } }
            if (!$roleBin) { throw new RuntimeException('Role not found'); }
            // Clear existing
            $pdo->prepare("DELETE FROM tbl4dss_role_actions WHERE ra_role_id = :r")->execute([':r'=>$roleBin]);
            // Insert new
            $ins = $pdo->prepare("INSERT INTO tbl4dss_role_actions (ra_role_id, ra_action_id) VALUES (:r,:a)");
            $sel = $pdo->prepare("SELECT action_id FROM tbl4dss_actions WHERE action_page = :p AND action_name = :n LIMIT 1");
            $count = 0;
            foreach ($keys as $k) {
                if (!is_string($k) || strpos($k, ':') === false) { continue; }
                [$p,$n] = explode(':', $k, 2);
                $sel->execute([':p'=>$p, ':n'=>$n]);
                $row = $sel->fetch(PDO::FETCH_ASSOC);
                if (!$row) { continue; }
                $ins->execute([':r'=>$roleBin, ':a'=>$row['action_id']]);
                $count++;
            }
            $pdo->commit();
            echo json_encode(['success'=>true,'saved'=>$count]);
        } catch (Throwable $e) { if (isset($pdo)&&$pdo->inTransaction()){$pdo->rollBack();} echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
        break;

    case "USER_ACTIONS_SAVE":
        header('Content-Type: application/json');
        try {
            $pdo = db_connected();
            $pdo->beginTransaction();
            // Support both x-www-form-urlencoded and application/json bodies
            $ct = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
            $isJson = stripos($ct, 'application/json') !== false;
            $payload = [];
            if ($isJson) {
                $raw = file_get_contents('php://input');
                $payload = json_decode($raw, true) ?: [];
            }
            $userUuid = trim(($isJson ? ($payload['user_refkey'] ?? '') : ($_POST['user_refkey'] ?? '')));
            $keysJson = $isJson ? ($payload['action_keys'] ?? []) : ($_POST['action_keys'] ?? '[]');
            $keys = is_string($keysJson) ? json_decode($keysJson, true) : (is_array($keysJson) ? $keysJson : []);
            if (!is_array($keys)) { $keys = []; }
            // Resolve user
            $userBin = null;
            foreach ([true,false] as $swap){ $b=uuidToBinary($userUuid,$swap); $st=$pdo->prepare("SELECT 1 FROM tbl4dss_users WHERE user_refkey=:u LIMIT 1"); $st->execute([':u'=>$b]); if($st->fetch()){ $userBin=$b; break; } }
            if (!$userBin) { throw new RuntimeException('User not found'); }
            // Clear existing
            $pdo->prepare("DELETE FROM tbl4dss_user_actions WHERE ua_user_refkey = :u")->execute([':u'=>$userBin]);
            // Insert new
            $ins = $pdo->prepare("INSERT INTO tbl4dss_user_actions (ua_user_refkey, ua_action_id) VALUES (:u,:a)");
            $sel = $pdo->prepare("SELECT action_id FROM tbl4dss_actions WHERE action_page = :p AND action_name = :n LIMIT 1");
            $count = 0;
            foreach ($keys as $k) {
                if (!is_string($k) || strpos($k, ':') === false) { continue; }
                [$p,$n] = explode(':', $k, 2);
                $sel->execute([':p'=>$p, ':n'=>$n]);
                $row = $sel->fetch(PDO::FETCH_ASSOC);
                if (!$row) { continue; }
                $ins->execute([':u'=>$userBin, ':a'=>$row['action_id']]);
                $count++;
            }
            $pdo->commit();
            echo json_encode(['success'=>true,'saved'=>$count]);
        } catch (Throwable $e) { if (isset($pdo)&&$pdo->inTransaction()){$pdo->rollBack();} echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
        break;

    case "ACTIONS_LIST":
        header('Content-Type: application/json');
        try {
            $pdo = db_connected();
            $stmt = $pdo->query("SELECT action_page, action_name FROM tbl4dss_actions ORDER BY action_page, action_name");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'actions' => $rows]);
        } catch (Throwable $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        break;

    case "ROLES_LIST":
        header('Content-Type: application/json');
        try {
            $pdo = db_connected();
            $stmt = $pdo->query("SELECT BIN_TO_UUID(role_id,1) AS role_uuid, HEX(role_id) AS role_hex, role_name FROM tbl4dss_roles ORDER BY role_name");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'roles' => $rows]);
        } catch (Throwable $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        break;

    case "USERS_LIST":
        header('Content-Type: application/json');
        try {
            $pdo = db_connected();
            $stmt = $pdo->query("SELECT BIN_TO_UUID(u.user_refkey,1) AS user_uuid, u.user_username, r.role_name FROM tbl4dss_users u LEFT JOIN tbl4dss_roles r ON r.role_id = u.user_role_id ORDER BY u.user_username");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'users' => $rows]);
        } catch (Throwable $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        break;

    case "CUSTOMER_SAVE":
        header('Content-Type: application/json');

        $uuid = $_POST['customer_refkey'] ?? '';
        $name = trim($_POST['customer_name'] ?? '');
        $address = trim($_POST['customer_address'] ?? '');
        $phone = trim($_POST['customer_phone'] ?? '');
        $fax = trim($_POST['customer_fax'] ?? '');
        $email = trim($_POST['customer_email'] ?? '');
        $isActive = isset($_POST['customer_is_active']) ? (int)$_POST['customer_is_active'] : 1;

        if ($name === '') {
            $resp = ['success' => false, 'message' => 'Customer name is required'];
            log_event('CUSTOMER_SAVE', 'failure', 'Missing customer_name', null, ['payload' => $_POST, 'response' => $resp]);
            echo json_encode($resp);
            break;
        }

        try {
            $pdo = db_connected();
            $pdo->beginTransaction();

            if ($uuid !== '') {
                // Update existing customer
                $bin = uuidToBinary($uuid, true);
                $stmt = $pdo->prepare("UPDATE tbl4dss_customers
                                        SET customer_name = :name,
                                            customer_address = :address,
                                            customer_phone = :phone,
                                            customer_fax = :fax,
                                            customer_email = :email,
                                            customer_is_active = :active,
                                            customer_updated_at = NOW()
                                        WHERE customer_refkey = :ref");
                $stmt->execute([
                    ':name' => $name,
                    ':address' => $address,
                    ':phone' => $phone,
                    ':fax' => $fax,
                    ':email' => $email,
                    ':active' => $isActive,
                    ':ref' => $bin
                ]);
                $ok = $stmt->rowCount() > 0;
                if (!$ok) {
                    throw new RuntimeException('Customer not found');
                }
                $retUuid = $uuid;
            } else {
                // Insert new customer
                $ref = generateUuidV4Binary(true);
                $stmt = $pdo->prepare("INSERT INTO tbl4dss_customers (
                                            customer_refkey, customer_name, customer_address, customer_phone, customer_fax, customer_email, customer_is_active, customer_created_at
                                        ) VALUES (
                                            :ref, :name, :address, :phone, :fax, :email, :active, NOW()
                                        )");
                $stmt->execute([
                    ':ref' => $ref,
                    ':name' => $name,
                    ':address' => $address,
                    ':phone' => $phone,
                    ':fax' => $fax,
                    ':email' => $email,
                    ':active' => $isActive
                ]);
                $retUuid = binaryToUuid($ref, true);
            }

            $pdo->commit();

            $resp = ['success' => true, 'message' => 'Customer saved', 'customer_refkey' => $retUuid];
            log_event('CUSTOMER_SAVE', 'success', 'Customer saved', null, ['response' => $resp]);
            echo json_encode($resp);
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $resp = ['success' => false, 'message' => $e->getMessage()];
            log_event('CUSTOMER_SAVE', 'failure', $e->getMessage(), null, ['payload' => $_POST, 'response' => $resp]);
            echo json_encode($resp);
        }
        break;

endswitch;
