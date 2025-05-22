<?php
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
    $tempPng = tempnam(sys_get_temp_dir(), 'ocr_') . '.png';
    $cmd = "convert -density 300 " . escapeshellarg($tmp) . "[0] -depth 8 -background white -alpha off " . escapeshellarg($tempPng);
    exec($cmd);
    file_put_contents('/tmp/ocr_debug.txt', "Convert command:\n$cmd\n", FILE_APPEND);

    if (!file_exists($tempPng)) {
        file_put_contents('/tmp/ocr_debug.txt', "❌ PNG was not created.\n", FILE_APPEND);
        echo json_encode(['error' => 'PDF conversion failed']);
        exit;
    } else {
        file_put_contents('/tmp/ocr_debug.txt', "✅ PNG created: $tempPng\n", FILE_APPEND);
        header('Content-Type: image/png');
        readfile($tempPng);
        exit;
    }
} else {
    $fileToSend = $tmp;
}

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

$data = json_decode($response, true);
$text = $data['ParsedResults'][0]['ParsedText'] ?? '';
echo json_encode(['text' => $text]);
?>