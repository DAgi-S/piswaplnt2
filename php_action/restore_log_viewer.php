<?php
// Simple endpoint to view restore log
$backupPath = __DIR__ . '/../backups/encryption_keys';
$logFile = $backupPath . '/restore_log.txt';

header('Content-Type: text/plain');
if (file_exists($logFile)) {
    readfile($logFile);
} else {
    echo "No restore log found.";
} 