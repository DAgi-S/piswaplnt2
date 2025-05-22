<?php
require_once __DIR__ . '/../../../includes/jwt/JWTMiddleware.php';
require_once __DIR__ . '/../../../includes/jwt/RateLimitMiddleware.php';
require_once __DIR__ . '/../../../includes/jwt/CSRFMiddleware.php';

// Apply security headers
require_once __DIR__ . '/../../../config/security.php';
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
    
    // Check rate limit
    if (!$rateLimitMiddleware->checkRateLimit('api')) {
        http_response_code(429);
        echo json_encode(['error' => 'Rate limit exceeded']);
        exit;
    }
    
    // Verify CSRF token for non-GET requests
    if ($_SERVER['REQUEST_METHOD'] !== 'GET' && !$csrfMiddleware->validateToken()) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF token validation failed']);
        exit;
    }
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Protected endpoint accessed successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
} 