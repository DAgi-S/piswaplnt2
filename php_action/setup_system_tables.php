<?php
require_once 'db_connect.php';
require_once 'core.php';

try {
    // Create system_config_categories table if it doesn't exist
    $createCategoriesTable = "CREATE TABLE IF NOT EXISTS system_config_categories (
        category_id INT PRIMARY KEY AUTO_INCREMENT,
        category_name VARCHAR(50) NOT NULL,
        description TEXT,
        display_order INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_category_name (category_name)
    )";
    
    if ($connect->query($createCategoriesTable)) {
        echo "Categories table created or already exists\n";
    } else {
        throw new Exception("Error creating categories table: " . $connect->error);
    }

    // Create system_config_settings table if it doesn't exist
    $createSettingsTable = "CREATE TABLE IF NOT EXISTS system_config_settings (
        setting_id INT PRIMARY KEY AUTO_INCREMENT,
        category_id INT NOT NULL,
        setting_key VARCHAR(100) NOT NULL,
        setting_value TEXT,
        data_type VARCHAR(20) DEFAULT 'string',
        display_name VARCHAR(100),
        description TEXT,
        validation_rules VARCHAR(255),
        is_encrypted TINYINT(1) DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        default_value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_by INT,
        updated_by INT,
        UNIQUE KEY unique_setting_key (setting_key),
        FOREIGN KEY (category_id) REFERENCES system_config_categories(category_id)
    )";
    
    if ($connect->query($createSettingsTable)) {
        echo "Settings table created or already exists\n";
    } else {
        throw new Exception("Error creating settings table: " . $connect->error);
    }

    // Create system_configuration_history table if it doesn't exist
    $createHistoryTable = "CREATE TABLE IF NOT EXISTS system_configuration_history (
        history_id INT PRIMARY KEY AUTO_INCREMENT,
        config_key VARCHAR(100) NOT NULL,
        old_value TEXT,
        new_value TEXT,
        changed_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (changed_by) REFERENCES users(id)
    )";
    
    if ($connect->query($createHistoryTable)) {
        echo "History table created or already exists\n";
    } else {
        throw new Exception("Error creating history table: " . $connect->error);
    }

    echo json_encode([
        'success' => true,
        'message' => 'System configuration tables created successfully'
    ]);

} catch (Exception $e) {
    error_log("Error setting up system tables: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error setting up system tables: ' . $e->getMessage()
    ]);
} 