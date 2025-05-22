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

try {
    // Check rate limit
    if (!$rateLimitMiddleware->checkRateLimit('login')) {
        http_response_code(429);
        echo json_encode(['error' => 'Rate limit exceeded']);
        exit;
    }
    
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    $username = $data['username'] ?? null;
    $password = $data['password'] ?? null;
    
    if (!$username || !$password) {
        http_response_code(400);
        echo json_encode(['error' => 'Username and password are required']);
        exit;
    }
    
    // Mock authentication for testing
    if ($username === 'test' && $password === 'test123') {
        $payload = [
            'sub' => 1,
            'username' => 'test',
            'role' => 'user'
        ];
        
        // Generate tokens
        $accessToken = $jwtMiddleware->generateToken($payload);
        $refreshToken = $jwtMiddleware->generateRefreshToken($payload);
        
        // Set refresh token cookie
        setcookie('refresh_token', $refreshToken, [
            'expires' => time() + JWT_REFRESH_TOKEN_EXPIRY,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        
        // Generate CSRF token
        $csrfToken = generate_csrf_token();
        
        echo json_encode([
            'status' => 'success',
            'access_token' => $accessToken,
            'csrf_token' => $csrfToken
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
} 