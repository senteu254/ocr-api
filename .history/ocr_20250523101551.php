<?php
header('Content-Type: application/json');

if (!isset($_FILES['file'])) {
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$tmp = $_FILES['file']['tmp_name'];
$tmpBase = tempnam(sys_get_temp_dir(), 'img_');
unlink($tmpBase); // we just need a unique base
$imageBase = $tmpBase . '-page';
$outputText = '';

$convertCmd = "convert -density 300 '$tmp' '{$imageBase}-%d.png'";
exec($convertCmd, $convertOut, $convertStatus);

if ($convertStatus !== 0) {
    echo json_encode(['error' => 'convert failed', 'details' => $convertOut]);
    exit;
}

// Loop through each image and run Tesseract
foreach (glob("{$imageBase}-*.png") as $imageFile) {
    $ocrOutput = tempnam(sys_get_temp_dir(), 'ocr_');
    exec("tesseract '$imageFile' '$ocrOutput'", $tessOut, $tessStatus);

    if ($tessStatus === 0) {
        $outputText .= file_get_contents($ocrOutput . '.txt') . "\n";
        unlink($ocrOutput . '.txt');
    }

    unlink($imageFile);
}

if (trim($outputText) === '') {
    echo json_encode(['error' => 'Tesseract failed or returned no text']);
} else {
    echo json_encode(['text' => $outputText]);
}
