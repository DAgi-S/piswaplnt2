<?php
// Backup Configuration Settings
$BACKUP_PATH = 'backups/';
$BACKUP_FREQUENCY = 24; // hours
$KEEP_BACKUPS = 7; // number of backups to keep

// Ensure backup directory exists
if (!file_exists($BACKUP_PATH)) {
    mkdir($BACKUP_PATH, 0755, true);
}
?> 