<?php
require_once 'db_connect.php';

try {
    // Check if column exists
    $check_sql = "SHOW COLUMNS FROM users LIKE 'telegram_chat_id'";
    $result = $connect->query($check_sql);
    
    if ($result->num_rows == 0) {
        // Column doesn't exist, add it
        $sql = "ALTER TABLE users ADD COLUMN telegram_chat_id VARCHAR(50) DEFAULT NULL";
        if ($connect->query($sql)) {
            echo "Telegram chat ID column added successfully!\n";
        } else {
            throw new Exception("Error adding telegram_chat_id column: " . $connect->error);
        }
    } else {
        echo "Telegram chat ID column already exists.\n";
    }

    // Update your user record with the Telegram chat ID
    $update_sql = "UPDATE users SET telegram_chat_id = '317393086' WHERE username = 'admin'";
    if ($connect->query($update_sql)) {
        echo "Updated admin user with Telegram chat ID.\n";
    } else {
        throw new Exception("Error updating user: " . $connect->error);
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 