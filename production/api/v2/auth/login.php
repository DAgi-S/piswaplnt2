<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Allow from any origin
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Access-Control-Allow-Origin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Max-Age: 86400"); // 24 hours cache
header("Content-Type: application/json; charset=UTF-8");

require_once '../../config.php';

// Log request method and headers for debugging
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Request Headers: " . json_encode(getallheaders()));

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Log the raw input for debugging
    $rawInput = file_get_contents('php://input');
    error_log("Raw input: " . $rawInput);
    
    $data = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON: ' . json_last_error_msg());
    }
    
    if (!isset($data['username']) || !isset($data['password'])) {
        throw new Exception('Username and password are required');
    }

    $username = $data['username'];
    $password = $data['password'];

    // Log the connection attempt
    error_log("Attempting login for username: " . $username);

    // Prepare the SQL statement with the correct table structure
    $stmt = $pdo->prepare("SELECT user_id, username, email, password, role_id, full_name FROM users WHERE username = ? AND status = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('Invalid username or password');
    }

    if (!password_verify($password, $user['password'])) {
        throw new Exception('Invalid username or password');
    }

    // Get user role name
    $stmt = $pdo->prepare("SELECT role_name FROM user_roles WHERE role_id = ?");
    $stmt->execute([$user['role_id']]);
    $role = $stmt->fetch(PDO::FETCH_ASSOC);

    // Generate a token
    $token = bin2hex(random_bytes(32));
    
    // Store the token in the database
    $stmt = $pdo->prepare("INSERT INTO user_tokens (user_id, token, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$user['user_id'], $token]);

    // Update last login time
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
    $stmt->execute([$user['user_id']]);

    // Return the response
    $response = [
        'success' => true,
        'token' => $token,
        'user_id' => $user['user_id'],
        'email' => $user['email'],
        'username' => $user['username'],
        'full_name' => $user['full_name'],
        'role' => $role ? $role['role_name'] : null
    ];
    
    error_log("Login successful for user: " . $username);
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}