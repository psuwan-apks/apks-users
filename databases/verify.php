<?php
/**
 * Database Verification Script for APKS - No Foreign Keys Schema
 * Run via: php databases/verify.php
 */
define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/app/lib/functions-mysql.php';

$pdo = db_connected();
$ok  = true;

function check(bool $pass, string $label): void {
    global $ok;
    if ($pass) {
        echo "  ✅ $label\n";
    } else {
        echo "  ❌ $label\n";
        $ok = false;
    }
}

echo "\n=== APKS Database Verification ===\n";
echo "    DB: " . DB_NAME . "  HOST: " . DB_HOST . ":" . DB_PORT . "\n\n";

// 1. Connection
echo "[1. Connection]\n";
check(true, "Connected to MySQL: " . DB_NAME);

// 2. Tables exist
echo "\n[2. Tables]\n";
$tables = ['tbl4users_users', 'tbl4users_oauth_clients', 'tbl4users_oauth_codes', 'tbl4users_oauth_tokens'];
foreach ($tables as $t) {
    $res = $pdo->query("SHOW TABLES LIKE '$t'")->rowCount();
    check($res > 0, "Table exists: $t");
}

// 3. Row counts
echo "\n[3. Row Counts]\n";
foreach ($tables as $t) {
    $count = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    echo "    → $t: $count row(s)\n";
}

// 4. No foreign keys
echo "\n[4. Foreign Key Constraints]\n";
$fkCount = $pdo->query(
    "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
     WHERE CONSTRAINT_TYPE = 'FOREIGN KEY'
     AND TABLE_SCHEMA = '" . DB_NAME . "'"
)->fetchColumn();
check($fkCount == 0, "No foreign keys — all referential integrity at app layer ($fkCount FK found)");

// 5. Engine check
echo "\n[5. Storage Engine]\n";
$engineRows = $pdo->query(
    "SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = '" . DB_NAME . "'"
)->fetchAll();
foreach ($engineRows as $r) {
    check($r['ENGINE'] === 'MyISAM', "Engine={$r['ENGINE']} for {$r['TABLE_NAME']}");
}

// 6. Index check (codes + tokens)
echo "\n[6. Indexes]\n";
foreach (['tbl4users_oauth_codes','tbl4users_oauth_tokens'] as $t) {
    $idxRows = $pdo->query("SHOW INDEX FROM `$t`")->fetchAll(PDO::FETCH_COLUMN, 2);
    $idxNames = implode(', ', array_unique($idxRows));
    check(count($idxRows) > 1, "Indexes on $t: $idxNames");
}

// 7. Credential sanity check
echo "\n[7. Credentials]\n";
foreach (['admin' => 'admin123', 'user' => 'password'] as $uname => $plain) {
    $row = $pdo->prepare("SELECT `password_hash` FROM `tbl4users_users` WHERE `username` = ?");
    $row->execute([$uname]);
    $hash = $row->fetchColumn();
    check($hash && password_verify($plain, $hash), "password_verify() for '$uname'");
}

// 8. OAuth clients
echo "\n[8. OAuth Clients]\n";
$clients = ['demo-client', 'apks-users-client'];
foreach ($clients as $cid) {
    $row = $pdo->prepare("SELECT `first_party` FROM `tbl4users_oauth_clients` WHERE `client_id` = ?");
    $row->execute([$cid]);
    $fp = $row->fetchColumn();
    check($fp !== false, "Client exists: $cid (first_party=$fp)");
}

echo "\n" . ($ok ? "✅ ALL CHECKS PASSED\n" : "❌ SOME CHECKS FAILED\n") . "\n";
