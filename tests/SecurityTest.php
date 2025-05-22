<?php
/**
 * Security Test Cases
 * Tests various security measures and configurations
 */

namespace Tests;

use PHPUnit\Framework\TestCase;

class SecurityTest extends TestCase {
    private $jwt;
    private $csrf;
    private $rateLimiter;
    
    protected function setUp(): void {
        parent::setUp();
        if (!defined('PHPUNIT_RUNNING')) {
            define('PHPUNIT_RUNNING', true);
        }
        
        // Initialize middleware
        $this->jwt = new \JWTMiddleware();
        $this->csrf = new \CSRFMiddleware();
        $this->rateLimiter = new \RateLimitMiddleware();
        
        // Set up test constants if not defined
        if (!defined('RATE_LIMIT_ATTEMPTS')) {
            define('RATE_LIMIT_ATTEMPTS', ['test' => 2]);
        }
        if (!defined('RATE_LIMIT_WINDOWS')) {
            define('RATE_LIMIT_WINDOWS', ['test' => 60]);
        }
    }
    
    public function testUnauthorizedAccess() {
        $response = $this->makeRequest('/api/v2/protected/resource', 'GET');
        $this->assertEquals(401, $response['status']);
    }
    
    public function testInvalidToken() {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer invalid.token.here';
        $response = $this->makeRequest('/api/v2/protected/resource', 'GET');
        $this->assertEquals(401, $response['status']);
    }
    
    public function testValidToken() {
        $payload = ['user_id' => 1, 'username' => 'test'];
        $token = $this->jwt->generateToken($payload);
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        $response = $this->makeRequest('/api/v2/protected/resource', 'GET');
        $this->assertEquals(200, $response['status']);
    }
    
    public function testCSRFProtection() {
        // Test without CSRF token
        $response = $this->makeRequest('/api/v2/protected/resource', 'POST');
        $this->assertEquals(403, $response['status']);
        
        // Test with valid CSRF token
        $token = $this->csrf->generateToken();
        $_COOKIE['csrf_token'] = $token;
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;
        $response = $this->makeRequest('/api/v2/protected/resource', 'POST');
        $this->assertEquals(200, $response['status']);
    }
    
    public function testRateLimiting() {
        $type = 'test';
        
        // First request should pass
        $this->assertTrue($this->rateLimiter->checkRateLimit($type));
        $headers = $this->rateLimiter->getHeaders();
        $this->assertEquals(2, $headers['X-RateLimit-Limit']);
        
        // Exhaust rate limit
        $this->rateLimiter->checkRateLimit($type);
        
        // Next request should fail
        $this->assertFalse($this->rateLimiter->checkRateLimit($type));
        $headers = $this->rateLimiter->getHeaders();
        $this->assertEquals(0, $headers['X-RateLimit-Remaining']);
    }
    
    public function testSecurityHeaders() {
        $response = $this->makeRequest('/api/v2/auth/login', 'GET');
        $headers = $response['headers'];
        
        $this->assertArrayHasKey('X-Content-Type-Options', $headers);
        $this->assertArrayHasKey('X-Frame-Options', $headers);
        $this->assertArrayHasKey('X-XSS-Protection', $headers);
        $this->assertArrayHasKey('Content-Security-Policy', $headers);
        $this->assertArrayHasKey('Strict-Transport-Security', $headers);
    }
    
    private function makeRequest($path, $method = 'GET') {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $path;
        
        ob_start();
        include __DIR__ . '/../public' . $path;
        $output = ob_get_clean();
        
        $response = [
            'status' => http_response_code(),
            'headers' => $this->getResponseHeaders(),
            'body' => $output
        ];
        
        return $response;
    }
    
    private function getResponseHeaders() {
        $headers = [];
        foreach (headers_list() as $header) {
            $parts = explode(':', $header, 2);
            if (count($parts) === 2) {
                $headers[trim($parts[0])] = trim($parts[1]);
            }
        }
        return $headers;
    }
} 