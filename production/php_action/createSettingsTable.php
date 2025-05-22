<?php
require_once 'db_connect.php';

// Create settings table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    value TEXT NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($connect->query($create_table_sql)) {
    echo "Settings table created successfully!\n";
} else {
    echo "Error creating settings table: " . $connect->error . "\n";
    exit;
}

// Insert default threshold buffer setting if it doesn't exist
$check_sql = "SELECT * FROM settings WHERE setting_key = 'threshold_buffer'";
$result = $connect->query($check_sql);

if ($result->num_rows == 0) {
    $insert_sql = "INSERT INTO settings (setting_key, value, description) VALUES 
        ('threshold_buffer', '20', 'Buffer percentage above minimum stock level for alerts'),
        ('notification_frequency', 'daily', 'How often to send low stock notifications'),
        ('email_notifications', 'true', 'Whether to send email notifications for low stock'),
        ('telegram_notifications', 'true', 'Whether to send Telegram notifications for low stock')";
    
    if ($connect->query($insert_sql)) {
        echo "Default settings inserted successfully!\n";
    } else {
        echo "Error inserting default settings: " . $connect->error . "\n";
    }
} else {
    echo "Settings already exist.\n";
}

echo "Setup completed!\n";
?> 