<?php
/**
 * Staging Environment Configuration
 */

// Database Configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'pistocklnt_staging');
define('DB_USER', getenv('DB_USER') ?: 'staging_user');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Redis Configuration
define('REDIS_HOST', getenv('REDIS_HOST') ?: '127.0.0.1');
define('REDIS_PORT', getenv('REDIS_PORT') ?: 6379);
define('REDIS_PASSWORD', getenv('REDIS_PASSWORD') ?: null);

// JWT Configuration
define('JWT_SECRET_KEY', getenv('JWT_SECRET_KEY'));
define('JWT_REFRESH_KEY', getenv('JWT_REFRESH_KEY'));
define('JWT_ACCESS_TOKEN_EXPIRY', 900); // 15 minutes
define('JWT_REFRESH_TOKEN_EXPIRY', 604800); // 7 days

// CORS Settings
define('JWT_ALLOWED_ORIGINS', [
    'http://staging.pistocklnt.lebawi.net',
    'https://staging.pistocklnt.lebawi.net'
]);

// Cookie Settings
define('JWT_REFRESH_COOKIE_SECURE', true);
define('JWT_REFRESH_COOKIE_HTTPONLY', true);
define('JWT_REFRESH_COOKIE_SAMESITE', 'Strict');
define('JWT_REFRESH_COOKIE_PATH', '/');
define('JWT_REFRESH_COOKIE_DOMAIN', '.staging.pistocklnt.lebawi.net');

// WebSocket Settings
define('WS_HOST', '0.0.0.0');
define('WS_PORT', 8080);
define('WS_SECURE', true);

// Rate Limiting
define('JWT_RATE_LIMIT_ATTEMPTS', 5);
define('JWT_RATE_LIMIT_MINUTES', 15);

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/staging-error.log'); 