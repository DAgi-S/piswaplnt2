<?php
/**
 * Login Endpoint
 * Handles user authentication and JWT token generation
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
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-XSRF-TOKEN');
        header('Access-Control-Allow-Credentials: true');
        header('Vary: Origin');
    }
}

require_once __DIR__ . '/../../../includes/jwt/JWTHandler.php';
require_once __DIR__ . '/../../../includes/jwt/RateLimitMiddleware.php';
require_once __DIR__ . '/../../../includes/jwt/CSRFMiddleware.php';
require_once __DIR__ . '/../../../includes/jwt/SessionManager.php';
require_once __DIR__ . '/../../../config/database.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Generate CSRF token for preflight requests
    if (JWT_CSRF_ENABLED && !$isTestEnvironment) {
        $csrfMiddleware = new CSRFMiddleware();
        $csrfMiddleware->generateToken();
    }
    if (!$isTestEnvironment) {
        http_response_code(200);
    }
    ob_end_flush();
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (!$isTestEnvironment) {
        http_response_code(405);
    }
    echo json_encode(['error' => 'Method not allowed']);
    ob_end_flush();
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
    $identifier = isset($data->username) ? $data->username : $_SERVER['REMOTE_ADDR'];
    $rateLimiter->handle($identifier);

    // Validate CSRF token if enabled
    if (JWT_CSRF_ENABLED && !in_array('/api/v2/auth/login', $JWT_CSRF_EXEMPT_ROUTES)) {
        $csrfMiddleware = new CSRFMiddleware();
        $csrfMiddleware->handle();
    }

    // Sanitize input
    $username = filter_var($data->username, FILTER_SANITIZE_EMAIL);
    $password = $data->password;

    // Validate email format
    if (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Connect to database
    $database = new PiStockDatabase();
    $db = $database->getConnection();

    // Prepare query with parameterized statement
    $query = "SELECT id, email, password, role, status FROM users WHERE email = ? LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Invalid credentials');
    }

    $user = $result->fetch_assoc();

    // Verify password using constant-time comparison
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

    // Create new session
    $sessionManager = SessionManager::getInstance();
    
    // Invalidate all other sessions for this user (optional, based on security requirements)
    // $sessionManager->invalidateAllSessions($user['id']);
    
    // Create new session
    $sessionId = $sessionManager->createSession($user['id'], $refreshToken);
    
    if (!$sessionId) {
        throw new Exception('Failed to create session');
    }

    // Set refresh token in HTTP-only cookie
    $jwtHandler->setRefreshTokenCookie($refreshToken);

    // Generate new CSRF token
    $csrfToken = null;
    if (JWT_CSRF_ENABLED) {
        $csrfMiddleware = new CSRFMiddleware();
        $csrfToken = $csrfMiddleware->generateToken();
    }

    // Get user permissions
    $permissions = $jwtHandler->getUserPermissions($user['role']);

    // Log successful login
    error_log(sprintf(
        "Successful login: user=%s, ip=%s, session=%s, timestamp=%s",
        $username,
        $_SERVER['REMOTE_ADDR'],
        $sessionId,
        date('Y-m-d H:i:s')
    ));

    // Return access token and user data
    echo json_encode([
        'status' => 'success',
        'message' => 'Login successful',
        'access_token' => $accessToken,
        'expires_in' => JWT_ACCESS_TOKEN_EXPIRY,
        'csrf_token' => $csrfToken,
        'session_id' => $sessionId,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'permissions' => $permissions
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

    // Log failed login attempt
    error_log(sprintf(
        "Failed login attempt: %s, ip=%s, timestamp=%s",
        $e->getMessage(),
        $_SERVER['REMOTE_ADDR'],
        date('Y-m-d H:i:s')
    ));

    http_response_code($statusCode);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} finally {
    ob_end_flush();
} 