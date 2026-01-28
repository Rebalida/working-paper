<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Auth.php';

Auth::requireAdmin();

$file = $_GET['file'] ?? '';

if (empty($file)) {
    die('No file specified');
}

// Sanitize filename
$file = basename($file);
$filepath = __DIR__ . '/uploads/' . $file;

if (!file_exists($filepath)) {
    die('File not found');
}

// Get file extension
$extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

// Set content type
$contentTypes = [
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png'
];

$contentType = $contentTypes[$extension] ?? 'application/octet-stream';

header('Content-Type: ' . $contentType);
header('Content-Disposition: inline; filename="' . $file . '"');
header('Content-Length: ' . filesize($filepath));

readfile($filepath);