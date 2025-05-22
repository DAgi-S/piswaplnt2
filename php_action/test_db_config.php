<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set CORS headers
header('Access-Control-Allow-Origin: ' . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*'));
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

    // Get POST data
    $dbHost = trim($_POST['db_host'] ?? '');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = $_POST['db_password'] ?? ''; // Password can be empty for localhost

    // Validate required fields
    $isLocalhost = strtolower($dbHost) === 'localhost';
    if (empty($dbHost) || empty($dbName) || empty($dbUser) || 
        (!$isLocalhost && empty($dbPass))) {
        throw new Exception('Please fill all required fields' . 
                          ($isLocalhost ? ' (password is optional for localhost)' : ''));
    }

    try {
        // For localhost without password
        if ($isLocalhost && empty($dbPass)) {
            $testConn = new mysqli($dbHost, $dbUser);
            if (!$testConn->connect_error) {
                // Try to select the database
                if (!$testConn->select_db($dbName)) {
                    throw new Exception("Database '$dbName' not found");
                }
            }
        } else {
            // Normal connection with password
            $testConn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
        }

        // Check connection
        if ($testConn->connect_error) {
            throw new Exception("Connection failed: " . $testConn->connect_error);
        }

        // Try to execute a simple query to verify permissions
        $result = $testConn->query("SELECT 1");
        if (!$result) {
            throw new Exception("Database query failed: " . $testConn->error);
        }

        // Close test connection
        $testConn->close();

        echo json_encode([
            'success' => true,
            'message' => 'Database connection test successful!'
        ]);

    } catch (Exception $e) {
        if (isset($testConn)) {
            $testConn->close();
        }
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error in test_db_config.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Database test failed: ' . $e->getMessage()
    ]);
} 