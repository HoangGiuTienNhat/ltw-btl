<?php
// Simple image upload endpoint. Saves to public/pic/product/ and returns path.
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$uploadDir = __DIR__ . '/../pic/product/';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}

if (empty($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$file = $_FILES['image'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Upload error code '.$file['error']]);
    exit;
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$safeExt = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
$basename = bin2hex(random_bytes(8));
$targetName = $basename . ($safeExt ? '.' . $safeExt : '');
$targetPath = $uploadDir . $targetName;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to move uploaded file']);
    exit;
}

$relative = 'pic/product/' . $targetName;
echo json_encode(['success' => true, 'path' => $relative]);
