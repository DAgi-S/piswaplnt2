<?php
require_once 'core.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Create notification_channels table
    $sql = "CREATE TABLE IF NOT EXISTS notification_channels (
        channel_id INT PRIMARY KEY AUTO_INCREMENT,
        type VARCHAR(50) NOT NULL,
        name VARCHAR(100) NOT NULL,
        config JSON,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_type (type)
    )";
    
    if (!$connect->query($sql)) {
        throw new Exception("Error creating notification_channels table: " . $connect->error);
    }

    // Create user_notification_preferences table
    $sql = "CREATE TABLE IF NOT EXISTS user_notification_preferences (
        preference_id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        notification_type VARCHAR(50) NOT NULL,
        channel_id INT NULL,
        is_enabled TINYINT(1) DEFAULT 1,
        config JSON NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_pref (user_id, notification_type, channel_id),
        FOREIGN KEY (channel_id) REFERENCES notification_channels(channel_id) ON DELETE SET NULL
    )";
    
    if (!$connect->query($sql)) {
        throw new Exception("Error creating user_notification_preferences table: " . $connect->error);
    }

    // Insert default channels if they don't exist
    $defaultChannels = [
        ['email', 'Email Notifications', '{"smtp_enabled": true}'],
        ['browser', 'Browser Notifications', '{"enabled": true}'],
        ['sms', 'SMS Notifications', '{"enabled": false}']
    ];

    $sql = "INSERT IGNORE INTO notification_channels (type, name, config) VALUES (?, ?, ?)";
    $stmt = $connect->prepare($sql);
    
    foreach ($defaultChannels as $channel) {
        $stmt->bind_param("sss", $channel[0], $channel[1], $channel[2]);
        if (!$stmt->execute()) {
            throw new Exception("Error inserting default channel {$channel[0]}: " . $stmt->error);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Notification tables created successfully'
    ]);

} catch (Exception $e) {
    error_log('Setup error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Setup failed: ' . $e->getMessage()
    ]);
} 