<?php
namespace Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JWTMiddleware {
    private $secretKey;
    private $refreshKey;
    private $algorithm;
    
    public function __construct() {
        $this->secretKey = JWT_SECRET_KEY;
        $this->refreshKey = JWT_REFRESH_KEY;
        $this->algorithm = JWT_ALGORITHM;
    }
    
    public function generateToken($payload) {
        $issuedAt = time();
        $expire = $issuedAt + JWT_ACCESS_TOKEN_EXPIRY;
        
        $tokenPayload = array_merge($payload, [
            'iat' => $issuedAt,
            'exp' => $expire,
            'iss' => JWT_ISSUER,
            'aud' => JWT_AUDIENCE
        ]);
        
        return JWT::encode($tokenPayload, $this->secretKey, $this->algorithm);
    }
    
    public function generateRefreshToken($payload) {
        $issuedAt = time();
        $expire = $issuedAt + JWT_REFRESH_TOKEN_EXPIRY;
        
        $tokenPayload = array_merge($payload, [
            'iat' => $issuedAt,
            'exp' => $expire,
            'iss' => JWT_ISSUER,
            'aud' => JWT_AUDIENCE,
            'type' => 'refresh'
        ]);
        
        return JWT::encode($tokenPayload, $this->refreshKey, $this->algorithm);
    }
    
    public function validateToken($token = null, $isRefresh = false) {
        try {
            if (!$token) {
                $token = $this->getTokenFromHeader();
            }
            
            $key = $isRefresh ? $this->refreshKey : $this->secretKey;
            $decoded = JWT::decode($token, new Key($key, $this->algorithm));
            
            if ($decoded->iss !== JWT_ISSUER || $decoded->aud !== JWT_AUDIENCE) {
                return false;
            }
            
            if ($isRefresh && (!isset($decoded->type) || $decoded->type !== 'refresh')) {
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function getTokenFromHeader() {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }
        throw new Exception('No token found in request');
    }
    
    public function getPayload($token = null, $isRefresh = false) {
        try {
            if (!$token) {
                $token = $this->getTokenFromHeader();
            }
            
            $key = $isRefresh ? $this->refreshKey : $this->secretKey;
            return JWT::decode($token, new Key($key, $this->algorithm));
        } catch (Exception $e) {
            throw new Exception('Invalid token');
        }
    }
} 