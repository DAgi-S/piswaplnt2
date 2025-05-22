<?php
/**
 * Logout Handler
 * Handles user logout with proper session cleanup and security measures
 */

require_once 'php_action/core.php';
require_once 'php_action/db_connect.php';

// Log the logout activity if user was logged in
if(isset($_SESSION['userId'])) {
    try {
        // Prepare the logout log entry
        $userId = $_SESSION['userId'];
        $roleId = isset($_SESSION['roleId']) ? $_SESSION['roleId'] : 0;
        $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'unknown';
        
        $sql = "INSERT INTO user_activity_log (user_id, activity_type, activity_description, ip_address) 
                VALUES (?, 'logout', ?, ?)";
        
        $description = "User logged out - Username: " . $username . ", Role ID: " . $roleId;
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("iss", $userId, $description, $ipAddress);
        $stmt->execute();
        
    } catch (Exception $e) {
        error_log("Logout logging error: " . $e->getMessage());
    }
}

// Clear all session data
$_SESSION = array();

// Destroy session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}

// Remove all session variables
session_unset(); 

// Destroy the session 
session_destroy(); 

// Clear any other cookies set by the application
$cookies = array('remember_me', 'user_preferences', 'last_activity');
foreach($cookies as $cookie) {
    if(isset($_COOKIE[$cookie])) {
        setcookie($cookie, '', time()-42000, '/');
    }
}

// Ensure no caching of this page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Redirect to login page
header('location:'.$store_url);
exit();
?>