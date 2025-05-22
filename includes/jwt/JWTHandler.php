<?php
/**
 * JWT Handler Class
 * Manages all JWT operations including generation, validation, and refresh
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTHandler {
    private static $instance = null;
    private $blacklistedTokens = [];

    /**
     * Singleton pattern implementation
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Generate access token
     */
    public function generateAccessToken($userData) {
        $issuedAt = time();
        $expire = $issuedAt + JWT_ACCESS_TOKEN_EXPIRY;

        $payload = [
            'iss' => JWT_ISSUER,
            'aud' => JWT_AUDIENCE,
            'iat' => $issuedAt,
            'exp' => $expire,
            'data' => $userData
        ];

        return JWT::encode($payload, JWT_SECRET_KEY, JWT_ALGORITHM);
    }

    /**
     * Generate refresh token
     */
    public function generateRefreshToken($userId) {
        $issuedAt = time();
        $expire = $issuedAt + JWT_REFRESH_TOKEN_EXPIRY;

        $payload = [
            'iss' => JWT_ISSUER,
            'aud' => JWT_AUDIENCE,
            'iat' => $issuedAt,
            'exp' => $expire,
            'data' => [
                'user_id' => $userId
            ]
        ];

        return JWT::encode($payload, JWT_REFRESH_KEY, JWT_ALGORITHM);
    }

    /**
     * Validate access token
     */
    public function validateAccessToken($token) {
        try {
            if ($this->isTokenBlacklisted($token)) {
                throw new Exception(JWT_ERROR_INVALID_TOKEN);
            }

            $decoded = JWT::decode($token, new Key(JWT_SECRET_KEY, JWT_ALGORITHM));
            
            // Additional validation
            if (!isset($decoded->iss) || $decoded->iss !== JWT_ISSUER ||
                !isset($decoded->aud) || $decoded->aud !== JWT_AUDIENCE ||
                !isset($decoded->exp) || $decoded->exp < time() ||
                !isset($decoded->data->id) || !isset($decoded->data->email) || !isset($decoded->data->role)) {
                throw new Exception(JWT_ERROR_INVALID_TOKEN);
            }

            return $decoded;
        } catch (\Firebase\JWT\ExpiredException $e) {
            http_response_code(401);
            throw new Exception(JWT_ERROR_EXPIRED_TOKEN);
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            http_response_code(401);
            throw new Exception(JWT_ERROR_INVALID_SIGNATURE);
        } catch (Exception $e) {
            http_response_code(401);
            throw new Exception(JWT_ERROR_INVALID_TOKEN);
        }
    }

    /**
     * Validate refresh token
     */
    public function validateRefreshToken($token) {
        try {
            return JWT::decode($token, new Key(JWT_REFRESH_KEY, JWT_ALGORITHM));
        } catch (Exception $e) {
            throw new Exception(JWT_ERROR_INVALID_TOKEN);
        }
    }

    /**
     * Check if user has required permission
     */
    public function hasPermission($userData, $requiredPermission) {
        global $JWT_ROLE_PERMISSIONS;
        
        $userRole = $userData->data->role;
        
        // Admin has all permissions
        if ($userRole === 'admin') {
            return true;
        }

        // Check specific permission
        return isset($JWT_ROLE_PERMISSIONS[$userRole][$requiredPermission]) && 
               $JWT_ROLE_PERMISSIONS[$userRole][$requiredPermission] === true;
    }

    /**
     * Blacklist a token
     */
    public function blacklistToken($token) {
        $this->blacklistedTokens[] = $token;
    }

    /**
     * Check if token is blacklisted
     */
    private function isTokenBlacklisted($token) {
        return in_array($token, $this->blacklistedTokens);
    }

    /**
     * Set refresh token cookie
     */
    public function setRefreshTokenCookie($refreshToken) {
        setcookie(
            JWT_REFRESH_COOKIE_NAME,
            $refreshToken,
            [
                'expires' => time() + JWT_REFRESH_TOKEN_EXPIRY,
                'path' => JWT_REFRESH_COOKIE_PATH,
                'secure' => JWT_REFRESH_COOKIE_SECURE,
                'httponly' => JWT_REFRESH_COOKIE_HTTPONLY,
                'samesite' => JWT_REFRESH_COOKIE_SAMESITE
            ]
        );
    }

    /**
     * Clear refresh token cookie
     */
    public function clearRefreshTokenCookie() {
        setcookie(
            JWT_REFRESH_COOKIE_NAME,
            '',
            [
                'expires' => time() - 3600,
                'path' => JWT_REFRESH_COOKIE_PATH,
                'secure' => JWT_REFRESH_COOKIE_SECURE,
                'httponly' => JWT_REFRESH_COOKIE_HTTPONLY,
                'samesite' => JWT_REFRESH_COOKIE_SAMESITE
            ]
        );
    }

    /**
     * Get user permissions
     */
    public function getUserPermissions($role) {
        global $JWT_ROLE_PERMISSIONS;
        
        if (!isset($JWT_ROLE_PERMISSIONS[$role])) {
            return [];
        }

        if ($role === 'admin') {
            return ['all'];
        }

        return array_keys(array_filter($JWT_ROLE_PERMISSIONS[$role], function($value) {
            return $value === true;
        }));
    }
} 