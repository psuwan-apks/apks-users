<?php
global $config;

if (isset($view) && file_exists($view)) {
    include_once $view;
} else {
    include_once $config['PATH_TO_VIEW'] . '404.php';
}
