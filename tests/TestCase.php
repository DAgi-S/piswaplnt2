<?php
/**
 * Base Test Case
 * Provides common functionality for all test cases
 */

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use PDO;
use PDOException;
use Firebase\JWT\JWT;

abstract class TestCase extends BaseTestCase
{
    protected $db;
    protected $redis;
    protected $jwtHandler;
    protected $testUser;
    protected $testRunner;

    protected function setUp(): void
    {
        parent::setUp();

        // Initialize test user data
        $this->testUser = [
            'id' => 1,
            'email' => 'test@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'role' => 'admin',
            'status' => 'active'
        ];

        // Set up database mock
        $this->db = \TestDatabase::getInstance();
        $this->db->setMockData([$this->testUser]);

        // Set up Redis mock
        $this->redis = new \TestRedis();

        // Initialize JWT handler
        $this->jwtHandler = new \JWTHandler();

        // Initialize test runner
        $this->testRunner = TestRunner::getInstance();
    }

    protected function executeEndpoint($path) {
        return $this->testRunner->runEndpoint($path);
    }

    protected function createRequest($method, $endpoint, $data = null, $headers = []) {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $endpoint;
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        // Set default headers
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Test Client';

        // Set custom headers
        foreach ($headers as $key => $value) {
            $_SERVER['HTTP_' . str_replace('-', '_', strtoupper($key))] = $value;
        }

        // Set input data
        if ($data !== null) {
            $GLOBALS['HTTP_RAW_POST_DATA'] = json_encode($data);
        }
    }

    protected function getJsonResponse() {
        return json_decode($this->testRunner->getOutputBuffer(), true);
    }

    protected function assertResponseStatus($expectedStatus) {
        $this->assertEquals($expectedStatus, $this->testRunner->getResponseCode());
    }

    protected function assertJsonStructure($expected, $actual) {
        foreach ($expected as $key => $value) {
            if (is_array($value)) {
                $this->assertArrayHasKey($key, $actual);
                $this->assertJsonStructure($value, $actual[$key]);
            } else {
                $this->assertArrayHasKey($value, $actual);
            }
        }
    }

    protected function generateTestToken($user = null) {
        $user = $user ?? $this->testUser;
        return $this->jwtHandler->generateAccessToken([
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role']
        ]);
    }

    protected function generateTestRefreshToken($userId = null) {
        $userId = $userId ?? $this->testUser['id'];
        return $this->jwtHandler->generateRefreshToken($userId);
    }

    protected function getHeader($name) {
        return $this->testRunner->getHeader($name);
    }

    protected function assertHeaderContains($name, $value) {
        $header = $this->getHeader($name);
        $this->assertNotNull($header, "Header '$name' not found");
        $this->assertStringContainsString($value, $header);
    }

    protected function assertHeaderNotSet($name) {
        $header = $this->getHeader($name);
        $this->assertNull($header, "Header '$name' should not be set");
    }

    protected function assertSecurityHeaders() {
        foreach (SECURITY_HEADERS as $header => $value) {
            $this->assertHeaderContains($header, $value);
        }
    }

    protected function assertCorsHeaders($origin = null) {
        if ($origin && in_array($origin, JWT_ALLOWED_ORIGINS)) {
            $this->assertHeaderContains('Access-Control-Allow-Origin', $origin);
            $this->assertHeaderContains('Access-Control-Allow-Credentials', 'true');
            $this->assertHeaderContains('Vary', 'Origin');
        } else {
            $this->assertHeaderNotSet('Access-Control-Allow-Origin');
        }
    }

    protected function assertCookieExists($name, $options = []) {
        $cookie = $this->testRunner->getCookie($name);
        $this->assertNotNull($cookie, "Cookie '$name' not found");
        foreach ($options as $key => $value) {
            $this->assertEquals($value, $cookie[$key], "Cookie option '$key' does not match");
        }
    }

    protected function assertCookieNotExists($name) {
        $cookie = $this->testRunner->getCookie($name);
        $this->assertNull($cookie, "Cookie '$name' should not be set");
    }
} 