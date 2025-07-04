<?php
require_once 'core.php';
header('Content-Type: application/json');

// Table for event/channel preferences
$table = 'system_backup_notification_settings';

// Create table if not exists
$createTableSql = "CREATE TABLE IF NOT EXISTS $table (
    id INT PRIMARY KEY AUTO_INCREMENT,
    notify_success TINYINT(1) DEFAULT 1,
    notify_failure TINYINT(1) DEFAULT 1,
    notify_verification TINYINT(1) DEFAULT 1,
    channels VARCHAR(255) DEFAULT 'email',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$connect->query($createTableSql);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Load settings
    $sql = "SELECT * FROM $table ORDER BY id DESC LIMIT 1";
    $result = $connect->query($sql);
    $settings = $result ? $result->fetch_assoc() : null;
    if ($settings) {
        $settings['channels'] = explode(',', $settings['channels']);
    }
    echo json_encode([
        'success' => true,
        'settings' => $settings
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notify_success = isset($_POST['notify_success']) ? 1 : 0;
    $notify_failure = isset($_POST['notify_failure']) ? 1 : 0;
    $notify_verification = isset($_POST['notify_verification']) ? 1 : 0;
    $channels = isset($_POST['channels']) ? implode(',', (array)$_POST['channels']) : 'email';

    // Upsert (insert or update latest row)
    $sql = "INSERT INTO $table (notify_success, notify_failure, notify_verification, channels) VALUES (?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE notify_success=VALUES(notify_success), notify_failure=VALUES(notify_failure), 
            notify_verification=VALUES(notify_verification), channels=VALUES(channels), updated_at=CURRENT_TIMESTAMP";
    // If table is empty, insert; else, update latest row
    $check = $connect->query("SELECT id FROM $table LIMIT 1");
    if ($check && $check->num_rows > 0) {
        $row = $check->fetch_assoc();
        $sql = "UPDATE $table SET notify_success=?, notify_failure=?, notify_verification=?, channels=?, updated_at=CURRENT_TIMESTAMP WHERE id=?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param('iiisi', $notify_success, $notify_failure, $notify_verification, $channels, $row['id']);
    } else {
        $sql = "INSERT INTO $table (notify_success, notify_failure, notify_verification, channels) VALUES (?, ?, ?, ?)";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param('iiisi', $notify_success, $notify_failure, $notify_verification, $channels, $row['id']);
    }
    $success = $stmt->execute();
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Settings saved.' : 'Failed to save settings.'
    ]);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid request method.']); 