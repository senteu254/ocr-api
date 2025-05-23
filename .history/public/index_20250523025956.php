<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$tmp = $_FILES['file']['tmp_name'];
$mime = mime_content_type($tmp);
$tempPng = '';
$text = '';

// Handle PDF: convert first page to image
if ($mime === 'application/pdf') {
    $tempPng = tempnam(sys_get_temp_dir(), 'ocr_') . '.png';

    $cmd = "convert -density 600 " . escapeshellarg($tmp) . " -depth 8 -background white -alpha off -resize 2480x3508 " . escapeshellarg($tempPng);

    exec($cmd, $output, $returnCode);

    if (!file_exists($tempPng) || $returnCode !== 0) {
        echo json_encode(['error' => 'Failed to convert PDF to image.']);
        exit;
    }

    $fileToOcr = $tempPng;
} else {
    // It's already an image
    $fileToOcr = $tmp;
}

// Run Tesseract OCR
$text = shell_exec("tesseract " . escapeshellarg($fileToOcr) . " stdout 2>&1");

// Clean up
if ($tempPng && file_exists($tempPng)) {
    unlink($tempPng);
}

echo json_encode(['text' => trim($text)]);
