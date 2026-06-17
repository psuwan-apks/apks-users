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

    case 'LANGUAGE_SET':
        // Read JSON body from the fetch request
        $input = json_decode(file_get_contents('php://input'), true);
        $lang = $input['language'] ?? '';

        // Validate: only allow known language codes
        $allowed = ['en', 'th'];
        if (in_array($lang, $allowed, true)) {
            $_SESSION['LANGUAGE'] = $lang;
            echo json_encode(['status' => 'success', 'language' => $lang]);
        } else {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid language']);
        }
        break;

        // Default CMD2PROCESS
    default:
        echo json_encode(['status' => 'error', 'message' => 'No command to process']);
        break;

endswitch;
