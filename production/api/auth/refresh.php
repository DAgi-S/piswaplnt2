<?php
/**
 * Refresh Token Endpoint
 * Handles token refresh requests and issues new access tokens
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . implode(', ', JWT_ALLOWED_ORIGINS));
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

require_once __DIR__ . '/../../../includes/jwt/JWTHandler.php';
require_once __DIR__ . '/../../../config/database.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit();
}

try {
    // Check for refresh token in cookie
    if (!isset($_COOKIE[JWT_REFRESH_COOKIE_NAME])) {
        throw new Exception('Refresh token not found');
    }

    $refreshToken = $_COOKIE[JWT_REFRESH_COOKIE_NAME];
    $jwtHandler = JWTHandler::getInstance();

    // Validate refresh token
    $decoded = $jwtHandler->validateRefreshToken($refreshToken);

    if (!$decoded || !isset($decoded->data->user_id)) {
        throw new Exception('Invalid refresh token');
    }

    // Get user data from database
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

    // Generate new access token
    $accessToken = $jwtHandler->generateAccessToken([
        'id' => $user['id'],
        'email' => $user['email'],
        'role' => $user['role']
    ]);

    // Generate new refresh token (rotate refresh token for security)
    $newRefreshToken = $jwtHandler->generateRefreshToken($user['id']);
    
    // Set new refresh token cookie
    $jwtHandler->setRefreshTokenCookie($newRefreshToken);

    // Blacklist old refresh token
    $jwtHandler->blacklistToken($refreshToken);

    // Return new access token
    echo json_encode([
        'status' => 'success',
        'message' => 'Token refreshed successfully',
        'access_token' => $accessToken,
        'expires_in' => JWT_ACCESS_TOKEN_EXPIRY,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role']
        ]
    ]);

} catch (Exception $e) {
    // Clear refresh token cookie on error
    if (isset($jwtHandler)) {
        $jwtHandler->clearRefreshTokenCookie();
    }

    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 