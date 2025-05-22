<?php
require_once 'core.php';
require_once 'db_connect.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create stock_movement_approvals table
$sql = "CREATE TABLE IF NOT EXISTS stock_movement_approvals (
    id INT(11) NOT NULL AUTO_INCREMENT,
    movement_id INT(11) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    approver_id INT(11) DEFAULT NULL,
    approval_date DATETIME DEFAULT NULL,
    rejection_reason TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY movement_id (movement_id),
    KEY approver_id (approver_id),
    KEY status (status),
    CONSTRAINT fk_movement_approval FOREIGN KEY (movement_id) REFERENCES stock_movements (id) ON DELETE CASCADE,
    CONSTRAINT fk_approver FOREIGN KEY (approver_id) REFERENCES accounts (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($connect->query($sql) === TRUE) {
    echo "Table 'stock_movement_approvals' created successfully\n";
} else {
    echo "Error creating table: " . $connect->error . "\n";
}

// Create stock_movement_approval_logs table
$sql = "CREATE TABLE IF NOT EXISTS stock_movement_approval_logs (
    id INT(11) NOT NULL AUTO_INCREMENT,
    approval_id INT(11) NOT NULL,
    old_status ENUM('pending', 'approved', 'rejected') NOT NULL,
    new_status ENUM('pending', 'approved', 'rejected') NOT NULL,
    changed_by INT(11) NOT NULL,
    notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY approval_id (approval_id),
    KEY changed_by (changed_by),
    CONSTRAINT fk_approval_log FOREIGN KEY (approval_id) REFERENCES stock_movement_approvals (id) ON DELETE CASCADE,
    CONSTRAINT fk_approval_log_user FOREIGN KEY (changed_by) REFERENCES accounts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($connect->query($sql) === TRUE) {
    echo "Table 'stock_movement_approval_logs' created successfully\n";
} else {
    echo "Error creating table: " . $connect->error . "\n";
}

// Add approval_required column to stock_movements table if it doesn't exist
$sql = "SHOW COLUMNS FROM stock_movements LIKE 'approval_required'";
$result = $connect->query($sql);

if ($result->num_rows == 0) {
    $sql = "ALTER TABLE stock_movements 
            ADD COLUMN approval_required TINYINT(1) NOT NULL DEFAULT 0 AFTER notes,
            ADD COLUMN approval_status ENUM('pending', 'approved', 'rejected', 'not_required') 
            NOT NULL DEFAULT 'not_required' AFTER approval_required";
    
    if ($connect->query($sql) === TRUE) {
        echo "Added approval columns to stock_movements table successfully\n";
    } else {
        echo "Error adding approval columns: " . $connect->error . "\n";
    }
}

// Update CHANGELOG.md
$changelog_entry = "\n## [Version 1.0.24] - " . date('Y-m-d') . "\n\n" .
"### ✨ Features & Improvements\n" .
"- **Stock Movement Approval System**:\n" .
"  - Added stock_movement_approvals table for tracking approval status\n" .
"  - Added stock_movement_approval_logs table for audit trail\n" .
"  - Added approval_required and approval_status columns to stock_movements\n" .
"  - Enhanced stock movement workflow with approval process\n" .
"  - Added comprehensive logging for approval changes\n";

file_put_contents('../CHANGELOG.md', $changelog_entry . file_get_contents('../CHANGELOG.md'));

echo "CHANGELOG.md updated successfully\n"; 