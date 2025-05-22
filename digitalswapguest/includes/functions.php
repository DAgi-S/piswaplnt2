<?php
/**
 * Utility functions for the application
 */

/**
 * Log user activity
 * @param string $action The action being performed
 * @param string $description Description of the activity
 * @return bool Whether the logging was successful
 * @function logActivity(Section, $description)
 */
function logActivity($action, $description) {
    global $connect;
    
    // Debug logging
    error_log("Attempting to log activity: " . $action . " - " . $description);
    
    // Check if we have database connection
    if (!isset($connect) || !($connect instanceof mysqli)) {
        error_log("Database connection not available in logActivity");
        return false;
    }
    
    // Get current user's guest_id from session
    $guest_id = $_SESSION['guest_id'] ?? null;
    if (!$guest_id) {
        error_log("No guest_id found in session");
        return false;
    }
    
    // Get the current timestamp
    $timestamp = date('Y-m-d H:i:s');
    
    try {
        // Prepare the SQL statement
        $sql = "INSERT INTO activity_logs (guest_id, action, description, timestamp) VALUES (?, ?, ?, ?)";
        $stmt = $connect->prepare($sql);
        
        if (!$stmt) {
            error_log("Failed to prepare statement: " . $connect->error);
            return false;
        }
        
        // Bind parameters and execute
        $stmt->bind_param("ssss", $guest_id, $action, $description, $timestamp);
        $result = $stmt->execute();
        
        if (!$result) {
            error_log("Failed to execute statement: " . $stmt->error);
        }
        
        $stmt->close();
        return $result;
    } catch (Exception $e) {
        error_log("Exception in logActivity: " . $e->getMessage());
        return false;
    }
}

/**
 * Validate guest access to account
 * @param int $accountId The account ID to validate
 * @return bool Whether the guest has access to the account
 */
function validateGuestAccountAccess($accountId) {
    global $connect;
    
    if (!isLoggedIn() || !isset($connect)) {
        return false;
    }
    
    try {
        $sql = "SELECT 1 FROM guest_account_links 
                WHERE guest_id = ? AND account_id = ?";
        $stmt = $connect->prepare($sql);
        
        if (!$stmt) {
            error_log("Failed to prepare statement in validateGuestAccountAccess");
            return false;
        }
        
        $guest_id = $_SESSION['guest_id'];
        $stmt->bind_param("si", $guest_id, $accountId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $hasAccess = $result->num_rows > 0;
        
        $stmt->close();
        return $hasAccess;
    } catch (Exception $e) {
        error_log("Error validating guest account access: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate a secure random token
 * @param int $length Length of the token (default: 32)
 * @return string Generated token
 */
function generateToken($length = 32) {
    try {
        return bin2hex(random_bytes($length / 2));
    } catch (Exception $e) {
        error_log("Error generating token: " . $e->getMessage());
        return false;
    }
}

/**
 * Log system errors to file
 * @param string $message Error message
 * @param string $level Error level (default: 'ERROR')
 * @return bool Whether the error was logged successfully
 */
function logSystemError($message, $level = 'ERROR') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
    
    return error_log($logMessage, 3, ERROR_LOG_FILE);
} 