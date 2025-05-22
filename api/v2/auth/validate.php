<?php
/**
 * Token Validation Endpoint
 * Validates JWT tokens and handles token refresh
 */

// Start output buffering
ob_start();

// Check if we're in a test environment
$isTestEnvironment = defined('PHPUNIT_RUNNING') && PHPUNIT_RUNNING === true;

require_once __DIR__ . '/../../../config/security.php';

// Set security headers only if not in test environment
if (!$isTestEnvironment) {
    // Set security headers
    foreach (SECURITY_HEADERS as $header => $value) {
        header("$header: $value");
    }

    header('Content-Type: application/json');

    // Set CORS headers only for allowed origins
    $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
    if (in_array($origin, JWT_ALLOWED_ORIGINS)) {
        header("Access-Control-Allow-Origin: $origin");
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Allow-Credentials: true');
        header('Vary: Origin');
    }
}

require_once __DIR__ . '/../../../includes/jwt/JWTHandler.php';
require_once __DIR__ . '/../../../config/database.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    if (!$isTestEnvironment) {
        http_response_code(200);
    }
    ob_end_flush();
    exit();
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    if (!$isTestEnvironment) {
        http_response_code(405);
    }
    echo json_encode(['error' => 'Method not allowed']);
    ob_end_flush();
    exit();
}

try {
    $jwtHandler = new JWTHandler();
    
    // Get Authorization header
    $authHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
    if (!$authHeader) {
        throw new Exception('Authorization header is required');
    }

    // Extract token
    if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        throw new Exception('Invalid authorization format');
    }

    $token = $matches[1];
    
    // Validate token
    $payload = $jwtHandler->validateAccessToken($token);
    
    if (!$payload) {
        throw new Exception('Invalid or expired token');
    }

    // Return user info from token
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $payload->id,
            'email' => $payload->email,
            'role' => $payload->role
        ]
    ]);

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    ob_end_flush();
} 