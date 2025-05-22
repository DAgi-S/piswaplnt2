<?php
/**
 * WebSocket Authentication Middleware
 * Handles JWT validation for WebSocket connections
 */

require_once __DIR__ . '/JWTHandler.php';

class WebSocketAuthMiddleware {
    private $jwtHandler;
    private $clients;
    private $authenticatedClients;

    public function __construct() {
        $this->jwtHandler = JWTHandler::getInstance();
        $this->authenticatedClients = new \SplObjectStorage;
    }

    /**
     * Handle authentication for a new connection
     */
    public function handleAuth($conn, $request) {
        try {
            // Get token from query parameters
            $query = $request->getUri()->getQuery();
            parse_str($query, $params);

            if (!isset($params['token'])) {
                throw new Exception('No token provided');
            }

            // Validate token
            $decoded = $this->jwtHandler->validateAccessToken($params['token']);

            // Store user data with connection
            $this->authenticatedClients->attach($conn, [
                'user_id' => $decoded->data->id,
                'email' => $decoded->data->email,
                'role' => $decoded->data->role
            ]);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if a connection is authenticated
     */
    public function isAuthenticated($conn) {
        return $this->authenticatedClients->contains($conn);
    }

    /**
     * Get user data for a connection
     */
    public function getUser($conn) {
        if (!$this->isAuthenticated($conn)) {
            return null;
        }
        return $this->authenticatedClients[$conn];
    }

    /**
     * Check if user has required permission
     */
    public function hasPermission($conn, $permission) {
        if (!$this->isAuthenticated($conn)) {
            return false;
        }

        $userData = $this->getUser($conn);
        return $this->jwtHandler->hasPermission((object)['data' => (object)$userData], $permission);
    }

    /**
     * Remove authentication data when connection closes
     */
    public function removeAuth($conn) {
        if ($this->authenticatedClients->contains($conn)) {
            $this->authenticatedClients->detach($conn);
        }
    }
} 