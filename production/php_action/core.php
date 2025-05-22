<?php
// Include configuration file
require_once 'config.php';

// Enable error reporting based on environment
if ($is_local) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', 'php_errors.log');
}

// Start output buffering to prevent whitespace issues
ob_start();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base paths
define('BASE_PATH', dirname(dirname(__FILE__)));
define('PRODUCTION_PATH', dirname(__FILE__));

// Include database connection
require_once PRODUCTION_PATH . '/db_connect.php';

// Set timezone
date_default_timezone_set('Asia/Manila');

// echo $_SESSION['userId'];

// Check if user is logged in
function isLoggedIn() {
    if(!isset($_SESSION['userId'])) {
        // Get the current script name
        $currentScript = basename($_SERVER['PHP_SELF']);
        
        // Log session data for debugging
        error_log("Session data in isLoggedIn: " . json_encode($_SESSION));
        
        // Don't redirect if already on login page or index
        if (!in_array($currentScript, ['login.php', 'index.php'])) {
            header('location: ' . $store_url . 'login.php');
            exit();
        }
        return false;
    }
    
    // Debug: log the current user ID
    error_log("Current user ID: " . $_SESSION['userId']);
    return true;
}

// Only check login status if not on login or index page
$currentScript = basename($_SERVER['PHP_SELF']);
if (!in_array($currentScript, ['login.php', 'index.php']) && !isset($_SESSION['userId'])) {
    header('location: ' . $store_url . 'login.php');
    exit();
}

// Get current user ID with fallbacks
function getCurrentUserId() {
    global $connect;
    
    // First try session userId
    if (isset($_SESSION['userId']) && !empty($_SESSION['userId'])) {
        return intval($_SESSION['userId']);
    }
    
    // Try alternative session variables
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        return intval($_SESSION['user_id']);
    }
    
    // Try to get by username if available
    if (isset($_SESSION['username']) && !empty($_SESSION['username'])) {
        try {
            $stmt = $connect->prepare("SELECT user_id FROM users WHERE username = ?");
            $stmt->execute([$_SESSION['username']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                return intval($result['user_id']);
            }
        } catch (Exception $e) {
            error_log("Error finding user by username: " . $e->getMessage());
        }
    }
    
    // Last resort: get an admin user
    try {
        $stmt = $connect->query("SELECT user_id FROM users WHERE role = 'admin' OR username = 'admin' LIMIT 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            return intval($result['user_id']);
        }
    } catch (Exception $e) {
        error_log("Error finding admin user: " . $e->getMessage());
    }
    
    // Absolute fallback to 1
    return 1;
}

/**
 * Check if the current user has a specific permission
 * @param string $permission The permission to check (e.g., 'purchase.view')
 * @return boolean True if user has permission, false otherwise
 */
function hasPermission($permission) {
    global $connect;
    
    if (!isset($_SESSION['userId'])) {
        error_log("No user ID in session");
        return false;
    }
    
    $userId = $_SESSION['userId'];
    
    // Debug log
    error_log("hasPermission: Checking permission '$permission' for user $userId");
    
    // For settings.php access, check for settings permissions
    if (basename($_SERVER['PHP_SELF']) === 'settings.php') {
        error_log("hasPermission: Checking settings.php access");
        
        $adminSql = "SELECT p.permission_name, p.module 
                    FROM users u
                    JOIN user_roles ur ON u.role_id = ur.role_id
                    JOIN role_permissions rp ON ur.role_id = rp.role_id
                    JOIN permissions p ON rp.permission_id = p.permission_id
                    WHERE u.user_id = ? 
                    AND (
                        p.permission_name IN ('settings.access', 'settings_access', 'settings.manage', 'system.settings.access')
                        OR p.module = 'Settings'
                    )
                    AND u.status = 1";
                    
        $adminStmt = $connect->prepare($adminSql);
        $adminStmt->bind_param("i", $userId);
        $adminStmt->execute();
        $adminResult = $adminStmt->get_result();
        $permissions = $adminResult->fetch_all(MYSQLI_ASSOC);
        $adminStmt->close();
        
        error_log("hasPermission: Found settings permissions: " . print_r($permissions, true));
        
        if (count($permissions) > 0) {
            error_log("hasPermission: User has settings access");
            return true;
        }
    }
    
    // Regular permission check
    $sql = "SELECT 1 
            FROM users u
            JOIN user_roles ur ON u.role_id = ur.role_id
            JOIN role_permissions rp ON ur.role_id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.permission_id
            WHERE u.user_id = ? 
            AND p.permission_name = ?
            AND u.status = 1";
            
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("is", $userId, $permission);
    $stmt->execute();
    $result = $stmt->get_result();
    $hasPermission = ($result->num_rows > 0);
    $stmt->close();
    
    error_log("hasPermission: Regular permission check result for '$permission': " . ($hasPermission ? 'true' : 'false'));
    
    return $hasPermission;
}

// Add debug function for permissions
function debugPermissions($userId) {
    global $connect;
    
    $sql = "SELECT p.permission_name, p.description
            FROM users u
            JOIN user_roles ur ON u.role_id = ur.role_id
            JOIN role_permissions rp ON ur.role_id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.permission_id
            WHERE u.user_id = ? 
            AND u.status = 1
            ORDER BY p.permission_name";
            
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $permissions = array();
    while ($row = $result->fetch_assoc()) {
        $permissions[] = $row;
    }
    
    error_log("User ID: " . $userId . " Permissions: " . json_encode($permissions));
    return $permissions;
}

/**
 * Logs a system action to activity_log and audit_log tables.
 * @param int $userId
 * @param string $actionType (e.g., CREATE, UPDATE, DELETE)
 * @param string $tableName (e.g., units)
 * @param int $recordId (the affected record's ID)
 * @param string|null $oldValues (JSON string or null)
 * @param string|null $newValues (JSON string or null)
 */
function logSystemAction($userId, $actionType, $tableName, $recordId, $oldValues = null, $newValues = null) {
    global $connect;
    try {
        // Insert into activity_log
        $stmt = $connect->prepare("INSERT INTO activity_log (user_id, action, module, reference_id, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param('issi', $userId, $actionType, $tableName, $recordId);
        $stmt->execute();
        $stmt->close();

        // Insert into audit_log
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
        $stmt2 = $connect->prepare("INSERT INTO audit_log (user_id, activity_type, description, old_value, new_value, ip_address, reference_id, timestamp) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $desc = $actionType . ' on ' . $tableName;
        $stmt2->bind_param('issssss', $userId, $actionType, $desc, $oldValues, $newValues, $ip, $recordId);
        $stmt2->execute();
        $stmt2->close();
    } catch (Exception $e) {
        error_log('logSystemAction error: ' . $e->getMessage());
    }
}

?> 