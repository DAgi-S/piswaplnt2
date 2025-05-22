<?php
// Include configuration file
require_once 'config.php';

// Enable error reporting for logging only
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Check if request is for API
$is_api_request = strpos($_SERVER['REQUEST_URI'], '/api/') !== false;

// Create connection with error handling
try {
    $connect = new mysqli($localhost, $username, $password, $dbname);
    
    // Check connection
    if ($connect->connect_error) {
        error_log("Database connection failed: " . $connect->connect_error);
        if ($is_api_request) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database connection failed']);
            exit();
        } else {
            throw new Exception("Database connection failed: " . $connect->connect_error);
        }
    }

    // Set charset
    if (!$connect->set_charset("utf8mb4")) {
        error_log("Error setting charset: " . $connect->error);
    }

    // Set timezone
    $connect->query("SET time_zone = '+00:00'");
    
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    if ($is_api_request) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit();
    } else {
        die("Database connection failed. Please try again later.");
    }
}

function validateLogin($username, $password) {
    global $connect;
    
    try {
        $sql = "SELECT u.*, ur.role_id, ur.role_name 
                FROM users u
                LEFT JOIN user_roles ur ON u.role_id = ur.role_id
                WHERE u.username = ? AND u.status = 1";
                
        $stmt = $connect->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed: " . $connect->error);
            return false;
        }
        
        $stmt->bind_param('s', $username);
        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            return false;
        }
        
        $result = $stmt->get_result();
        if($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if(password_verify($password, $user['password'])) {
                return $user;
            }
            error_log("Password verification failed for user: " . $username);
        }
        return false;
    } catch (Exception $e) {
        error_log("Login validation error: " . $e->getMessage());
        return false;
    }
}

// Function to check if table exists
function tableExists($tableName) {
    global $connect;
    $result = $connect->query("SHOW TABLES LIKE '" . $connect->real_escape_string($tableName) . "'");
    return $result->num_rows > 0;
}

// Verify required tables exist - only log errors, don't output
$requiredTables = ['users', 'user_roles', 'user_activity_log'];
foreach ($requiredTables as $table) {
    if (!tableExists($table)) {
        error_log("Required table missing: " . $table);
    }
}
?> 