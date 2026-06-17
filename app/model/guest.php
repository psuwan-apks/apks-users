<?php
global $config;

$current_view = $config['PATH_TO_VIEW'];

$ACT2PROCESS = get("action");

switch ($ACT2PROCESS):
    default:
    case "home":
        $view = $current_view . "page-dashboard.php";
        break;

    case "aboutus":
        $view = $current_view . "page-aboutus.php";
        break;

    case"contact":
        $view = $current_view . "page-contact.php";
        break;

endswitch;