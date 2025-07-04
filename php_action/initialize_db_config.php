<?php
require_once 'db_connect.php';
require_once 'core.php';

try {
    // Check if database category exists
    $categoryQuery = "SELECT category_id FROM system_config_categories WHERE category_name = 'Database'";
    $result = $connect->query($categoryQuery);
    
    if ($result->num_rows === 0) {
        // Create database category
        $createCategoryQuery = "INSERT INTO system_config_categories (category_name, description, display_order) VALUES ('Database', 'Database configuration settings', 2)";
        $connect->query($createCategoryQuery);
        $categoryId = $connect->insert_id;
    } else {
        $row = $result->fetch_assoc();
        $categoryId = $row['category_id'];
    }

    // Define database settings
    $settings = [
        'db_host' => [
            'value' => 'localhost',
            'type' => 'string',
            'display_name' => 'Database Host',
            'description' => 'Database server address',
            'validation' => 'required'
        ],
        'db_name' => [
            'value' => 'pistocklnt1march',
            'type' => 'string',
            'display_name' => 'Database Name',
            'description' => 'Database name',
            'validation' => 'required'
        ],
        'db_user' => [
            'value' => 'root',
            'type' => 'string',
            'display_name' => 'Username',
            'description' => 'Database username',
            'validation' => 'required'
        ],
        'db_password' => [
            'value' => '',
            'type' => 'password',
            'display_name' => 'Password',
            'description' => 'Database password',
            'validation' => ''
        ]
    ];

    // Insert or update settings
    $query = "INSERT INTO system_config_settings 
              (category_id, setting_key, setting_value, data_type, display_name, description, validation_rules, created_at) 
              VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
              ON DUPLICATE KEY UPDATE 
              setting_value = VALUES(setting_value),
              data_type = VALUES(data_type),
              display_name = VALUES(display_name),
              description = VALUES(description),
              validation_rules = VALUES(validation_rules),
              updated_at = NOW()";

    $stmt = $connect->prepare($query);

    foreach ($settings as $key => $setting) {
        $stmt->bind_param("issssss", 
            $categoryId,
            $key,
            $setting['value'],
            $setting['type'],
            $setting['display_name'],
            $setting['description'],
            $setting['validation']
        );
        $stmt->execute();
    }

    echo json_encode([
        'success' => true,
        'message' => 'Database configuration initialized successfully'
    ]);

} catch (Exception $e) {
    error_log("Error initializing database configuration: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error initializing database configuration: ' . $e->getMessage()
    ]);
} 