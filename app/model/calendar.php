<?php
global $config;

$current_view = $config['PATH_TO_VIEW'];

$ACT2PROCESS = get("action");

switch ($ACT2PROCESS):
    default:
    case "calendar":
        $view = $current_view . "calendar.php";
        break;

endswitch;