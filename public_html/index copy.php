<?php
// Display all PHP's error
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Date Default TimeZone set
date_default_timezone_set('Asia/Bangkok');

// Session
session_start();

// Global variables
global $config;

const DS = DIRECTORY_SEPARATOR;
$dir_root = realpath(dirname(__FILE__, 2));
$dir_app = $dir_root . DS . 'app';
defined('APPLICATION_PATH') || define('APPLICATION_PATH', realpath($dir_app));

/* Load setting path (Simple MVC bootstrap)
   This project uses a very simple home-grown MVC pattern:
   - Request param 'page' selects app/model/<page>.php and app/view/<page>.php
   - The selected view is rendered inside app/view/layout.php
   - Language is loaded from app/lang/<lang>.php based on session
*/
require APPLICATION_PATH . DS . 'config' . DS . 'config.php';

/* Language translation */
$lang = $_SESSION['LANGUAGE'] ?? ($_SESSION['LANGUAGE'] = 'en');

$translations = include $config['PATH_TO_LANG'] . $lang . '.php';
/* Language translation */

$page = get('page', 'guest');
$model = $config['PATH_TO_MODEL'] . $page . '.php';
$view = $config['PATH_TO_VIEW'] . $page . '.php';
$_404 = $config['PATH_TO_VIEW'] . '404.php';

// Enforce action-based access control (role precedence, then user-specific) for non-guest pages
try {
    if ($page !== 'guest') {
        $action = get('action', '');
        // Allow if no specific action is requested (to avoid breaking legacy pages)
        if ($action !== '') {
            $pdo = db_connected();
            // Resolve action id; if action not registered, allow (scan may not have run yet)
            $stmtA = $pdo->prepare("SELECT action_id FROM tbl4dss_actions WHERE action_page = :p AND action_name = :a LIMIT 1");
            $stmtA->execute([':p' => $page, ':a' => $action]);
            $actRow = $stmtA->fetch(PDO::FETCH_ASSOC);
            if ($actRow && isset($actRow['action_id'])) {
                $actionId = $actRow['action_id'];
                $allowed = false;
                // Role check first (higher precedence)
                $roleUuid = $_SESSION['USER']['role_uuid'] ?? null;
                if ($roleUuid) {
                    // Try both UUID byte orders for compatibility
                    $roleBins = [];
                    foreach ([true, false] as $swap) { $roleBins[] = uuidToBinary($roleUuid, $swap); }
                    $stmtR = $pdo->prepare("SELECT 1 FROM tbl4dss_role_actions WHERE ra_role_id = :r AND ra_action_id = :a LIMIT 1");
                    foreach ($roleBins as $rb) {
                        $stmtR->execute([':r' => $rb, ':a' => $actionId]);
                        if ($stmtR->fetch()) { $allowed = true; break; }
                    }
                }
                // User-specific fallback only if role not already allowed
                if (!$allowed && isset($_SESSION['USER']['user_refkey_bin'])) {
                    $userBin = base64_decode($_SESSION['USER']['user_refkey_bin']);
                    $stmtU = $pdo->prepare("SELECT 1 FROM tbl4dss_user_actions WHERE ua_user_refkey = :u AND ua_action_id = :a LIMIT 1");
                    $stmtU->execute([':u' => $userBin, ':a' => $actionId]);
                    if ($stmtU->fetch()) { $allowed = true; }
                }

                if (!$allowed) {
                    // Deny access by rendering 404
                    $model = null; // avoid loading model
                    $view = $_404;
                }
            }
        }
    }
} catch (Throwable $e) {
    // On any error during permission check, deny access to be safe
    $model = null;
    $view = $_404;
}


if ($model && file_exists($model)) {
    require_once $model;
}

if (file_exists($view)) {
    // Simple MVC: render the selected view inside the main layout
    $main_content = $view;
    include_once $config['PATH_TO_VIEW'] . "layout.php";
} else {
    include_once $config['PATH_TO_VIEW'] . '404.php';
}
