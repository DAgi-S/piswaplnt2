<?php
/**
 * JWT Authentication Middleware
 * Validates JWT tokens and handles authorization
 */

require_once __DIR__ . '/JWTHandler.php';

class JWTMiddleware {
    private $jwtHandler;
    private $excludedPaths;

    public function __construct($excludedPaths = []) {
        $this->jwtHandler = JWTHandler::getInstance();
        $this->excludedPaths = $excludedPaths;
    }

    /**
     * Main middleware function
     */
    public function handle() {
        // Skip authentication for excluded paths
        if ($this->isExcludedPath()) {
            return true;
        }

        // Get token from header
        $token = $this->getBearerToken();

        if (!$token) {
            $this->sendError('No token provided', 401);
            return false;
        }

        try {
            // Validate token
            $decoded = $this->jwtHandler->validateAccessToken($token);
            
            // Store user data in request
            $_REQUEST['user'] = $decoded->data;
            
            return true;
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 401);
            return false;
        }
    }

    /**
     * Check if current path is excluded from authentication
     */
    private function isExcludedPath() {
        $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        foreach ($this->excludedPaths as $path) {
            if (strpos($path, '*') !== false) {
                $pattern = str_replace('*', '.*', $path);
                if (preg_match('#^' . $pattern . '$#', $currentPath)) {
                    return true;
                }
            } else if ($path === $currentPath) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get bearer token from Authorization header
     */
    private function getBearerToken() {
        $headers = $this->getAuthorizationHeader();
        
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    /**
     * Get Authorization header
     */
    private function getAuthorizationHeader() {
        $headers = null;
        
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER['Authorization']);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
        } else if (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        
        return $headers;
    }

    /**
     * Check if user has required permission
     */
    public function hasPermission($permission) {
        if (!isset($_REQUEST['user'])) {
            return false;
        }

        return $this->jwtHandler->hasPermission($_REQUEST['user'], $permission);
    }

    /**
     * Send error response
     */
    private function sendError($message, $code = 400) {
        http_response_code($code);
        echo json_encode([
            'status' => 'error',
            'message' => $message
        ]);
        exit();
    }
} 