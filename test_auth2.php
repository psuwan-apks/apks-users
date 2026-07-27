<?php
session_start();
const DS = DIRECTORY_SEPARATOR;
$dir_root = realpath(__DIR__);
$dir_app = $dir_root . DS . "app";
define("APPLICATION_PATH", realpath($dir_app));
require_once APPLICATION_PATH . "/config/config.php";

$username = "test_" . time();
$password = "test_pass";

if (User::createUser($username, $password)) {
    echo "User created.\n";
    $user = User::findByUsername($username);
    echo "Hash in DB: " . $user["password_hash"] . "\n";
    if (User::authenticate($username, $password)) {
        echo "Auth successful.\n";
    } else {
        echo "Auth failed.\n";
    }
} else {
    echo "Create failed.\n";
}
?>
