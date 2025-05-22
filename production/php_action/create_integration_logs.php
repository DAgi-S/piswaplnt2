<?php
require_once 'core.php';

function createIntegrationLogsTable() {
    global $connect;
    
    // Create system_integration_logs table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS system_integration_logs (
        id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11),
        module VARCHAR(100),
        endpoint VARCHAR(255),
        request_method VARCHAR(10),
        ip_address VARCHAR(45),
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    )";
    
    if ($connect->query($sql)) {
        echo "system_integration_logs table created or already exists.\n";
    } else {
        echo "Error creating table: " . $connect->error . "\n";
    }
}

// Run the table creation
createIntegrationLogsTable();
?> 