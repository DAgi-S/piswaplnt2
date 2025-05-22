<?php
require_once 'db_connect.php';

$sql = "CREATE TABLE IF NOT EXISTS notification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES production_products(id)
)";

if ($connect->query($sql)) {
    echo "Notification logs table created successfully!";
} else {
    echo "Error creating notification logs table: " . $connect->error;
}
?> 