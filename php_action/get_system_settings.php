<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Required files
require_once '../php_action/db_connect.php';
require_once '../php_action/core.php';

// Basic security check
if (!isset($_SESSION['userId'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    // Get configuration settings based on category
    $category = $_GET['category'] ?? 'Email';
    
    $query = "SELECT s.setting_key, s.setting_value, s.data_type, s.display_name, 
                     s.description, s.validation_rules, s.default_value 
             FROM system_config_settings s
             INNER JOIN system_config_categories c ON s.category_id = c.category_id
             WHERE c.category_name = ?
             ORDER BY s.setting_id";

    $stmt = $connect->prepare($query);
    if (!$stmt) {
        throw new Exception("Database error: " . $connect->error);
    }
    
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) {
        throw new Exception("Database error: " . $connect->error);
    }

    $settings = [];
    while ($row = $result->fetch_assoc()) {
        // Don't send actual password values to client
        if (in_array($row['setting_key'], ['mail_password', 'db_password'])) {
            $row['setting_value'] = '';
        }
        $settings[$row['setting_key']] = [
            'value' => $row['setting_value'] ?? '',
            'type' => $row['data_type'] ?? 'text',
            'display_name' => $row['display_name'] ?? ucwords(str_replace('_', ' ', $row['setting_key'])),
            'description' => $row['description'] ?? '',
            'validation' => $row['validation_rules'] ?? '',
            'default' => $row['default_value'] ?? ''
        ];
    }

    // If no settings found, return defaults based on category
    if (empty($settings)) {
        switch ($category) {
            case 'Email':
                $settings = [
                    'mail_server' => ['value' => '', 'type' => 'string', 'display_name' => 'Mail Server', 'description' => 'SMTP server address', 'validation' => 'required', 'default' => 'smtp.gmail.com'],
                    'mail_port' => ['value' => '', 'type' => 'integer', 'display_name' => 'Mail Port', 'description' => 'SMTP server port', 'validation' => 'required|numeric', 'default' => '587'],
                    'mail_username' => ['value' => '', 'type' => 'email', 'display_name' => 'Mail Username', 'description' => 'SMTP account username', 'validation' => 'required|email', 'default' => ''],
                    'mail_password' => ['value' => '', 'type' => 'password', 'display_name' => 'Mail Password', 'description' => 'SMTP account password', 'validation' => 'required', 'default' => ''],
                    'mail_from' => ['value' => '', 'type' => 'email', 'display_name' => 'Default Sender', 'description' => 'Default sender email address', 'validation' => 'required|email', 'default' => '']
                ];
                break;
            case 'Database':
                $settings = [
                    'db_host' => ['value' => 'localhost', 'type' => 'string', 'display_name' => 'Database Host', 'description' => 'Database server address', 'validation' => 'required', 'default' => 'localhost'],
                    'db_name' => ['value' => 'pistocklntmarch', 'type' => 'string', 'display_name' => 'Database Name', 'description' => 'Database name', 'validation' => 'required', 'default' => 'pistocklntmarch'],
                    'db_user' => ['value' => '', 'type' => 'string', 'display_name' => 'Username', 'description' => 'Database username', 'validation' => 'required', 'default' => ''],
                    'db_password' => ['value' => '', 'type' => 'password', 'display_name' => 'Password', 'description' => 'Database password', 'validation' => '', 'default' => '']
                ];
                break;
        }
    }

    echo json_encode([
        'success' => true,
        'settings' => $settings
    ]);

} catch (Exception $e) {
    error_log("Error in get_system_settings.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 