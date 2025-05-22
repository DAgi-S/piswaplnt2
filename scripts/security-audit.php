<?php
/**
 * Security Audit Script
 * Performs comprehensive security checks on the system
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/jwt/config.php';

class SecurityAudit {
    private $results = [];
    private $errors = [];
    private $warnings = [];

    public function run() {
        echo "Starting Security Audit...\n\n";

        $this->checkJWTConfiguration()
             ->checkCORSSettings()
             ->checkCSRFProtection()
             ->checkRateLimiting()
             ->checkCookieSettings()
             ->checkWebSocketSecurity()
             ->checkTokenBlacklisting()
             ->checkPermissions()
             ->checkSecurityHeaders()
             ->checkDependencies();

        $this->printResults();
    }

    private function checkJWTConfiguration() {
        echo "Checking JWT Configuration...\n";

        // Check JWT secret keys
        if (!getenv('JWT_SECRET_KEY')) {
            $this->errors[] = "JWT_SECRET_KEY not set in environment";
        }
        if (!getenv('JWT_REFRESH_KEY')) {
            $this->errors[] = "JWT_REFRESH_KEY not set in environment";
        }

        // Check token expiry times
        if (JWT_ACCESS_TOKEN_EXPIRY > 3600) {
            $this->warnings[] = "Access token expiry time is longer than recommended (1 hour)";
        }
        if (JWT_REFRESH_TOKEN_EXPIRY > 604800) {
            $this->warnings[] = "Refresh token expiry time is longer than recommended (7 days)";
        }

        $this->results['jwt'] = "JWT configuration checked";
        return $this;
    }

    private function checkCORSSettings() {
        echo "Checking CORS Settings...\n";

        // Check allowed origins
        if (in_array('*', JWT_ALLOWED_ORIGINS)) {
            $this->errors[] = "Wildcard CORS origin (*) is not allowed";
        }

        foreach (JWT_ALLOWED_ORIGINS as $origin) {
            if (!filter_var($origin, FILTER_VALIDATE_URL)) {
                $this->errors[] = "Invalid CORS origin: $origin";
            }
            if (parse_url($origin, PHP_URL_SCHEME) !== 'https') {
                $this->warnings[] = "Non-HTTPS origin allowed: $origin";
            }
        }

        $this->results['cors'] = "CORS settings checked";
        return $this;
    }

    private function checkCSRFProtection() {
        echo "Checking CSRF Protection...\n";

        if (!JWT_CSRF_ENABLED) {
            $this->errors[] = "CSRF protection is disabled";
        }
        if (JWT_CSRF_TOKEN_LENGTH < 32) {
            $this->errors[] = "CSRF token length is too short (minimum 32 bytes)";
        }

        $this->results['csrf'] = "CSRF protection checked";
        return $this;
    }

    private function checkRateLimiting() {
        echo "Checking Rate Limiting...\n";

        // Check Redis connection
        try {
            $redis = new Redis();
            $redis->connect(REDIS_HOST, REDIS_PORT);
            if (REDIS_PASSWORD) {
                $redis->auth(REDIS_PASSWORD);
            }
            $redis->ping();
        } catch (Exception $e) {
            $this->errors[] = "Redis connection failed: " . $e->getMessage();
        }

        if (JWT_RATE_LIMIT_ATTEMPTS > 10) {
            $this->warnings[] = "Rate limit attempts threshold is high";
        }

        $this->results['rate_limiting'] = "Rate limiting checked";
        return $this;
    }

    private function checkCookieSettings() {
        echo "Checking Cookie Settings...\n";

        if (!JWT_REFRESH_COOKIE_SECURE) {
            $this->errors[] = "Refresh token cookies are not secure";
        }
        if (!JWT_REFRESH_COOKIE_HTTPONLY) {
            $this->errors[] = "Refresh token cookies are not HTTP-only";
        }
        if (JWT_REFRESH_COOKIE_SAMESITE !== 'Strict') {
            $this->warnings[] = "Cookie SameSite policy is not Strict";
        }

        $this->results['cookies'] = "Cookie settings checked";
        return $this;
    }

    private function checkWebSocketSecurity() {
        echo "Checking WebSocket Security...\n";

        if (!WS_SECURE) {
            $this->errors[] = "WebSocket connections are not secure (WSS)";
        }
        if (WS_HOST === '0.0.0.0') {
            $this->warnings[] = "WebSocket server listening on all interfaces";
        }

        $this->results['websocket'] = "WebSocket security checked";
        return $this;
    }

    private function checkTokenBlacklisting() {
        echo "Checking Token Blacklisting...\n";

        // Check if Redis is available for token blacklisting
        try {
            $redis = new Redis();
            $redis->connect(REDIS_HOST, REDIS_PORT);
            $redis->ping();
        } catch (Exception $e) {
            $this->warnings[] = "Token blacklisting may not work without Redis";
        }

        $this->results['blacklisting'] = "Token blacklisting checked";
        return $this;
    }

    private function checkPermissions() {
        echo "Checking Permissions System...\n";

        global $JWT_ROLE_PERMISSIONS;
        if (!isset($JWT_ROLE_PERMISSIONS) || empty($JWT_ROLE_PERMISSIONS)) {
            $this->errors[] = "Role permissions are not configured";
        }

        foreach ($JWT_ROLE_PERMISSIONS as $role => $permissions) {
            if ($role !== 'admin' && isset($permissions['all']) && $permissions['all'] === true) {
                $this->warnings[] = "Non-admin role '$role' has all permissions";
            }
        }

        $this->results['permissions'] = "Permissions system checked";
        return $this;
    }

    private function checkSecurityHeaders() {
        echo "Checking Security Headers...\n";

        $headers = [
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'X-XSS-Protection' => '1; mode=block',
            'Content-Security-Policy' => "default-src 'self'"
        ];

        // Test headers on the API endpoint
        $ch = curl_init('https://staging.pistocklnt.lebawi.net/api/v2/auth/validate');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $response = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers_string = substr($response, 0, $header_size);
        curl_close($ch);

        foreach ($headers as $header => $value) {
            if (stripos($headers_string, $header) === false) {
                $this->warnings[] = "Security header '$header' is missing";
            }
        }

        $this->results['headers'] = "Security headers checked";
        return $this;
    }

    private function checkDependencies() {
        echo "Checking Dependencies...\n";

        // Check composer.lock for vulnerabilities
        if (file_exists(__DIR__ . '/../composer.lock')) {
            $lockFile = json_decode(file_get_contents(__DIR__ . '/../composer.lock'), true);
            foreach ($lockFile['packages'] as $package) {
                if (isset($package['security-advisories']) && !empty($package['security-advisories'])) {
                    $this->errors[] = "Security vulnerability found in {$package['name']}";
                }
            }
        }

        $this->results['dependencies'] = "Dependencies checked";
        return $this;
    }

    private function printResults() {
        echo "\nSecurity Audit Results:\n";
        echo "=====================\n\n";

        foreach ($this->results as $category => $message) {
            echo "✓ $message\n";
        }

        if (!empty($this->errors)) {
            echo "\nErrors Found:\n";
            echo "============\n";
            foreach ($this->errors as $error) {
                echo "❌ $error\n";
            }
        }

        if (!empty($this->warnings)) {
            echo "\nWarnings Found:\n";
            echo "==============\n";
            foreach ($this->warnings as $warning) {
                echo "⚠️ $warning\n";
            }
        }

        if (empty($this->errors) && empty($this->warnings)) {
            echo "\n✅ No security issues found!\n";
        }
    }
}

// Run the security audit
$audit = new SecurityAudit();
$audit->run(); 