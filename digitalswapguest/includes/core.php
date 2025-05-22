<?php
/**
 * Core functionality file
 */

// Load configuration first
require_once __DIR__ . '/config.php';

// Load session configuration
require_once __DIR__ . '/session_config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    error_log("Core - Session started with ID: " . session_id());
}

// Debug session state
error_log("Core - Current Session ID: " . session_id());
error_log("Core - Session Data: " . print_r($_SESSION, true));

// Load other required files
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/functions.php';

// Initialize error reporting
if (DISPLAY_ERRORS) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}

// Set timezone
date_default_timezone_set(DEFAULT_TIMEZONE);

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    $loggedIn = isset($_SESSION['guest_id']) && !empty($_SESSION['guest_id']) &&
                isset($_SESSION['guest_code']) && !empty($_SESSION['guest_code']);
    error_log("Core - isLoggedIn check - Session ID: " . session_id());
    error_log("Core - isLoggedIn check - Result: " . ($loggedIn ? "true" : "false"));
    error_log("Core - isLoggedIn check - Session Data: " . print_r($_SESSION, true));
    return $loggedIn;
}

/**
 * Redirect to login page if not logged in
 */
function requireLogin() {
    error_log("Core - requireLogin check - Session ID: " . session_id());
    error_log("Core - requireLogin check - Session Data: " . print_r($_SESSION, true));
    error_log("Core - requireLogin check - Script path: " . $_SERVER['SCRIPT_NAME']);
    
    if (!isLoggedIn()) {
        error_log("Core - User not logged in, redirecting to login page");
        setFlashMessage('Please log in to continue', 'warning');
        $loginPath = rtrim(dirname($_SERVER['PHP_SELF']), '/') . '/index.php';
        error_log("Core - Redirecting to: " . $loginPath);
        header('Location: ' . $loginPath);
        exit();
    }
    error_log("Core - User is logged in, continuing");
}

/**
 * Get current user data
 * @return array|null
 */
function getCurrentUser() {
    global $connect;
    
    try {
        if (!isset($_SESSION['guest_id'])) {
            return null;
        }

        $sql = "SELECT gu.* FROM guest_users gu WHERE gu.id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("i", $_SESSION['guest_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        $userData = $result->fetch_assoc();
        
        // If active account is set, get the account details
        if (isset($_SESSION['active_guest_account'])) {
            $sql = "SELECT a.id as account_id, a.account_owner, a.account_platform, a.Currency 
                    FROM accounts a 
                    JOIN guest_account_links gal ON a.id = gal.account_id 
                    WHERE gal.guest_id = ? AND a.id = ? AND a.status = 1";
            $stmt = $connect->prepare($sql);
            $stmt->bind_param("ii", $userData['id'], $_SESSION['active_guest_account']);
            $stmt->execute();
            $accountResult = $stmt->get_result();
            
            if ($accountResult->num_rows > 0) {
                $accountData = $accountResult->fetch_assoc();
                $userData = array_merge($userData, $accountData);
                error_log("getCurrentUser - Account data merged: " . print_r($accountData, true));
            } else {
                error_log("getCurrentUser - No account data found for ID: " . $_SESSION['active_guest_account']);
                // If active account is not found or inactive, reset it
                unset($_SESSION['active_guest_account']);
                
                // Try to get first available active account
                $sql = "SELECT a.id FROM accounts a 
                        JOIN guest_account_links gal ON a.id = gal.account_id 
                        WHERE gal.guest_id = ? AND a.status = 1 
                        LIMIT 1";
                $stmt = $connect->prepare($sql);
                $stmt->bind_param("i", $userData['id']);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $_SESSION['active_guest_account'] = $row['id'];
                    error_log("getCurrentUser - Set new active account: " . $row['id']);
                }
            }
        }
        
        return $userData;
    } catch (Exception $e) {
        error_log("Error in getCurrentUser: " . $e->getMessage());
        return null;
    }
}

/**
 * Clean input data
 * @param mixed $data
 * @return mixed
 */
function cleanInput($data) {
    if (is_array($data)) {
        return array_map('cleanInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Format currency amount
 * @param float $amount
 * @param string $currency
 * @return string
 */
function formatCurrency($amount, $currency = DEFAULT_CURRENCY) {
    return $currency . ' ' . number_format(
        $amount,
        CURRENCY_DECIMALS,
        CURRENCY_DECIMAL_SEPARATOR,
        CURRENCY_THOUSANDS_SEPARATOR
    );
}

/**
 * Format date
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDate($date, $format = DATE_FORMAT) {
    return date($format, strtotime($date));
}

/**
 * Generate CSRF token
 * @return string
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Set flash message
 * @param string $message
 * @param string $type
 */
function setFlashMessage($message, $type = MESSAGE_INFO) {
    $_SESSION['flash_message'] = [
        'text' => $message,
        'type' => $type
    ];
}

/**
 * Get flash message
 * @return array|null
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

// Set error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("[$errno] $errstr in $errfile on line $errline");
    if (DISPLAY_ERRORS) {
        echo '<div class="alert alert-danger">' . htmlspecialchars("[$errno] $errstr") . '</div>';
    } else {
        echo '<div class="alert alert-danger">An error occurred. Please try again later.</div>';
    }
    return true;
});

// Set exception handler
set_exception_handler(function($e) {
    error_log($e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    http_response_code(500);
    if (DISPLAY_ERRORS) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>';
    } else {
        echo '<div class="alert alert-danger">An error occurred. Please try again later.</div>';
    }
});
?> 