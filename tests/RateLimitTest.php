<?php
/**
 * Rate Limiting Test Cases
 * Tests rate limiting functionality with both Redis and file-based fallback
 */

namespace Tests;

use PHPUnit\Framework\TestCase;
use RateLimitMiddleware;

class RateLimitTest extends TestCase {
    private $middleware;
    private $redis;

    protected function setUp(): void {
        parent::setUp();
        require_once __DIR__ . '/../config/security.php';
        require_once __DIR__ . '/../includes/jwt/RateLimitMiddleware.php';
        
        // Clear any existing rate limits
        $this->cleanupRateLimits();
        
        if (!defined('PHPUNIT_RUNNING')) {
            define('PHPUNIT_RUNNING', true);
        }
        $this->middleware = new RateLimitMiddleware();
    }

    protected function tearDown(): void {
        parent::tearDown();
        $this->cleanupRateLimits();
    }

    private function cleanupRateLimits() {
        // Clean up Redis if available
        try {
            if (class_exists('Redis')) {
                $redis = new \Redis();
                $redis->connect(REDIS_HOST, REDIS_PORT);
                if (REDIS_PASSWORD) {
                    $redis->auth(REDIS_PASSWORD);
                }
                $redis->del('rate_limit:test_user');
            }
        } catch (\Exception $e) {
            // Redis not available, ignore
        }

        // Clean up file-based rate limits
        $path = sys_get_temp_dir() . '/rate_limits';
        if (file_exists($path)) {
            $files = glob($path . '/*');
            foreach ($files as $file) {
                @unlink($file);
            }
        }
    }

    /**
     * Test basic rate limiting functionality
     */
    public function testBasicRateLimiting() {
        $identifier = 'test_user';

        // First three requests should succeed
        for ($i = 0; $i < 3; $i++) {
            $result = $this->middleware->checkRateLimit($identifier);
            $this->assertTrue($result);
        }

        // Fourth request should fail
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(JWT_ERROR_RATE_LIMIT);
        $this->middleware->checkRateLimit($identifier);
    }

    /**
     * Test rate limit reset after window
     */
    public function testRateLimitReset() {
        $identifier = 'test_user';

        // Use up all attempts
        for ($i = 0; $i < 3; $i++) {
            $this->middleware->checkRateLimit($identifier);
        }

        // Wait for rate limit window to expire
        sleep(61);

        // Should be able to make requests again
        $result = $this->middleware->checkRateLimit($identifier);
        $this->assertTrue($result);
    }

    /**
     * Test rate limiting with different identifiers
     */
    public function testDifferentIdentifiers() {
        // First user uses up their limit
        $identifier1 = 'test_user_1';
        for ($i = 0; $i < 3; $i++) {
            $this->middleware->checkRateLimit($identifier1);
        }

        // Second user should still be able to make requests
        $identifier2 = 'test_user_2';
        $result = $this->middleware->checkRateLimit($identifier2);
        $this->assertTrue($result);
    }

    /**
     * Test rate limit headers
     */
    public function testRateLimitHeaders() {
        $identifier = 'test_user';

        // Make a request and check headers
        $this->middleware->checkRateLimit($identifier);
        
        $headers = $this->middleware->getHeaders();
        
        $this->assertContains('X-RateLimit-Limit: 3', $headers);
        $this->assertContains('X-RateLimit-Remaining: 2', $headers);
        $this->assertMatchesRegularExpression('/X-RateLimit-Reset: \d+/', $headers[2]);
    }

    /**
     * Test file-based fallback
     */
    public function testFileFallback() {
        // Force file-based fallback by setting invalid Redis credentials
        putenv('REDIS_HOST=invalid_host');
        $middleware = new RateLimitMiddleware();
        
        $identifier = 'test_user';

        // First three requests should succeed
        for ($i = 0; $i < 3; $i++) {
            $result = $middleware->checkRateLimit($identifier);
            $this->assertTrue($result);
        }

        // Fourth request should fail
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(JWT_ERROR_RATE_LIMIT);
        $middleware->checkRateLimit($identifier);
    }

    /**
     * Test concurrent requests
     */
    public function testConcurrentRequests() {
        $identifier = 'test_user';
        $processes = [];

        // Simulate concurrent requests using multiple processes
        for ($i = 0; $i < 5; $i++) {
            $pid = pcntl_fork();
            if ($pid == -1) {
                $this->fail('Could not fork');
            } else if ($pid) {
                $processes[] = $pid;
            } else {
                try {
                    $this->middleware->checkRateLimit($identifier);
                } catch (\Exception $e) {
                    exit(1);
                }
                exit(0);
            }
        }

        // Wait for all processes and count failures
        $failures = 0;
        foreach ($processes as $pid) {
            pcntl_waitpid($pid, $status);
            if (pcntl_wexitstatus($status) == 1) {
                $failures++;
            }
        }

        // At least 2 requests should fail (as limit is 3)
        $this->assertGreaterThanOrEqual(2, $failures);
    }

    /**
     * Test rate limit cleanup
     */
    public function testRateLimitCleanup() {
        $path = sys_get_temp_dir() . '/rate_limits';
        
        // Create some expired rate limit files
        for ($i = 0; $i < 5; $i++) {
            $file = $path . '/expired_' . $i;
            file_put_contents($file, json_encode([
                'attempts' => 1,
                'timestamp' => time() - 3600 // 1 hour ago
            ]));
            touch($file, time() - 3600);
        }

        // Create some current rate limit files
        for ($i = 0; $i < 5; $i++) {
            $file = $path . '/current_' . $i;
            file_put_contents($file, json_encode([
                'attempts' => 1,
                'timestamp' => time()
            ]));
        }

        // Trigger cleanup by making multiple requests
        for ($i = 0; $i < 20; $i++) {
            try {
                $this->middleware->checkRateLimit('cleanup_test_' . $i);
            } catch (\Exception $e) {
                // Ignore rate limit exceptions
            }
        }

        // Check that expired files were cleaned up
        $files = glob($path . '/expired_*');
        $this->assertEmpty($files, 'Expired rate limit files should be cleaned up');

        // Check that current files still exist
        $files = glob($path . '/current_*');
        $this->assertCount(5, $files, 'Current rate limit files should remain');
    }

    public function testRateLimitExceeded() {
        $type = 'test';
        define('RATE_LIMIT_ATTEMPTS', ['test' => 2]);
        define('RATE_LIMIT_WINDOWS', ['test' => 60]);
        
        // First request - should pass
        $this->assertTrue($this->middleware->checkRateLimit($type));
        $headers = $this->middleware->getHeaders();
        $this->assertEquals(2, $headers['X-RateLimit-Limit']);
        $this->assertEquals(1, $headers['X-RateLimit-Remaining']);
        
        // Second request - should pass
        $this->assertTrue($this->middleware->checkRateLimit($type));
        $headers = $this->middleware->getHeaders();
        $this->assertEquals(2, $headers['X-RateLimit-Limit']);
        $this->assertEquals(0, $headers['X-RateLimit-Remaining']);
        
        // Third request - should fail
        $this->assertFalse($this->middleware->checkRateLimit($type));
        $headers = $this->middleware->getHeaders();
        $this->assertEquals(2, $headers['X-RateLimit-Limit']);
        $this->assertEquals(0, $headers['X-RateLimit-Remaining']);
    }
    
    public function testCleanupExpiredLimits() {
        $cleaned = $this->middleware->cleanupExpiredLimits();
        $this->assertIsInt($cleaned);
    }
} 