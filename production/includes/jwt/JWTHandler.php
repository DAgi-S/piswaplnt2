<?php
/**
 * JWT Handler Class
 * Manages all JWT operations including generation, validation, and refresh
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/jwt/config.php';

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

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
            'data' => [
                'user_id' => $userData['id'],
                'email' => $userData['email'],
                'role' => $userData['role']
            ]
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
            return $decoded;
        } catch (\Firebase\JWT\ExpiredException $e) {
            throw new Exception(JWT_ERROR_EXPIRED_TOKEN);
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            throw new Exception(JWT_ERROR_INVALID_SIGNATURE);
        } catch (Exception $e) {
            throw new Exception(JWT_ERROR_INVALID_TOKEN);
        }
    }

    /**
     * Validate refresh token
     */
    public function validateRefreshToken($token) {
        try {
            $decoded = JWT::decode($token, new Key(JWT_REFRESH_KEY, JWT_ALGORITHM));
            return $decoded;
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
        if ($userRole === 'admin' || 
            isset($JWT_ROLE_PERMISSIONS[$userRole]['all']) && 
            $JWT_ROLE_PERMISSIONS[$userRole]['all'] === true) {
            return true;
        }

        // Check specific permission
        return isset($JWT_ROLE_PERMISSIONS[$userRole][$requiredPermission]) && 
               $JWT_ROLE_PERMISSIONS[$userRole][$requiredPermission] === true;
    }

    /**
     * Blacklist a token (for logout)
     */
    public function blacklistToken($token) {
        $this->blacklistedTokens[] = $token;
        // In production, store this in Redis or database
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
                'domain' => $_SERVER['HTTP_HOST'],
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
                'domain' => $_SERVER['HTTP_HOST'],
                'secure' => JWT_REFRESH_COOKIE_SECURE,
                'httponly' => JWT_REFRESH_COOKIE_HTTPONLY,
                'samesite' => JWT_REFRESH_COOKIE_SAMESITE
            ]
        );
    }

    /**
     * Get user permissions based on role
     */
    public function getUserPermissions($role) {
        global $JWT_ROLE_PERMISSIONS;
        
        if (!isset($JWT_ROLE_PERMISSIONS[$role])) {
            return [];
        }

        // If role has all permissions
        if (isset($JWT_ROLE_PERMISSIONS[$role]['all']) && 
            $JWT_ROLE_PERMISSIONS[$role]['all'] === true) {
            // Collect all possible permissions from all roles
            $allPermissions = [];
            foreach ($JWT_ROLE_PERMISSIONS as $rolePerms) {
                foreach ($rolePerms as $perm => $value) {
                    if ($perm !== 'all' && $value === true) {
                        $allPermissions[$perm] = true;
                    }
                }
            }
            return array_keys($allPermissions);
        }

        // Return permissions where value is true
        return array_keys(array_filter($JWT_ROLE_PERMISSIONS[$role], function($value) {
            return $value === true;
        }));
    }
} 