<?php
require_once '../includes/core.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set response header
header('Content-Type: application/json');

// Initialize response array
$response = [
    'success' => false,
    'messages' => ''
];

// Debug log function
function debugLog($message, $data = null) {
    error_log("Login Debug - " . $message);
    if ($data !== null) {
        error_log("Data: " . print_r($data, true));
    }
}

// Function to send JSON response
function sendJsonResponse($data) {
    if (headers_sent()) {
        debugLog('Headers already sent. Could not send JSON response.');
        return;
    }
    
    try {
        echo json_encode($data);
    } catch (Exception $e) {
        debugLog('JSON encoding error: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'messages' => 'An error occurred while processing your request.'
        ]);
    }
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    debugLog('Invalid request method: ' . $_SERVER['REQUEST_METHOD']);
    $response['messages'] = 'Invalid request method';
    sendJsonResponse($response);
}

// Debug log POST data (excluding password)
$debugPost = $_POST;
if (isset($debugPost['password'])) {
    $debugPost['password'] = 'REDACTED';
}
debugLog('Received POST data', $debugPost);

// Validate required fields
if (empty($_POST['username']) || empty($_POST['password'])) {
    debugLog('Missing required fields');
    $response['messages'] = 'Username and password are required';
    sendJsonResponse($response);
}

try {
    // Check database connection
    if (!isset($connect) || !($connect instanceof mysqli)) {
        throw new Exception("Database connection not available");
    }
    debugLog('Database connection verified');

    // Clean input
    $username = cleanInput($_POST['username']);
    $password = $_POST['password'];
    debugLog('Processing login for username: ' . $username);

    // Get user data
    $sql = "SELECT * FROM guest_users WHERE username = ? LIMIT 1";
    $stmt = $connect->prepare($sql);
    
    if (!$stmt) {
        debugLog('Database prepare error: ' . $connect->error);
        throw new Exception("Database error: " . $connect->error);
    }
    
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    debugLog('Query executed successfully');

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        debugLog('User found', ['guest_id' => $user['guest_id'], 'status' => $user['status']]);
        
        // Check if account is active
        if ($user['status'] != 1) {
            debugLog('Inactive account attempt', ['username' => $username, 'status' => $user['status']]);
            logActivity('login_failed', 'Inactive account attempt: ' . $username);
            $response['messages'] = 'Account is inactive. Please contact administrator.';
            sendJsonResponse($response);
        }

        // Verify password
        if (password_verify($password, $user['password'])) {
            debugLog('Password verified successfully');
            
            // Clear any existing session data
            session_unset();
            session_destroy();
            session_start();
            session_regenerate_id(true);
            debugLog('Session regenerated with new ID: ' . session_id());
            
            // Set session variables
            $_SESSION['guest_id'] = $user['id'];
            $_SESSION['guest_code'] = $user['guest_id'];
            $_SESSION['guest_name'] = $user['full_name'];
            $_SESSION['user_data'] = [
                'id' => $user['id'],
                'guest_id' => $user['guest_id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'],
                'account_name' => $user['account_name']
            ];
            debugLog('Session variables set', $_SESSION);
            
            // Get linked accounts
            $accountSql = "SELECT gal.account_id 
                          FROM guest_account_links gal 
                          WHERE gal.guest_id = ? 
                          LIMIT 1";
            $accountStmt = $connect->prepare($accountSql);
            $accountStmt->bind_param("i", $user['id']);
            $accountStmt->execute();
            $accountResult = $accountStmt->get_result();
            
            if ($accountResult->num_rows > 0) {
                $accountRow = $accountResult->fetch_assoc();
                $_SESSION['active_guest_account'] = $accountRow['account_id'];
                debugLog('Active account set', ['account_id' => $accountRow['account_id']]);
            } else {
                debugLog('No linked accounts found for user');
            }
            $accountStmt->close();
            
            // Update last login
            $updateSql = "UPDATE guest_users SET last_login = NOW() WHERE id = ?";
            $updateStmt = $connect->prepare($updateSql);
            $updateStmt->bind_param("i", $user['id']);
            $updateStmt->execute();
            $updateStmt->close();
            debugLog('Last login updated');

            // Log successful login
            logActivity('login_success', 'User logged in successfully');

            // Verify session data is set
            debugLog('Final session state', [
                'session_id' => session_id(),
                'session_data' => $_SESSION
            ]);

            $response['success'] = true;
            $response['messages'] = 'Login successful';
            $response['redirect'] = rtrim(dirname(dirname($_SERVER['PHP_SELF'])), '/') . '/dashboard.php';
            debugLog('Login successful, preparing redirect', ['redirect' => $response['redirect']]);
        } else {
            debugLog('Invalid password attempt', ['username' => $username]);
            logActivity('login_failed', 'Invalid password for user: ' . $username);
            sleep(1);
            $response['messages'] = 'Invalid username or password';
        }
    } else {
        debugLog('User not found', ['username' => $username]);
        logActivity('login_failed', 'Invalid username attempt: ' . $username);
        sleep(1);
        $response['messages'] = 'Invalid username or password';
    }

} catch (Exception $e) {
    debugLog('Login error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
    error_log("Login error for " . $username . ": " . $e->getMessage());
    logActivity('login_error', 'System error during login attempt');
    $response['messages'] = 'An error occurred. Please try again.';
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
}

debugLog('Sending final response', $response);
sendJsonResponse($response); 