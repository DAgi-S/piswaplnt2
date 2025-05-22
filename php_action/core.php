<?php
// Only start session if one isn't already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration file
require_once 'config.php';

// Add error reporting after session start but don't display errors
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

require_once 'db_connect.php';
require_once 'middleware.php';

// Function to update session information in database
function updateSessionInfo() {
    global $connect;
    
    if (!isset($_SESSION['userId'])) {
        return; // Don't track sessions for non-logged-in users
    }
    
    $session_id = session_id();
    $user_id = $_SESSION['userId'];
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    // Use REPLACE INTO to either insert new or update existing session
    $sql = "REPLACE INTO sessions (session_id, user_id, ip_address, user_agent, last_activity) 
            VALUES (?, ?, ?, ?, NOW())";
            
    try {
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("siss", $session_id, $user_id, $ip_address, $user_agent);
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Failed to update session info: " . $e->getMessage());
    }
}

// Update session info if user is logged in
if (isset($_SESSION['userId'])) {
    updateSessionInfo();
}

// Debug session
error_log('Session in core.php: ' . print_r($_SESSION, true));

// Check if request is for API
$is_api_request = strpos($_SERVER['REQUEST_URI'], '/api/') !== false;

// Check if user is logged in
$current_page = basename($_SERVER['PHP_SELF']);
$public_pages = array('index.php', 'login.php', 'forgot-password.php');

if (!isset($_SESSION['userId']) && !in_array($current_page, $public_pages)) {
    if ($is_api_request) {
        // For API requests, return JSON error
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    } else {
        // For web requests, redirect to login
        header('location: ../index.php');
        exit();
    }
}

function executeQuery($sql, $params = [], $types = '') {
    global $connect;
    
    try {
        $stmt = $connect->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        return $stmt->get_result();
    } catch (Exception $e) {
        error_log("Query Error: " . $e->getMessage());
        return false;
    }
}

// After database connection
if ($connect->connect_error) {
    error_log("Database connection failed: " . $connect->connect_error);
    if ($is_api_request) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit();
    } else {
        die("Connection failed: " . $connect->connect_error);
    }
}

?>