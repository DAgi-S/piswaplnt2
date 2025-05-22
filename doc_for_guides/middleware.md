# Middleware Documentation

## Overview
The middleware system provides a robust layer for handling authentication, authorization, and security measures across the application. It includes various middleware components for different security and operational needs.

## File Structure

```
├── includes/
│   ├── middleware.php              # Main middleware file
│   └── jwt/
│       ├── JWTMiddleware.php       # JWT authentication middleware
│       ├── CSRFMiddleware.php      # CSRF protection middleware
│       ├── RateLimitMiddleware.php # Rate limiting middleware
│       ├── WebSocketAuthMiddleware.php # WebSocket authentication
│       └── TokenExpirationMiddleware.php # Token expiration handling
├── production/
│   ├── includes/
│   │   └── jwt/
│   │       ├── middleware.php      # Production JWT middleware
│   │       └── CSRFMiddleware.php  # Production CSRF protection
│   └── php_action/
│       └── middleware.php          # Production action middleware
```

## Middleware Types

### 1. Authentication Middleware
- JWT-based authentication
- Session-based authentication
- WebSocket authentication
- Token validation and refresh

### 2. Authorization Middleware
- Role-based access control
- Permission checking
- Module-specific access
- API endpoint protection

### 3. Security Middleware
- CSRF protection
- Rate limiting
- Input validation
- Request sanitization

### 4. Production Middleware
- Production-specific authentication
- Enhanced security measures
- Performance monitoring
- Error handling

## Implementation Details

### 1. JWT Authentication
```php
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
        // Token generation logic
    }
    
    public function validateToken($token) {
        // Token validation logic
    }
}
```

### 2. CSRF Protection
```php
class CSRFMiddleware {
    private $enabled;
    private $cookieName;
    private $headerName;

    public function __construct() {
        $this->enabled = JWT_CSRF_ENABLED;
        $this->cookieName = JWT_CSRF_COOKIE_NAME;
        $this->headerName = JWT_CSRF_HEADER_NAME;
    }

    public function generateToken() {
        // CSRF token generation
    }

    public function validateToken() {
        // CSRF token validation
    }
}
```

### 3. Rate Limiting
```php
class RateLimitMiddleware {
    private $redis;
    private $maxAttempts;
    private $decayMinutes;
    private $prefix = 'rate_limit:';

    public function __construct($maxAttempts = null, $decayMinutes = null) {
        $this->maxAttempts = $maxAttempts ?? RATE_LIMIT_ATTEMPTS['api'];
        $this->decayMinutes = $decayMinutes ?? RATE_LIMIT_WINDOWS['api'];
    }

    public function handle($identifier = null) {
        // Rate limiting logic
    }
}
```

## Security Features

### 1. Authentication
- JWT token validation
- Session management
- Token refresh mechanism
- Secure cookie handling

### 2. Authorization
- Role-based access control
- Permission checking
- Module access validation
- API endpoint protection

### 3. Protection
- CSRF token validation
- Rate limiting
- Input sanitization
- Request validation

## Error Handling

### 1. Authentication Errors
```php
if (!$this->validateToken($token)) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token']);
    exit();
}
```

### 2. Authorization Errors
```php
if (!$this->hasPermission($permission)) {
    http_response_code(403);
    echo json_encode(['error' => 'Permission denied']);
    exit();
}
```

### 3. Rate Limit Errors
```php
if ($this->isRateLimited()) {
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded']);
    exit();
}
```

## Best Practices

### 1. Authentication
- Use secure token generation
- Implement token refresh
- Validate all requests
- Handle token expiration

### 2. Authorization
- Implement role-based access
- Check permissions
- Validate module access
- Protect sensitive endpoints

### 3. Security
- Use CSRF protection
- Implement rate limiting
- Validate all inputs
- Sanitize requests

### 4. Performance
- Optimize token validation
- Cache permissions
- Monitor rate limits
- Track performance metrics

## Implementation Examples

### 1. Basic Middleware Usage
```php
require_once 'middleware.php';

// Check authentication
if (!checkApiPermission('view_dashboard')) {
    http_response_code(403);
    echo json_encode(['error' => 'Permission denied']);
    exit();
}
```

### 2. JWT Middleware Usage
```php
$jwtMiddleware = new JWTMiddleware();
$token = $jwtMiddleware->generateToken([
    'user_id' => $userId,
    'role' => $role
]);
```

### 3. CSRF Protection
```php
$csrfMiddleware = new CSRFMiddleware();
$token = $csrfMiddleware->generateToken();
// Add token to form
echo '<input type="hidden" name="csrf_token" value="' . $token . '">';
```

## Maintenance

### 1. Regular Tasks
- Monitor authentication logs
- Check security measures
- Update middleware components
- Review access patterns

### 2. Troubleshooting
- Check token validation
- Verify permissions
- Monitor rate limits
- Review error logs

### 3. Updates
- Update security measures
- Optimize performance
- Add new features
- Fix security issues

## Security Considerations

### 1. Token Security
- Secure token generation
- Token expiration
- Token refresh
- Token blacklisting

### 2. Access Control
- Role validation
- Permission checking
- Module access
- API protection

### 3. Request Security
- CSRF protection
- Rate limiting
- Input validation
- Request sanitization

## Implementation Notes

### 1. Authentication
- Use secure algorithms
- Implement token refresh
- Handle token expiration
- Validate all requests

### 2. Authorization
- Check roles
- Validate permissions
- Protect endpoints
- Monitor access

### 3. Security
- Implement CSRF protection
- Use rate limiting
- Validate inputs
- Sanitize requests 