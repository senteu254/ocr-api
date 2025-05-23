<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$tmpFile = $_FILES['file']['tmp_name'];
$imgFile = '/tmp/ocr_image.png';

// Convert PDF to image
exec("convert -density 300 '$tmpFile[0]' '$imgFile' 2>&1", $out1, $ret1);
if ($ret1 !== 0) {
    http_response_code(500);
    echo json_encode(['error' => 'PDF to image conversion failed', 'details' => $out1]);
    exit;
}

// Run Tesseract
$outputFile = tempnam(sys_get_temp_dir(), 'ocr_');
exec("tesseract '$imgFile' '$outputFile' 2>&1", $out2, $ret2);
if ($ret2 !== 0) {
    http_response_code(500);
    echo json_encode(['error' => 'Tesseract OCR failed', 'details' => $out2]);
    exit;
}

$text = file_get_contents($outputFile . '.txt');

// Cleanup
@unlink($imgFile);
@unlink($outputFile . '.txt');

echo json_encode(['text' => $text]);
?>
