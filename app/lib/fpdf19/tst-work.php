<?php
require('fpdf.php'); 

$pdf = new FPDF();
$pdf->AddPage();

// 1. Register Regular Font
$pdf->AddFont('THSarabunNew', '', 'THSarabunNew.json');

// 2. Register Bold Font (Point to your newly generated bold JSON file)
$pdf->AddFont('THSarabunNew', 'B', 'THSarabunNew_Bold.json'); 

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
?>