<?php
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
if (!defined('APPLICATION_PATH')) {
    $dir_root = realpath(dirname(__FILE__, 2));
    $dir_app = $dir_root . DS . 'app';
    define('APPLICATION_PATH', realpath($dir_app));
}
require_once APPLICATION_PATH . DS . 'config' . DS . 'config.php';
global $config;
require_once $config["PATH_TO_PDF"] . 'temp-header.php';

$pdf = new PDF();
$pdf->AliasNbPages();

// 1. Register Regular Font
$pdf->AddFont('THSarabunNew', '', 'THSarabunNew.json');

// 2. Register Bold Font (Point to your newly generated bold JSON file)
$pdf->AddFont('THSarabunNew', 'B', 'THSarabunNew_Bold.json');

$pdf->AddPage();

// --- Print Regular Text ---
$pdf->SetFont('THSarabunNew', '', 16);
$thai_text = iconv('UTF-8', 'cp874', 'สวัสดีชาวโลก ผ่านฟอนต์แบบ JSON! (ตัวปกติ)');
$pdf->Cell(0, 10, $thai_text, 0, 1);

// --- Print Bold Text ---
// Pass 'B' to change the style to Bold
$pdf->SetFont('THSarabunNew', 'B', 25);
$thai_text_bold = iconv('UTF-8', 'cp874', 'สวัสดีชาวโลก ผ่านฟอนต์แบบ JSON! พื้นฐาน สุขสันต์ (ตัวหนา)');
$pdf->Cell(0, 10, $thai_text_bold, 0, 1);

$pdf->Output();
