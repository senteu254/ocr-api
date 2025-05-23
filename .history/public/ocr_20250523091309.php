<?php
header('Content-Type: application/json');

if (!isset($_FILES['file'])) {
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$tmp = $_FILES['file']['tmp_name'];
$image = tempnam(sys_get_temp_dir(), 'img') . '.png';
$output = tempnam(sys_get_temp_dir(), 'ocr');

// Convert PDF to image
exec("convert -density 300 '$tmp' '$image'", $out1, $ret1);
if ($ret1 !== 0) {
    echo json_encode(['error' => 'convert failed', 'details' => $out1]);
    exit;
}

// Extract text with Tesseract
exec("tesseract '$image' '$output'", $out2, $ret2);
if ($ret2 !== 0) {
    echo json_encode(['error' => 'tesseract failed', 'details' => $out2]);
    exit;
}

$text = file_get_contents($output . '.txt');

unlink($image);
unlink($output . '.txt');

echo json_encode(['text' => $text ?: '[empty output]']);
