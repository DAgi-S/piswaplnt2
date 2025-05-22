<?php
/**
 * Login Endpoint
 * Handles user authentication and JWT token generation
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . implode(', ', JWT_ALLOWED_ORIGINS));
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-XSRF-TOKEN');
header('Access-Control-Allow-Credentials: true');

require_once __DIR__ . '/../../../includes/jwt/JWTHandler.php';
require_once __DIR__ . '/../../../includes/jwt/RateLimitMiddleware.php';
require_once __DIR__ . '/../../../includes/jwt/CSRFMiddleware.php';
require_once __DIR__ . '/../../../config/database.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Generate CSRF token for preflight requests
    if (JWT_CSRF_ENABLED) {
        $csrfMiddleware = new CSRFMiddleware();
        $csrfMiddleware->generateToken();
    }
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

try {
    // Get posted data
    $data = json_decode(file_get_contents("php://input"));

    if (!isset($data->username) || !isset($data->password)) {
        throw new Exception('Username and password are required');
    }

    // Apply rate limiting
    $rateLimiter = new RateLimitMiddleware();
    // Use username for rate limiting if available, otherwise use IP
    $identifier = isset($data->username) ? $data->username : null;
    $rateLimiter->handle($identifier);

    // Validate CSRF token if enabled
    if (JWT_CSRF_ENABLED && !in_array('/api/v2/auth/login', $JWT_CSRF_EXEMPT_ROUTES)) {
        $csrfMiddleware = new CSRFMiddleware();
        $csrfMiddleware->handle();
    }

    // Sanitize input
    $username = filter_var($data->username, FILTER_SANITIZE_EMAIL);
    $password = $data->password;

    // Connect to database
    $database = new Database();
    $db = $database->getConnection();

    // Prepare query
    $query = "SELECT id, email, password, role, status FROM users WHERE email = ? LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Invalid credentials');
    }

    $user = $result->fetch_assoc();

    // Verify password
    if (!password_verify($password, $user['password'])) {
        throw new Exception('Invalid credentials');
    }

    // Check if user is active
    if ($user['status'] !== 'active') {
        throw new Exception('Account is not active');
    }

    // Generate tokens
    $jwtHandler = JWTHandler::getInstance();
    
    $accessToken = $jwtHandler->generateAccessToken([
        'id' => $user['id'],
        'email' => $user['email'],
        'role' => $user['role']
    ]);

    $refreshToken = $jwtHandler->generateRefreshToken($user['id']);

    // Set refresh token in HTTP-only cookie
    $jwtHandler->setRefreshTokenCookie($refreshToken);

    // Generate new CSRF token if enabled
    $csrfToken = null;
    if (JWT_CSRF_ENABLED) {
        $csrfMiddleware = new CSRFMiddleware();
        $csrfToken = $csrfMiddleware->generateToken();
    }

    // Return access token and user data
    echo json_encode([
        'status' => 'success',
        'message' => 'Login successful',
        'access_token' => $accessToken,
        'expires_in' => JWT_ACCESS_TOKEN_EXPIRY,
        'csrf_token' => $csrfToken,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role']
        ]
    ]);

} catch (Exception $e) {
    $statusCode = 401;
    
    // Different status code for rate limiting and CSRF errors
    if ($e->getMessage() === JWT_ERROR_RATE_LIMIT) {
        $statusCode = 429;
    } elseif ($e->getMessage() === JWT_ERROR_CSRF) {
        $statusCode = 403;
    }

    http_response_code($statusCode);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 