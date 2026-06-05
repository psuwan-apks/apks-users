<?php

$config = [
    'PATH_TO_MODEL' => APPLICATION_PATH . DS . 'model' . DS,
    'PATH_TO_VIEW' => APPLICATION_PATH . DS . 'view' . DS,
    'PATH_TO_LANG' => APPLICATION_PATH . DS . 'lang' . DS,
    'PATH_TO_DATA' => APPLICATION_PATH . DS . 'data' . DS,
    'PATH_TO_PDF' => APPLICATION_PATH . DS . 'pdf' . DS,
    'PATH_TO_LIB' => APPLICATION_PATH . DS . 'lib' . DS,
];

include_once $config['PATH_TO_LIB'] . 'functions.php';
include_once $config['PATH_TO_LIB'] . 'functions-mysql.php';
include_once $config['PATH_TO_LIB'] . 'functions-datetime.php';
include_once $config['PATH_TO_LIB'] . 'functions-lang.php';
include_once $config['PATH_TO_LIB'] . 'fpdf19' . DS . 'fpdf.php';
