<?php
require 'vendor/autoload.php';
include 'db_connect.php';

function runOCR($path) {
    $ch = curl_init();
    $cFile = new CURLFile(realpath($path), mime_content_type($path));
    curl_setopt($ch, CURLOPT_URL, 'https://ocr-api-129q.onrender.com');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => $cFile]);
    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);
    return $data['text'] ?? '';
}

// Get the next pending file
$res = $conn->query("SELECT * FROM ocr_shipments WHERE status='pending' ORDER BY id ASC LIMIT 1");

if ($row = $res->fetch_assoc()) {
    $id = $row['id'];
    $file = 'uploads/' . $row['filename'];

    if (!file_exists($file)) {
        echo "❌ File not found: $file\n";
        $stmt = $conn->prepare("UPDATE ocr_shipments SET status='failed', ocr_log=? WHERE id=?");
        $msg = "File not found";
        $stmt->bind_param("si", $msg, $id);
        $stmt->execute();
        exit;
    }

    $text = runOCR($file);

    if (trim($text)) {
        $stmt = $conn->prepare("UPDATE ocr_shipments SET ocr_raw_text=?, status='completed', updated_at=NOW() WHERE id=?");
        $stmt->bind_param("si", $text, $id);
        $stmt->execute();
        echo "✅ OCR complete for ID $id\n";
    } else {
        $error = 'OCR returned empty.';
        $stmt = $conn->prepare("UPDATE ocr_shipments SET status='failed', ocr_log=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param("si", $error, $id);
        $stmt->execute();
        echo "❌ OCR failed for ID $id\n";
    }
} else {
    echo "⏳ No pending files\n";
}
?>
