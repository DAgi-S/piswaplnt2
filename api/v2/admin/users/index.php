<?php
require_once __DIR__ . '/../../../../includes/jwt/JWTMiddleware.php';
require_once __DIR__ . '/../../../../includes/jwt/RateLimitMiddleware.php';
require_once __DIR__ . '/../../../../includes/jwt/CSRFMiddleware.php';

// Apply security headers
require_once __DIR__ . '/../../../../config/security.php';
apply_security_headers();

// Initialize middleware
$jwtMiddleware = new JWTMiddleware();
$rateLimitMiddleware = new RateLimitMiddleware();
$csrfMiddleware = new CSRFMiddleware();

try {
    // Verify JWT token
    $token = $jwtMiddleware->getTokenFromHeader();
    if (!$token || !$jwtMiddleware->validateToken($token)) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    // Check if user has admin role
    $payload = $jwtMiddleware->getPayload($token);
    if (!isset($payload['role']) || $payload['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden: Admin access required']);
        exit;
    }
    
    // Check rate limit
    if (!$rateLimitMiddleware->checkRateLimit('api')) {
        http_response_code(429);
        echo json_encode(['error' => 'Rate limit exceeded']);
        exit;
    }
    
    // Return mock user list for testing
    echo json_encode([
        'status' => 'success',
        'users' => [
            ['id' => 1, 'username' => 'admin', 'role' => 'admin'],
            ['id' => 2, 'username' => 'user1', 'role' => 'user'],
            ['id' => 3, 'username' => 'user2', 'role' => 'user']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
} 