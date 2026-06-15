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

// 🌟 ✅ แก้ไขจุดที่ 1: เปิดใช้งานและนำเข้าไฟล์หลักของ TCPDF ให้ถูกต้อง
// สมมติว่าโฟลเดอร์ tcpdf อยู่ที่เดียวกับไฟล์นี้ หรือจัดการ Path ตามโครงสร้างของคุณ
// require_once('tcpdf' . DS . 'tcpdf.php');

// 2. สร้าง Instance ของ TCPDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// 3. เริ่มตั้งค่าและเขียนเอกสาร
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetTitle('ทดสอบ TCPDF ภาษาไทย');
$pdf->SetAuthor('APKS');

// ปิดการแสดงผล Header และ Footer เพื่อความคลีน (เหมือนตัวอย่าง FPDF เดิม)
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// เพิ่มหน้าใหม่
$pdf->AddPage();

// --- พิมพ์ข้อความตัวปกติ (Regular) ---
// ใช้ฟอนต์ 'thsarabun' ที่ลงทะเบียนไว้ในระบบ TCPDF แล้ว
$pdf->SetFont('thsarabun', '', 16); 
$pdf->Cell(0, 10, 'สวัสดีชาวโลก ผ่านฟอนต์ TCPDF (ตัวปกติ): สระไม่ลอย วรรณยุกต์ไม่จมแน่นอน!', 0, 1);

// --- พิมพ์ข้อความตัวหนา (Bold) ---
// การระบุสไตล์ 'B' จะทำการโหลดฟอนต์ 'thsarabunb' ที่เราสร้างขึ้นโดยอัตโนมัติ
$pdf->SetFont('thsarabun', 'B', 22);
$pdf->Cell(0, 12, 'สวัสดีชาวโลก ผ่านฟอนต์ TCPDF (ตัวหนา): พื้นฐาน สุขสันต์ กล้วยน้ำว้า', 0, 1);

// --- การพ่นข้อความแบบ HTML (แนะนำสำหรับภาษาไทยเนื่องจากจัดช่องไฟได้สวยงามที่สุด) ---
$pdf->SetFont('thsarabun', '', 16);
$html_text = '
<h3>ตัวอย่างการแสดงผลภาษาไทยด้วย writeHTML:</h3>
<p>คำทดสอบวรรณยุกต์และสระลอย: <b>"ที่" "ซึ่ง" "อัน" "น้ำ" "กล้วยน้ำว้า" "สุขสันต์"</b></p>
<p style="color: #2c3e50;">การใช้ <i>writeHTML</i> จะช่วยปรับช่องไฟและตัวสะกดภาษาไทยให้มีความละเอียดและสมบูรณ์ที่สุดครับ</p>
';
$pdf->writeHTML($html_text, true, false, true, false, '');

// 4. ส่งออกไฟล์ PDF ไปยังบราวเซอร์ (แสดงผลทันทีแบบ Inline)
$pdf->Output('test_thai.pdf', 'I');
?>