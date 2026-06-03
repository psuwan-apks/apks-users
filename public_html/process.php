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

endswitch;
