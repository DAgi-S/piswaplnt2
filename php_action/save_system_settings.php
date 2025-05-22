<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Required files
require_once 'db_connect.php';
require_once 'core.php';

// Basic security check
if (!isset($_SESSION['userId'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $category = $_POST['category'] ?? '';
    $settings = $_POST['settings'] ?? [];

    if (empty($category) || empty($settings)) {
        throw new Exception('Missing required parameters');
    }

    // Get category ID
    $categoryQuery = "SELECT category_id FROM system_config_categories WHERE category_name = ?";
    $stmt = $connect->prepare($categoryQuery);
    if (!$stmt) {
        throw new Exception("Database error: " . $connect->error);
    }
    
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result || $result->num_rows === 0) {
        // Create category if it doesn't exist
        $createCategoryQuery = "INSERT INTO system_config_categories (category_name, description) VALUES (?, ?)";
        $stmt = $connect->prepare($createCategoryQuery);
        $description = $category . " configuration settings";
        $stmt->bind_param("ss", $category, $description);
        $stmt->execute();
        $categoryId = $connect->insert_id;
    } else {
        $row = $result->fetch_assoc();
        $categoryId = $row['category_id'];
    }

    // Begin transaction
    $connect->begin_transaction();

    try {
        // Prepare update/insert statement
        $query = "INSERT INTO system_config_settings 
                 (category_id, setting_key, setting_value, data_type, updated_at) 
                 VALUES (?, ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE 
                 setting_value = VALUES(setting_value),
                 updated_at = NOW()";
        
        $stmt = $connect->prepare($query);
        
        // Update each setting
        foreach ($settings as $key => $value) {
            $dataType = ($key === 'db_password') ? 'password' : 'string';
            $stmt->bind_param("isss", $categoryId, $key, $value, $dataType);
            $stmt->execute();

            // Log the change
            $historyQuery = "INSERT INTO system_configuration_history 
                           (config_key, old_value, new_value, changed_by, created_at) 
                           VALUES (?, ?, ?, ?, NOW())";
            $historyStmt = $connect->prepare($historyQuery);
            $oldValue = ''; // You might want to fetch the old value before updating
            $userId = $_SESSION['userId'];
            $historyStmt->bind_param("sssi", $key, $oldValue, $value, $userId);
            $historyStmt->execute();
        }

        // Commit transaction
        $connect->commit();

        echo json_encode([
            'success' => true,
            'message' => $category . ' configuration saved successfully'
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $connect->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error in save_system_settings.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 