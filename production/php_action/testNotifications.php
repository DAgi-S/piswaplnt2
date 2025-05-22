<?php
require_once 'db_connect.php';
require_once 'classes/NotificationHandler.php';

header('Content-Type: text/plain');

try {
    // Create notification_logs table if it doesn't exist
    $create_table_sql = "CREATE TABLE IF NOT EXISTS notification_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_id INT NOT NULL,
        message TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    
    if (!$connect->query($create_table_sql)) {
        throw new Exception("Error creating notification_logs table: " . $connect->error);
    }

    // Test notifications
    $notificationHandler = new NotificationHandler($connect);
    $result = $notificationHandler->testNotifications();
    
    echo "Test completed successfully!\n";
    echo "You should receive:\n";
    echo "1. An email at your configured email address\n";
    echo "2. A Telegram message in your bot chat\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 