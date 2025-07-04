<?php
// Scheduled Backup Script (for cron/Task Scheduler)
require_once '../core.php';
require_once 'classes/backup/BackupManager.php';
require_once 'classes/backup/BackupRetention.php';
require_once 'classes/backup/BackupEncryption.php';

// Connect to DB
$db = db_connect();

// Get all enabled schedules due to run now or earlier
$now = date('H:i:s');
$today = date('l'); // e.g., Monday
$datetime_now = date('Y-m-d H:i:s');

$sql = "SELECT * FROM system_backup_schedules WHERE enabled = 1 AND (
    (frequency = 'daily' AND time_of_day <= ?) OR
    (frequency = 'weekly' AND day_of_week = ? AND time_of_day <= ?) OR
    (frequency = 'custom' AND next_run <= ?)
)";
$stmt = $db->prepare($sql);
$stmt->bind_param('ssss', $now, $today, $now, $datetime_now);
$stmt->execute();
$schedules = $stmt->get_result();

while ($schedule = $schedules->fetch_assoc()) {
    // Run backup
    $type = $schedule['backup_type'];
    $result = BackupManager::runScheduledBackup($type, $schedule['id']);
    // Encrypt backup if enabled
    if ($result && isset($result['backup_file'])) {
        $encryption = new BackupEncryption($db);
        if ($encryption->isEnabled()) {
            $encryption->encryptFile($result['backup_file']);
        }
    }
    // Update last_run and next_run
    $update = $db->prepare("UPDATE system_backup_schedules SET last_run = NOW(), next_run = DATE_ADD(NOW(), INTERVAL 1 DAY) WHERE id = ?");
    $update->bind_param('i', $schedule['id']);
    $update->execute();
}

// Enforce retention policy after all backups
$retention = new BackupRetention($db);
$retention->enforce();

// Optionally log output
file_put_contents(__DIR__.'/scheduled_backup.log', date('Y-m-d H:i:s')." - Checked and ran scheduled backups\n", FILE_APPEND); 