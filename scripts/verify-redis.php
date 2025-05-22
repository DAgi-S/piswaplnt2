<?php
/**
 * Redis Verification Script
 * Tests Redis connection and configuration for rate limiting
 */

require_once __DIR__ . '/../config/security.php';

echo "Redis Verification Script\n";
echo "------------------------\n\n";

// Check Redis PHP extension
echo "1. Checking Redis PHP extension... ";
if (extension_loaded('redis')) {
    echo "✓ Installed\n";
} else {
    echo "✗ Not installed\n";
    echo "Please install the Redis PHP extension:\n";
    echo "- Windows: Download from https://pecl.php.net/package/redis\n";
    echo "- Linux: sudo apt-get install php-redis\n";
    exit(1);
}

// Test Redis connection
echo "\n2. Testing Redis connection... ";
try {
    $redis = new Redis();
    $redis->connect(
        REDIS_HOST,
        REDIS_PORT,
        REDIS_TIMEOUT,
        null,
        0,
        0,
        ['auth' => REDIS_PASSWORD]
    );
    
    $redis->ping();
    echo "✓ Connected successfully\n";
    
    // Test authentication
    echo "   - Authentication: ";
    if (REDIS_PASSWORD) {
        $redis->auth(REDIS_PASSWORD);
        echo "✓ Authenticated\n";
    } else {
        echo "! No password set (not recommended for production)\n";
    }
    
    // Test basic operations
    echo "   - Basic operations: ";
    $testKey = REDIS_PREFIX . 'test_key';
    $redis->set($testKey, 'test_value');
    $value = $redis->get($testKey);
    $redis->del($testKey);
    
    if ($value === 'test_value') {
        echo "✓ Working\n";
    } else {
        echo "✗ Failed\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "✗ Failed\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test rate limiting
echo "\n3. Testing rate limiting... ";
try {
    require_once __DIR__ . '/../includes/jwt/RateLimitMiddleware.php';
    $rateLimiter = new RateLimitMiddleware(3, 1); // 3 attempts per minute
    
    $identifier = 'test_user_' . time();
    
    // Test successful requests
    $success = true;
    for ($i = 0; $i < 3; $i++) {
        $result = $rateLimiter->handle($identifier);
        if (!$result) {
            $success = false;
            break;
        }
    }
    
    // Test rate limit exceeded
    try {
        $rateLimiter->handle($identifier);
        $success = false;
    } catch (Exception $e) {
        if ($e->getMessage() !== JWT_ERROR_RATE_LIMIT) {
            $success = false;
        }
    }
    
    if ($success) {
        echo "✓ Working\n";
    } else {
        echo "✗ Failed\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "✗ Failed\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test Redis memory usage
echo "\n4. Checking Redis memory usage... ";
$info = $redis->info();
$usedMemory = $info['used_memory_human'];
$maxMemory = $info['maxmemory_human'] ?: 'no limit';
echo "✓ Using $usedMemory (Max: $maxMemory)\n";

// Test Redis persistence
echo "\n5. Checking Redis persistence... ";
$config = $redis->config('GET', 'save');
if (!empty($config['save'])) {
    echo "✓ Persistence enabled\n";
    echo "   Save configuration: " . $config['save'] . "\n";
} else {
    echo "! Persistence disabled (not recommended for production)\n";
}

// Final status
echo "\nFinal Status: ";
echo "✓ Redis is properly configured for rate limiting\n\n";

// Recommendations
echo "Recommendations:\n";
echo "---------------\n";
if (!REDIS_PASSWORD) {
    echo "! Set a strong Redis password in production\n";
}
if ($maxMemory === 'no limit') {
    echo "! Set a memory limit to prevent excessive memory usage\n";
}
if (empty($config['save'])) {
    echo "! Enable persistence to prevent data loss on restart\n";
}

// Cleanup
$redis->close(); 