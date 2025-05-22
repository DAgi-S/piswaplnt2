<?php
/**
 * Rate Limiting Middleware
 * Implements rate limiting for API endpoints using Redis
 */

class RateLimitMiddleware {
    private $redis;
    private $maxAttempts;
    private $decayMinutes;
    private $prefix = 'rate_limit:';

    /**
     * Initialize rate limiting middleware
     */
    public function __construct($maxAttempts = null, $decayMinutes = null) {
        $this->maxAttempts = $maxAttempts ?? JWT_RATE_LIMIT_ATTEMPTS;
        $this->decayMinutes = $decayMinutes ?? JWT_RATE_LIMIT_MINUTES;
        
        // Initialize Redis connection
        $this->redis = new Redis();
        try {
            $this->redis->connect('127.0.0.1', 6379);
        } catch (Exception $e) {
            // Fallback to file-based storage if Redis is not available
            error_log('Redis connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle rate limiting
     */
    public function handle($identifier = null) {
        // Get client identifier (IP address if not provided)
        $identifier = $identifier ?? $this->getClientIdentifier();
        $key = $this->prefix . $identifier;

        try {
            if ($this->redis->isConnected()) {
                return $this->handleRedis($key);
            } else {
                return $this->handleFileSystem($key);
            }
        } catch (Exception $e) {
            error_log('Rate limiting error: ' . $e->getMessage());
            return true; // Allow request on error
        }
    }

    /**
     * Handle rate limiting using Redis
     */
    private function handleRedis($key) {
        $current = $this->redis->get($key);
        
        if (!$current) {
            $this->redis->setex($key, $this->decayMinutes * 60, 1);
            return true;
        }

        if ($current >= $this->maxAttempts) {
            $this->setRateLimitHeaders($current);
            throw new Exception(JWT_ERROR_RATE_LIMIT);
        }

        $this->redis->incr($key);
        $this->setRateLimitHeaders($current + 1);
        return true;
    }

    /**
     * Handle rate limiting using filesystem
     */
    private function handleFileSystem($key) {
        $file = sys_get_temp_dir() . '/' . md5($key);
        
        if (!file_exists($file)) {
            file_put_contents($file, '1|' . time());
            return true;
        }

        list($attempts, $timestamp) = explode('|', file_get_contents($file));
        
        // Reset if time window has passed
        if (time() - $timestamp >= $this->decayMinutes * 60) {
            file_put_contents($file, '1|' . time());
            return true;
        }

        if ($attempts >= $this->maxAttempts) {
            $this->setRateLimitHeaders($attempts);
            throw new Exception(JWT_ERROR_RATE_LIMIT);
        }

        file_put_contents($file, (intval($attempts) + 1) . '|' . $timestamp);
        $this->setRateLimitHeaders(intval($attempts) + 1);
        return true;
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
                $ip = $_SERVER[$header];
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * Set rate limit headers
     */
    private function setRateLimitHeaders($current) {
        header('X-RateLimit-Limit: ' . $this->maxAttempts);
        header('X-RateLimit-Remaining: ' . max(0, $this->maxAttempts - $current));
        header('X-RateLimit-Reset: ' . (time() + $this->decayMinutes * 60));
    }
} 