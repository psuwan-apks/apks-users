<?php
// Display all PHP's error
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Date Default TimeZone set
date_default_timezone_set('Asia/Bangkok');

// Session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

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
$lang = $_SESSION['LANGUAGE'] ?? ($_SESSION['LANGUAGE'] = 'th');

$translations = include $config['PATH_TO_LANG'] . $lang . '.php';
/* Language translation */

$page = get('page', 'guest');
$model = $config['PATH_TO_MODEL'] . $page . '.php';
$view = $config['PATH_TO_VIEW'] . $page . '.php';
$_404 = $config['PATH_TO_VIEW'] . '404.php';

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
