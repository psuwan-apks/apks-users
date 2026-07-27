<?php
/**
 * Database Cleanup Script for APKS
 * Run via: php databases/cleanup.php
 */

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/app/lib/functions-mysql.php';
require_once APP_ROOT . '/app/model/user.php';

try {
    $pdo = db_connected();
    echo "\n=== Starting APKS Database Cleanup ===\n";
    echo "DB: " . DB_NAME . "  HOST: " . DB_HOST . ":" . DB_PORT . "\n\n";

    // 1. Drop obsolete tables
    $obsoleteTables = ['tbl4users_permissions', 'tbl4users_roles', 'tbl4users_user_roles'];
    foreach ($obsoleteTables as $table) {
        echo "Checking obsolete table: $table...\n";
        $exists = $pdo->query("SHOW TABLES LIKE '$table'")->rowCount();
        if ($exists > 0) {
            $pdo->exec("DROP TABLE `$table`");
            echo "  🗑️ Dropped table `$table` successfully.\n";
        } else {
            echo "  ℹ️ Table `$table` does not exist. Skipping.\n";
        }
    }

    // 2. Truncate transient runtime tables
    $transientTables = ['tbl4users_oauth_codes', 'tbl4users_oauth_tokens', 'tbl4users_oauth_consents'];
    foreach ($transientTables as $table) {
        echo "Truncating transient table: $table...\n";
        $exists = $pdo->query("SHOW TABLES LIKE '$table'")->rowCount();
        if ($exists > 0) {
            $pdo->exec("TRUNCATE TABLE `$table`");
            echo "  🧹 Truncated table `$table` successfully.\n";
        } else {
            echo "  ⚠️ Table `$table` does not exist.\n";
        }
    }

    // 3. Populate missing UUIDs for users
    echo "Checking user records for missing UUIDs...\n";
    $users = $pdo->query("SELECT `id`, `username`, `uuid` FROM `tbl4users_users` WHERE `uuid` IS NULL OR `uuid` = ''")->fetchAll();
    
    if (empty($users)) {
        echo "  ✅ All users already have UUIDs populated.\n";
    } else {
        $stmtUpdate = $pdo->prepare("UPDATE `tbl4users_users` SET `uuid` = :uuid WHERE `id` = :id");
        foreach ($users as $u) {
            $newUuid = User::generateUuid();
            $stmtUpdate->execute([
                ':uuid' => $newUuid,
                ':id' => $u['id']
            ]);
            echo "  🆔 Generated and assigned UUID `$newUuid` to user `{$u['username']}`.\n";
        }
        echo "  ✅ Populated UUIDs for " . count($users) . " user(s).\n";
    }

    echo "\n🎉 DATABASE CLEANUP COMPLETED SUCCESSFULLY!\n\n";

} catch (PDOException $e) {
    echo "\n❌ Database Error: " . $e->getMessage() . "\n\n";
    exit(1);
}
