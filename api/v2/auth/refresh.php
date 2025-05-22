<?php
/**
 * Refresh Token Endpoint
 * Handles token refresh requests and issues new access tokens
 */

// Set security headers
require_once __DIR__ . '/../../../config/security.php';
apply_security_headers();

header('Content-Type: application/json');

// Set CORS headers only for allowed origins
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if (in_array($origin, JWT_ALLOWED_ORIGINS)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-XSRF-TOKEN');
    header('Access-Control-Allow-Credentials: true');
    header('Vary: Origin');
}

require_once __DIR__ . '/../../../includes/jwt/JWTHandler.php';
require_once __DIR__ . '/../../../includes/jwt/RateLimitMiddleware.php';
require_once __DIR__ . '/../../../includes/jwt/SessionManager.php';
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
    // Apply rate limiting
    $rateLimiter = new RateLimitMiddleware(3, 15); // Stricter rate limiting for refresh
    $rateLimiter->handle($_SERVER['REMOTE_ADDR']);

    // Check for refresh token in cookie
    if (!isset($_COOKIE[JWT_REFRESH_COOKIE_NAME])) {
        throw new Exception('Refresh token not found');
    }

    // Check for session ID in request
    $data = json_decode(file_get_contents("php://input"));
    if (!isset($data->session_id)) {
        throw new Exception('Session ID is required');
    }

    $refreshToken = $_COOKIE[JWT_REFRESH_COOKIE_NAME];
    $sessionId = $data->session_id;

    $jwtHandler = JWTHandler::getInstance();
    $sessionManager = SessionManager::getInstance();

    // Validate refresh token
    $decoded = $jwtHandler->validateRefreshToken($refreshToken);

    if (!$decoded || !isset($decoded->data->user_id)) {
        throw new Exception('Invalid refresh token');
    }

    $userId = $decoded->data->user_id;

    // Validate session
    if (!$sessionManager->validateSession($userId, $sessionId, $refreshToken)) {
        // If session validation fails, invalidate all sessions for this user
        $sessionManager->invalidateAllSessions($userId);
        throw new Exception('Invalid session');
    }

    // Get user data from database
    $database = new PiStockDatabase();
    $db = $database->getConnection();

    $query = "SELECT id, email, role, status FROM users WHERE id = ? AND status = 'active' LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('User not found or inactive');
    }

    $user = $result->fetch_assoc();

    // Generate new tokens
    $accessToken = $jwtHandler->generateAccessToken([
        'id' => $user['id'],
        'email' => $user['email'],
        'role' => $user['role']
    ]);

    $newRefreshToken = $jwtHandler->generateRefreshToken($user['id']);

    // Invalidate old session and create new one
    $sessionManager->invalidateSession($userId, $sessionId);
    $newSessionId = $sessionManager->createSession($userId, $newRefreshToken);

    if (!$newSessionId) {
        throw new Exception('Failed to create new session');
    }

    // Set new refresh token cookie
    $jwtHandler->setRefreshTokenCookie($newRefreshToken);

    // Get user permissions
    $permissions = $jwtHandler->getUserPermissions($user['role']);

    // Log token refresh
    error_log(sprintf(
        "Token refreshed: user=%s, ip=%s, old_session=%s, new_session=%s, timestamp=%s",
        $user['email'],
        $_SERVER['REMOTE_ADDR'],
        $sessionId,
        $newSessionId,
        date('Y-m-d H:i:s')
    ));

    // Return new access token and session info
    echo json_encode([
        'status' => 'success',
        'message' => 'Token refreshed successfully',
        'access_token' => $accessToken,
        'expires_in' => JWT_ACCESS_TOKEN_EXPIRY,
        'session_id' => $newSessionId,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'permissions' => $permissions
        ]
    ]);

} catch (Exception $e) {
    $statusCode = 401;
    
    if ($e->getMessage() === JWT_ERROR_RATE_LIMIT) {
        $statusCode = 429;
    }

    // Log refresh failure
    error_log(sprintf(
        "Token refresh failed: %s, ip=%s, timestamp=%s",
        $e->getMessage(),
        $_SERVER['REMOTE_ADDR'],
        date('Y-m-d H:i:s')
    ));

    http_response_code($statusCode);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 