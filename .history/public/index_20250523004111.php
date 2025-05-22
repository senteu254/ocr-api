<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$tmp = $_FILES['file']['tmp_name'];
$mime = mime_content_type($tmp);

$ocrApiKey = 'K85840573888957'; // Your OCR.Space API key

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.ocr.space/parse/image');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'apikey' => $ocrApiKey,
    'language' => 'eng',
    'scale' => true,
    'file' => new CURLFile(realpath($tmp), $mime)
]);

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
$text = $data['ParsedResults'][0]['ParsedText'] ?? '';

echo json_encode(['text' => $text]);
