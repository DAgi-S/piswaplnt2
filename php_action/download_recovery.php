<?php
require_once 'core.php';
require_once 'classes/ConfigurationManager.php';

// Check permissions
if (!isset($_SESSION['userId']) || !isset($_SESSION['role_id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied');
}

// Validate timestamp parameter
if (!isset($_GET['timestamp']) || !preg_match('/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}$/', $_GET['timestamp'])) {
    header('HTTP/1.1 400 Bad Request');
    exit('Invalid timestamp format');
}

$config = ConfigurationManager::getInstance();
$backupPath = $config->get('backup_path') . '/encryption_keys';
$timestamp = $_GET['timestamp'];
$recoveryFile = $backupPath . '/recovery_info_' . $timestamp . '.txt';

// Check if file exists and is readable
if (!file_exists($recoveryFile) || !is_readable($recoveryFile)) {
    header('HTTP/1.1 404 Not Found');
    exit('Recovery information not found');
}

// Set headers for file download
header('Content-Type: text/plain');
header('Content-Disposition: attachment; filename="recovery_info_' . $timestamp . '.txt"');
header('Content-Length: ' . filesize($recoveryFile));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Output file contents
readfile($recoveryFile);
exit(); 