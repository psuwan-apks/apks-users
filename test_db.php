<?php
$dir_root = realpath(__DIR__);
require_once $dir_root . "/app/lib/functions-mysql.php";
$pdo = db_connected();
$stmt = $pdo->query("SELECT * FROM `tbl4users_users` ORDER BY id DESC LIMIT 5");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($users);
?>
