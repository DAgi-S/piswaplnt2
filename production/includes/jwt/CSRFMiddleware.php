<?php
/**
 * CSRF Protection Middleware
 * Handles CSRF token generation and validation
 */

class CSRFMiddleware {
    private $tokenLength = 32;
    private $cookieName = 'XSRF-TOKEN';
    private $headerName = 'X-XSRF-TOKEN';
    private $sessionKey = 'csrf_tokens';
    private $maxTokens = 10; // Maximum number of valid tokens per session

    /**
     * Initialize CSRF middleware
     */
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION[$this->sessionKey])) {
            $_SESSION[$this->sessionKey] = [];
        }
    }

    /**
     * Generate a new CSRF token
     */
    public function generateToken() {
        $token = bin2hex(random_bytes($this->tokenLength));
        
        // Store token in session
        $_SESSION[$this->sessionKey][] = $token;
        
        // Keep only the most recent tokens
        if (count($_SESSION[$this->sessionKey]) > $this->maxTokens) {
            array_shift($_SESSION[$this->sessionKey]);
        }

        // Set token in cookie for JavaScript access
        setcookie(
            $this->cookieName,
            $token,
            [
                'expires' => 0, // Session cookie
                'path' => '/',
                'domain' => $_SERVER['HTTP_HOST'],
                'secure' => true,
                'samesite' => 'Strict'
            ]
        );

        return $token;
    }

    /**
     * Validate CSRF token
     */
    public function validateToken($token = null) {
        // Get token from header if not provided
        if ($token === null) {
            $headers = getallheaders();
            $token = isset($headers[$this->headerName]) ? $headers[$this->headerName] : null;
        }

        // Check if token exists and is valid
        if (!$token || !in_array($token, $_SESSION[$this->sessionKey])) {
            throw new Exception('Invalid CSRF token');
        }

        // Remove used token (single-use tokens)
        $key = array_search($token, $_SESSION[$this->sessionKey]);
        unset($_SESSION[$this->sessionKey][$key]);
        $_SESSION[$this->sessionKey] = array_values($_SESSION[$this->sessionKey]);

        return true;
    }

    /**
     * Check if request requires CSRF validation
     */
    public function shouldValidateRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        return in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH']);
    }

    /**
     * Handle CSRF protection for the request
     */
    public function handle() {
        // Skip validation for non-modifying requests
        if (!$this->shouldValidateRequest()) {
            return true;
        }

        try {
            // Validate CSRF token
            $this->validateToken();
            return true;
        } catch (Exception $e) {
            http_response_code(403);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
            exit();
        }
    }

    /**
     * Get current CSRF token (creates new if none exists)
     */
    public function getToken() {
        if (empty($_SESSION[$this->sessionKey])) {
            return $this->generateToken();
        }
        return end($_SESSION[$this->sessionKey]);
    }
} 