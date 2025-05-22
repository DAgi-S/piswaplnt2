<?php
namespace Middleware;

use Exception;

/**
 * CSRF Protection Middleware
 * Implements CSRF token generation and validation
 */

class CSRFMiddleware {
    private $enabled;
    private $cookieName;
    private $headerName;

    public function __construct() {
        $this->enabled = JWT_CSRF_ENABLED;
        $this->cookieName = JWT_CSRF_COOKIE_NAME;
        $this->headerName = JWT_CSRF_HEADER_NAME;
    }

    /**
     * Generate a new CSRF token
     */
    public function generateToken() {
        if (!$this->enabled) {
            return null;
        }
        
        $token = bin2hex(random_bytes(32));
        
        setcookie($this->cookieName, $token, [
            'expires' => time() + 3600,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => false,
            'samesite' => 'Strict'
        ]);

        return $token;
    }

    /**
     * Validate CSRF token
     */
    public function handle() {
        if (!JWT_CSRF_ENABLED) {
            return true;
        }

        // Get token from cookie
        $cookieToken = isset($_COOKIE[$this->cookieName]) ? $_COOKIE[$this->cookieName] : null;

        // Get token from header
        $headerName = 'HTTP_' . str_replace('-', '_', strtoupper($this->headerName));
        $headerToken = isset($_SERVER[$headerName]) ? $_SERVER[$headerName] : null;

        if (!$cookieToken || !$headerToken || !hash_equals($cookieToken, $headerToken)) {
            http_response_code(403);
            throw new Exception(JWT_ERROR_CSRF);
        }

        return true;
    }

    /**
     * Clear CSRF token
     */
    public function clearToken() {
        setcookie(
            $this->cookieName,
            '',
            [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => true,
                'httponly' => false,
                'samesite' => 'Strict'
            ]
        );
    }

    public function validateToken() {
        if (!$this->enabled) {
            return true;
        }
        
        $cookieToken = $_COOKIE[$this->cookieName] ?? null;
        $headerToken = $_SERVER['HTTP_' . str_replace('-', '_', strtoupper($this->headerName))] ?? null;
        
        if (!$cookieToken || !$headerToken) {
            return false;
        }
        
        return hash_equals($cookieToken, $headerToken);
    }
} 