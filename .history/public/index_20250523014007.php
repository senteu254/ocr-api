<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$tmp = $_FILES['file']['tmp_name'];
$mime = mime_content_type($tmp);
$ocrApiKey = getenv('OCR_API_KEY');
$tempPng = '';

if ($mime === 'application/pdf') {
    // Convert first page of PDF to high-resolution PNG
    $tempPng = tempnam(sys_get_temp_dir(), 'ocr_') . '.png';
    $cmd = "convert -density 300 " . escapeshellarg($tmp) . "[0] -depth 8 -background white -alpha off " . escapeshellarg($tempPng);
    exec($cmd);
    file_put_contents('/tmp/ocr_debug.txt', "Running convert command:\n$cmd\n", FILE_APPEND);


    if (!file_exists($tempPng)) {
        echo json_encode(['error' => 'PDF conversion failed']);
        exit;
    }

    $fileToSend = $tempPng;
    $mime = 'image/png';
} else {
    $fileToSend = $tmp;
}

// Send to OCR.Space
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.ocr.space/parse/image');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'apikey' => $ocrApiKey,
    'language' => 'eng',
    'scale' => true,
    'OCREngine' => 2,
    'file' => new CURLFile(realpath($fileToSend), $mime)
]);

$response = curl_exec($ch);
curl_close($ch);

if ($tempPng && file_exists($tempPng)) unlink($tempPng);

$data = json_decode($response, true);
$text = $data['ParsedResults'][0]['ParsedText'] ?? '';

echo json_encode(['text' => $text]);

