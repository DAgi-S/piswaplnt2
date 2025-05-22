<?php
require_once 'core.php';

$sql = "CREATE TABLE IF NOT EXISTS guest_account_links (
    id INT PRIMARY KEY AUTO_INCREMENT,
    guest_id INT,
    account_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (guest_id) REFERENCES guest_users(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_guest_account (guest_id, account_id)
)";

if ($connect->query($sql)) {
    echo "Guest account links table created successfully";
} else {
    echo "Error creating table: " . $connect->error;
} 