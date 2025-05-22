<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once __DIR__ . '/../php_action/db_connect.php';
require_once __DIR__ . '/../php_action/functions.php';

// Function to check if user has permission
function has_permission($permission_name) {
    // Simpler signature - only takes permission name
    // Uses session directly
    // Handles unauthorized redirects
    // Uses a different database query structure
    global $connect;
    
    if (!isset($_SESSION['userId'])) {
        return false;
    }

    $sql = "SELECT 1
            FROM users u
            JOIN user_roles r ON u.role_id = r.role_id
            JOIN role_permissions rp ON r.role_id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.permission_id
            WHERE u.user_id = ? AND p.permission_name = ?
            LIMIT 1";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param('is', $_SESSION['userId'], $permission_name);
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0;
}

// Function to check multiple permissions (any)
function has_any_permission($permissions) {
    foreach ($permissions as $permission) {
        if (has_permission($permission)) {
            return true;
        }
    }
    return false;
}

// Function to check multiple permissions (all)
function has_all_permissions($permissions) {
    foreach ($permissions as $permission) {
        if (!has_permission($permission)) {
            return false;
        }
    }
    return true;
}

// Function to handle unauthorized access
function handle_unauthorized_access($message = '') {
    // Log the unauthorized access attempt
    $user_id = isset($_SESSION['userId']) ? $_SESSION['userId'] : 'Guest';
    $page = $_SERVER['REQUEST_URI'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $timestamp = date('Y-m-d H:i:s');
    
    error_log("Unauthorized access attempt - User: $user_id, Page: $page, IP: $ip, Time: $timestamp");
    
    // Set error message in session if provided
    if (!empty($message)) {
        $_SESSION['error'] = $message;
    }
    
    // Redirect to access denied page
    header('Location: ' . ROOT_URL . 'access_denied.php');
    exit();
}

function check_permission($required_permission) {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error_message'] = "Please log in to access this feature.";
        header("Location: login.php");
        exit();
    }

    // Get user permissions
    $user_permissions = get_user_permissions($_SESSION['user_id']);

    // Check if user has the required permission
    if (!in_array($required_permission, $user_permissions)) {
        $_SESSION['error_message'] = "Access denied. You need the '{$required_permission}' permission to access this feature. Please contact your administrator if you believe this is an error.";
        header("Location: error.php");
        exit();
    }

    return true;
}

function get_user_permissions($user_id) {
    global $conn;
    
    // First check if permissions are cached in session
    if (isset($_SESSION['user_permissions'])) {
        return $_SESSION['user_permissions'];
    }
    
    // Query to get user's role and direct permissions
    $permissions = array();
    
    // Get permissions from user's role
    $role_query = "SELECT p.permission_name 
                   FROM permissions p 
                   INNER JOIN role_permissions rp ON p.id = rp.permission_id 
                   INNER JOIN user_roles ur ON rp.role_id = ur.role_id 
                   WHERE ur.user_id = ?";
    
    $stmt = $conn->prepare($role_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $permissions[] = $row['permission_name'];
    }
    
    // Get direct user permissions
    $user_query = "SELECT p.permission_name 
                   FROM permissions p 
                   INNER JOIN user_permissions up ON p.id = up.permission_id 
                   WHERE up.user_id = ?";
    
    $stmt = $conn->prepare($user_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        if (!in_array($row['permission_name'], $permissions)) {
            $permissions[] = $row['permission_name'];
        }
    }
    
    // Cache permissions in session
    $_SESSION['user_permissions'] = $permissions;
    
    return $permissions;
} 