<?php
/**
 * Token Validation Endpoint
 * Validates JWT tokens and returns user information if valid
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . implode(', ', JWT_ALLOWED_ORIGINS));
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

require_once __DIR__ . '/../../../includes/jwt/JWTHandler.php';
require_once __DIR__ . '/../../../config/database.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Allow both GET and POST methods
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'])) {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit();
}

try {
    $jwtHandler = JWTHandler::getInstance();
    
    // Get token from Authorization header
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : 
                 (isset($headers['authorization']) ? $headers['authorization'] : null);

    if (!$authHeader) {
        throw new Exception('No authorization header found');
    }

    // Extract token from Bearer string
    if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        throw new Exception('Invalid authorization format');
    }

    $token = $matches[1];

    // Validate access token
    $decoded = $jwtHandler->validateAccessToken($token);

    if (!$decoded || !isset($decoded->data)) {
        throw new Exception('Invalid token');
    }

    // Get fresh user data from database
    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT id, email, role, status FROM users WHERE id = ? AND status = 'active' LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $decoded->data->user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('User not found or inactive');
    }

    $user = $result->fetch_assoc();

    // Calculate token expiration
    $expiresIn = $decoded->exp - time();

    // Return validation result and user data
    echo json_encode([
        'status' => 'success',
        'message' => 'Token is valid',
        'expires_in' => $expiresIn,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role']
        ],
        'permissions' => $jwtHandler->getUserPermissions($user['role'])
    ]);

} catch (Exception $e) {
    $statusCode = 401;
    
    // Different status code for rate limiting
    if ($e->getMessage() === JWT_ERROR_RATE_LIMIT) {
        $statusCode = 429;
    }

    http_response_code($statusCode);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 