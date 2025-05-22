<?php
/**
 * Security Configuration
 * Centralizes all security-related settings and constants
 */

// Security Headers
define('SECURITY_HEADERS', [
    'X-Content-Type-Options' => 'nosniff',
    'X-Frame-Options' => 'DENY',
    'X-XSS-Protection' => '1; mode=block',
    'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
    'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';",
    'Referrer-Policy' => 'strict-origin-when-cross-origin',
    'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()'
]);

// JWT Configuration
define('JWT_SECRET_KEY', getenv('JWT_SECRET_KEY') ?: 'your-secret-key-here');
define('JWT_REFRESH_KEY', getenv('JWT_REFRESH_KEY') ?: 'your-refresh-key-here');
define('JWT_ACCESS_TOKEN_EXPIRY', 900); // 15 minutes
define('JWT_REFRESH_TOKEN_EXPIRY', 2592000); // 30 days
define('JWT_ALGORITHM', 'HS256');
define('JWT_ISSUER', 'pistocklntmarch');
define('JWT_AUDIENCE', 'pistocklntmarch-client');

// CORS Configuration
define('CORS_ALLOWED_ORIGINS', [
    'http://localhost',
    'http://localhost:3000',
    'http://localhost/pistocklnt1march'
]);
define('CORS_ALLOWED_METHODS', 'GET, POST, PUT, DELETE, OPTIONS');
define('CORS_ALLOWED_HEADERS', 'Content-Type, Authorization, X-CSRF-Token');
define('CORS_EXPOSE_HEADERS', 'X-RateLimit-Limit, X-RateLimit-Remaining, X-RateLimit-Reset');
define('CORS_MAX_AGE', 86400); // 24 hours

// CSRF Protection
define('CSRF_ENABLED', true);
define('CSRF_TOKEN_LENGTH', 32);
define('CSRF_COOKIE_NAME', 'XSRF-TOKEN');
define('CSRF_HEADER_NAME', 'X-CSRF-TOKEN');
define('CSRF_COOKIE_PATH', '/');
define('CSRF_COOKIE_DOMAIN', '');
define('CSRF_COOKIE_SECURE', true);
define('CSRF_COOKIE_HTTPONLY', true);
define('CSRF_COOKIE_SAMESITE', 'Lax');

// Rate Limiting
define('RATE_LIMIT_ENABLED', true);
define('RATE_LIMIT_REQUESTS', 60);
define('RATE_LIMIT_WINDOW', 60); // 60 requests per minute
define('RATE_LIMIT_HEADERS', true);

// Cookie Security
define('COOKIE_SECURE', true);
define('COOKIE_HTTPONLY', true);
define('COOKIE_SAMESITE', 'Lax');
define('SESSION_REGENERATE', true);

// Content Security Policy
define('CSP_ENABLED', true);
define('CSP_POLICY', "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:;");

// XSS Protection
define('XSS_PROTECTION', '1; mode=block');

// Frame Options
define('FRAME_OPTIONS', 'SAMEORIGIN');

// Content Type Options
define('CONTENT_TYPE_OPTIONS', 'nosniff');

// Referrer Policy
define('REFERRER_POLICY', 'same-origin');

// Feature Policy
define('FEATURE_POLICY', "geolocation 'none'; midi 'none'; sync-xhr 'self'; microphone 'none'; camera 'none'; magnetometer 'none'; gyroscope 'none'; fullscreen 'self'; payment 'none'");

// Redis Configuration
if (!defined('REDIS_HOST')) {
    define('REDIS_HOST', getenv('REDIS_HOST') ?: '127.0.0.1');
    define('REDIS_PORT', getenv('REDIS_PORT') ?: 6379);
    define('REDIS_PASSWORD', getenv('REDIS_PASSWORD'));
    define('REDIS_TIMEOUT', 1.0);
    define('REDIS_READ_TIMEOUT', 1.0);
    define('REDIS_RETRY_INTERVAL', 100);
    define('REDIS_PERSISTENT_ID', null);
    define('REDIS_PREFIX', 'pistocklnt:');
}

// Role Permissions
global $JWT_ROLE_PERMISSIONS;
$JWT_ROLE_PERMISSIONS = [
    'admin' => [
        'create_order' => true,
        'edit_order' => true,
        'delete_order' => true,
        'view_orders' => true,
        'manage_users' => true,
        'view_reports' => true,
        'manage_settings' => true
    ],
    'manager' => [
        'create_order' => true,
        'edit_order' => true,
        'view_orders' => true,
        'view_reports' => true
    ],
    'user' => [
        'create_order' => true,
        'view_orders' => true
    ],
    'viewer' => [
        'view_orders' => true
    ]
];

// WebSocket Security Settings
if (!defined('WS_SECURE')) {
    define('WS_SECURE', true);
    define('WS_HOST', getenv('WS_HOST') ?: 'localhost');
    define('WS_PORT', getenv('WS_PORT') ?: 8080);
    define('WS_SSL_CERT', getenv('WS_SSL_CERT') ?: __DIR__ . '/ssl/cert.pem');
    define('WS_SSL_KEY', getenv('WS_SSL_KEY') ?: __DIR__ . '/ssl/key.pem');
}

// Password Policy
if (!defined('PASSWORD_MIN_LENGTH')) {
    define('PASSWORD_MIN_LENGTH', 12);
    define('PASSWORD_REQUIRE_UPPERCASE', true);
    define('PASSWORD_REQUIRE_LOWERCASE', true);
    define('PASSWORD_REQUIRE_NUMBER', true);
    define('PASSWORD_REQUIRE_SPECIAL', true);
    define('PASSWORD_HISTORY_SIZE', 5);
}

// Session Security
if (!defined('SESSION_SECURE')) {
    define('SESSION_SECURE', true);
    define('SESSION_HTTPONLY', true);
    define('SESSION_SAMESITE', 'Strict');
    define('SESSION_LIFETIME', 3600);
    define('SESSION_REGENERATE_TIME', 300);
}

// Input Validation
if (!defined('MAX_REQUEST_SIZE')) {
    define('MAX_REQUEST_SIZE', '10M');
    define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx']);
    define('MAX_FILE_SIZE', '5M');
}

// Error Handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Security Logging
if (!defined('SECURITY_LOG_PATH')) {
    define('SECURITY_LOG_PATH', __DIR__ . '/../logs/security.log');
    define('SECURITY_LOG_LEVEL', 'warning'); // debug, info, warning, error, critical
    define('SECURITY_LOG_MAX_FILES', 30);
    define('SECURITY_LOG_MAX_SIZE', '10M');
}

/**
 * Apply security headers
 */
function apply_security_headers() {
    if (CSP_ENABLED) {
        header("Content-Security-Policy: " . CSP_POLICY);
    }
    header("X-XSS-Protection: " . XSS_PROTECTION);
    header("X-Frame-Options: " . FRAME_OPTIONS);
    header("X-Content-Type-Options: " . CONTENT_TYPE_OPTIONS);
    header("Referrer-Policy: " . REFERRER_POLICY);
    header("Feature-Policy: " . FEATURE_POLICY);
    
    // Apply CORS headers if needed
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        $origin = $_SERVER['HTTP_ORIGIN'];
        if (in_array($origin, CORS_ALLOWED_ORIGINS)) {
            header("Access-Control-Allow-Origin: " . $origin);
            header("Access-Control-Allow-Methods: " . CORS_ALLOWED_METHODS);
            header("Access-Control-Allow-Headers: " . CORS_ALLOWED_HEADERS);
            header("Access-Control-Expose-Headers: " . CORS_EXPOSE_HEADERS);
            header("Access-Control-Max-Age: " . CORS_MAX_AGE);
            header("Access-Control-Allow-Credentials: true");
        }
    }
}

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (!CSRF_ENABLED) return null;
    
    $token = bin2hex(random_bytes(CSRF_TOKEN_LENGTH / 2));
    setcookie(
        CSRF_COOKIE_NAME,
        $token,
        [
            'expires' => 0,
            'path' => CSRF_COOKIE_PATH,
            'domain' => CSRF_COOKIE_DOMAIN,
            'secure' => CSRF_COOKIE_SECURE,
            'httponly' => CSRF_COOKIE_HTTPONLY,
            'samesite' => CSRF_COOKIE_SAMESITE
        ]
    );
    return $token;
}

/**
 * Verify CSRF token
 */
function verify_csrf_token() {
    if (!CSRF_ENABLED) return true;
    
    $token = $_COOKIE[CSRF_COOKIE_NAME] ?? null;
    $headerToken = $_SERVER['HTTP_' . str_replace('-', '_', CSRF_HEADER_NAME)] ?? null;
    
    return $token && $headerToken && hash_equals($token, $headerToken);
}

// Apply security headers by default
apply_security_headers(); 