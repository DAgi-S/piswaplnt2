<?php
require_once '../core.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit('Unauthorized access');
}

// Check if file parameter is provided
if (!isset($_GET['file'])) {
    header('HTTP/1.1 400 Bad Request');
    exit('No file specified');
}

$file = $_GET['file'];
$backupDir = '../backups/';

// Validate file path to prevent directory traversal
$realPath = realpath($backupDir . $file);
if ($realPath === false || strpos($realPath, realpath($backupDir)) !== 0) {
    header('HTTP/1.1 403 Forbidden');
    exit('Invalid file path');
}

// Check if file exists
if (!file_exists($realPath)) {
    header('HTTP/1.1 404 Not Found');
    exit('File not found');
}

// Get file info
$fileInfo = pathinfo($realPath);
$fileName = $fileInfo['basename'];

// Set headers for file download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . filesize($realPath));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Output file
readfile($realPath);
exit(); 