<?php
/**
 * Token Expiration Middleware
 * Handles token expiration and automatic refresh
 */

class TokenExpirationMiddleware {
    private $jwtHandler;
    private $refreshThreshold = 300; // 5 minutes before expiration

    public function __construct() {
        $this->jwtHandler = JWTHandler::getInstance();
    }

    /**
     * Handle token expiration
     * @param string $accessToken The current access token
     * @return array Response containing token status and new tokens if refreshed
     */
    public function handle($accessToken) {
        try {
            $payload = $this->jwtHandler->validateToken($accessToken);
            
            if (!$payload) {
                return [
                    'status' => 'error',
                    'message' => 'Invalid token',
                    'code' => 401
                ];
            }

            // Check if token is about to expire
            $exp = $payload->exp ?? 0;
            $now = time();
            
            // If token is expired
            if ($exp <= $now) {
                return [
                    'status' => 'error',
                    'message' => 'Token has expired',
                    'code' => 401,
                    'should_refresh' => true
                ];
            }

            // If token is about to expire
            if ($exp - $now <= $this->refreshThreshold) {
                // Try to refresh using refresh token
                if (isset($_COOKIE[JWT_REFRESH_COOKIE_NAME])) {
                    $refreshToken = $_COOKIE[JWT_REFRESH_COOKIE_NAME];
                    $refreshPayload = $this->jwtHandler->validateRefreshToken($refreshToken);
                    
                    if ($refreshPayload) {
                        // Generate new tokens
                        $newAccessToken = $this->jwtHandler->generateAccessToken([
                            'id' => $payload->id,
                            'email' => $payload->email,
                            'role' => $payload->role
                        ]);
                        
                        $newRefreshToken = $this->jwtHandler->generateRefreshToken($payload->id);
                        
                        // Set new refresh token cookie
                        $this->jwtHandler->setRefreshTokenCookie($newRefreshToken);
                        
                        // Blacklist old tokens
                        $this->jwtHandler->blacklistToken($accessToken);
                        $this->jwtHandler->blacklistToken($refreshToken);
                        
                        return [
                            'status' => 'refresh',
                            'message' => 'Token has been refreshed',
                            'access_token' => $newAccessToken,
                            'expires_in' => JWT_ACCESS_TOKEN_EXPIRY
                        ];
                    }
                }
            }

            // Token is still valid
            return [
                'status' => 'valid',
                'message' => 'Token is valid',
                'expires_in' => $exp - $now
            ];

        } catch (Exception $e) {
            error_log("Token validation error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Token validation failed',
                'code' => 401
            ];
        }
    }

    /**
     * Log token related events
     */
    private function logTokenEvent($event, $userId = null, $status = 'info') {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'user_id' => $userId,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ];

        error_log(
            sprintf(
                "[%s] %s - User: %s, IP: %s, UA: %s",
                strtoupper($status),
                $event,
                $userId ?? 'Unknown',
                $logEntry['ip'],
                $logEntry['user_agent']
            ),
            3,
            SECURITY_LOG_PATH
        );
    }
} 