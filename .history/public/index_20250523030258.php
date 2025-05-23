<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$tmp = $_FILES['file']['tmp_name'];
$mime = mime_content_type($tmp);
$text = '';
$tempFiles = [];

function cleanUp($files) {
    foreach ($files as $file) {
        if (file_exists($file)) unlink($file);
    }
}

if ($mime === 'application/pdf') {
    $outputPrefix = sys_get_temp_dir() . '/page';
    $cmd = "convert -density 300 " . escapeshellarg($tmp) . " -depth 8 -background white -alpha off -resize 2480x3508 " . escapeshellarg($outputPrefix) . "-%03d.png";
    exec($cmd, $output, $code);

    $pageFiles = glob($outputPrefix . "-*.png");
    if (!$pageFiles) {
        echo json_encode(['error' => 'PDF conversion failed.']);
        exit;
    }

    foreach ($pageFiles as $img) {
        $tempFiles[] = $img;
        $pageText = shell_exec("tesseract " . escapeshellarg($img) . " stdout 2>&1");
        $text .= $pageText . "\n";
    }
} else {
    $text = shell_exec("tesseract " . escapeshellarg($tmp) . " stdout 2>&1");
}

cleanUp($tempFiles);
echo json_encode(['text' => trim($text)]);
?>