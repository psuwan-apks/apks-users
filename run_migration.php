<?php
require_once __DIR__ . '/app/lib/functions-mysql.php';

try {
    $pdo = db_connected();
    $sql = file_get_contents(__DIR__ . '/databases/migration_oauth_update.sql');
    $pdo->exec($sql);
    echo "Migration applied successfully.\n";
} catch (PDOException $e) {
    echo "Error applying migration: " . $e->getMessage() . "\n";
}
