<?php
/**
 * Rate Limiting Middleware
 * Implements rate limiting with Redis and secure file-based fallback
 */

class RateLimitMiddleware {
    private $redis;
    private $useRedis;
    private $maxAttempts;
    private $decayMinutes;
    private $prefix = 'rate_limit:';
    private $storage_path;
    private $headers = [];
    private $isTest;

    /**
     * Initialize rate limiting middleware
     */
    public function __construct($maxAttempts = null, $decayMinutes = null) {
        // Load configuration
        require_once __DIR__ . '/../../config/security.php';
        
        $this->maxAttempts = $maxAttempts ?? RATE_LIMIT_ATTEMPTS['api'];
        $this->decayMinutes = $decayMinutes ?? RATE_LIMIT_WINDOWS['api'];
        $this->storage_path = sys_get_temp_dir() . '/rate_limits';
        
        // Create storage directory if it doesn't exist
        if (!file_exists($this->storage_path)) {
            mkdir($this->storage_path, 0755, true);
        }

        $this->isTest = defined('PHPUNIT_RUNNING') && PHPUNIT_RUNNING === true;
        $this->useRedis = extension_loaded('redis');
        
        if ($this->useRedis) {
            $this->redis = new Redis();
            try {
                $this->redis->connect(
                    getenv('REDIS_HOST') ?: '127.0.0.1',
                    getenv('REDIS_PORT') ?: 6379,
                    1.0, // 1 second timeout
                    null,
                    0,
                    0,
                    ['auth' => getenv('REDIS_PASSWORD')]
                );
                
                // Test connection
                $this->redis->ping();
            } catch (Exception $e) {
                error_log('Redis connection failed, using file-based rate limiting: ' . $e->getMessage());
                $this->useRedis = false;
            }
        }
    }

    /**
     * Handle rate limiting
     */
    public function handle($identifier = null) {
        $identifier = $identifier ?? $this->getClientIdentifier();
        $key = $this->prefix . md5($identifier);

        try {
            return $this->useRedis ? 
                   $this->handleRedis($key) : 
                   $this->handleFileSystem($key);
        } catch (Exception $e) {
            error_log('Rate limiting error: ' . $e->getMessage());
            
            // Only allow requests to proceed on non-critical errors
            if ($e->getMessage() === JWT_ERROR_RATE_LIMIT) {
                throw $e;
            }
            return true;
        }
    }

    /**
     * Handle rate limiting using Redis
     */
    private function handleRedis($key) {
        $current = $this->redis->get($key);
        
        if (!$current) {
            $this->redis->setex($key, $this->decayMinutes * 60, 1);
            $this->setRateLimitHeaders(1);
            return true;
        }

        if ($current >= $this->maxAttempts) {
            $ttl = $this->redis->ttl($key);
            $this->setRateLimitHeaders($current, $ttl);
            http_response_code(429);
            throw new Exception(JWT_ERROR_RATE_LIMIT);
        }

        $this->redis->incr($key);
        $ttl = $this->redis->ttl($key);
        $this->setRateLimitHeaders($current + 1, $ttl);
        return true;
    }

    /**
     * Handle rate limiting using filesystem
     */
    private function handleFileSystem($key) {
        $file = $this->storage_path . '/' . $key;
        
        if (!file_exists($file)) {
            file_put_contents($file, json_encode([
                'attempts' => 1,
                'timestamp' => time()
            ]), LOCK_EX);
            $this->setRateLimitHeaders(1);
            return true;
        }

        $data = json_decode(file_get_contents($file), true);
        
        // Reset if time window has passed
        if (time() - $data['timestamp'] >= $this->decayMinutes * 60) {
            $data = [
                'attempts' => 1,
                'timestamp' => time()
            ];
            file_put_contents($file, json_encode($data), LOCK_EX);
            $this->setRateLimitHeaders(1);
            return true;
        }

        if ($data['attempts'] >= $this->maxAttempts) {
            $resetTime = $data['timestamp'] + ($this->decayMinutes * 60);
            $this->setRateLimitHeaders($data['attempts'], $resetTime - time());
            http_response_code(429);
            throw new Exception(JWT_ERROR_RATE_LIMIT);
        }

        $data['attempts']++;
        file_put_contents($file, json_encode($data), LOCK_EX);
        $this->setRateLimitHeaders($data['attempts']);
        
        // Cleanup old files
        $this->cleanupOldFiles();
        
        return true;
    }

    /**
     * Clean up old rate limit files
     */
    private function cleanupOldFiles() {
        if (rand(1, 100) <= 5) { // 5% chance to run cleanup
            $files = glob($this->storage_path . '/*');
            $now = time();
            foreach ($files as $file) {
                if ($now - filemtime($file) >= $this->decayMinutes * 60) {
                    @unlink($file);
                }
            }
        }
    }

    /**
     * Get client identifier (IP address)
     */
    private function getClientIdentifier() {
        $headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (array_key_exists($header, $_SERVER)) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim(reset($ips)); // Get first IP in list
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * Set rate limit headers
     */
    private function setRateLimitHeaders($current, $resetTime = null) {
        if ($resetTime === null) {
            $resetTime = time() + ($this->decayMinutes * 60);
        }
        
        header('X-RateLimit-Limit: ' . $this->maxAttempts);
        header('X-RateLimit-Remaining: ' . max(0, $this->maxAttempts - $current));
        header('X-RateLimit-Reset: ' . $resetTime);
        
        if ($current >= $this->maxAttempts) {
            header('Retry-After: ' . $resetTime);
        }
    }

    public function getHeaders() {
        return $this->headers;
    }

    public function cleanupExpiredLimits() {
        if ($this->useRedis) {
            // Redis handles cleanup automatically
            return true;
        }
        
        $tempDir = sys_get_temp_dir();
        $files = glob($tempDir . '/rate_limit_*');
        $now = time();
        $cleaned = 0;
        
        foreach ($files as $file) {
            if (!file_exists($file)) continue;
            
            $data = json_decode(file_get_contents($file), true);
            if ($data && isset($data['expires']) && $data['expires'] < $now) {
                unlink($file);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }

    public function checkRateLimit($type) {
        if (!isset(RATE_LIMIT_ATTEMPTS[$type]) || !isset(RATE_LIMIT_WINDOWS[$type])) {
            return true;
        }
        
        $this->maxAttempts = RATE_LIMIT_ATTEMPTS[$type];
        $this->decayMinutes = RATE_LIMIT_WINDOWS[$type];
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $key = $this->prefix . $type . ':' . md5($ip);
        
        if ($this->useRedis) {
            return $this->checkRedisRateLimit($key);
        } else {
            return $this->checkFileRateLimit($key);
        }
    }
    
    private function checkRedisRateLimit($key) {
        $attempts = $this->redis->get($key);
        
        if (!$attempts) {
            $this->redis->setex($key, $this->decayMinutes * 60, 1);
            $this->setRateLimitHeaders($this->maxAttempts, $this->maxAttempts - 1, time() + $this->decayMinutes * 60);
            return true;
        }
        
        if ($attempts >= $this->maxAttempts) {
            $ttl = $this->redis->ttl($key);
            $this->setRateLimitHeaders($this->maxAttempts, 0, time() + $ttl);
            return false;
        }
        
        $this->redis->incr($key);
        $ttl = $this->redis->ttl($key);
        $this->setRateLimitHeaders($this->maxAttempts, $this->maxAttempts - $attempts - 1, time() + $ttl);
        return true;
    }
    
    private function checkFileRateLimit($key) {
        $file = sys_get_temp_dir() . '/' . md5($key);
        
        if (!file_exists($file)) {
            file_put_contents($file, json_encode([
                'attempts' => 1,
                'expires' => time() + $this->decayMinutes * 60
            ]), LOCK_EX);
            $this->setRateLimitHeaders($this->maxAttempts, $this->maxAttempts - 1, time() + $this->decayMinutes * 60);
            return true;
        }
        
        $data = json_decode(file_get_contents($file), true);
        
        if (time() > $data['expires']) {
            $data = [
                'attempts' => 1,
                'expires' => time() + $this->decayMinutes * 60
            ];
            file_put_contents($file, json_encode($data), LOCK_EX);
            $this->setRateLimitHeaders($this->maxAttempts, $this->maxAttempts - 1, $data['expires']);
            return true;
        }
        
        if ($data['attempts'] >= $this->maxAttempts) {
            $this->setRateLimitHeaders($this->maxAttempts, 0, $data['expires']);
            return false;
        }
        
        $data['attempts']++;
        file_put_contents($file, json_encode($data), LOCK_EX);
        $this->setRateLimitHeaders($this->maxAttempts, $this->maxAttempts - $data['attempts'], $data['expires']);
        return true;
    }
    
    private function setRateLimitHeaders($limit, $remaining, $reset) {
        $headers = [
            'X-RateLimit-Limit' => $limit,
            'X-RateLimit-Remaining' => $remaining,
            'X-RateLimit-Reset' => $reset
        ];
        
        if ($this->isTest) {
            $this->headers = array_merge($this->headers, $headers);
        } else {
            foreach ($headers as $name => $value) {
                header("$name: $value");
            }
        }
    }
} 