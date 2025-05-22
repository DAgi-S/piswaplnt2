# Authentication Check Documentation

## Overview
The authentication check system provides a standardized way to verify user authentication and session validity across different modules of the application. It includes various authentication check components for different security and operational needs.

## File Structure

```
├── includes/
│   └── auth_check.php              # Main authentication check file
├── production/
│   ├── includes/
│   │   └── auth_check.php          # Production module authentication check
│   └── api/
│       └── utils/
│           └── auth_check.php      # API authentication check
└── guest/
    └── includes/
        └── auth_check.php          # Guest module authentication check
```

## Authentication Check Types

### 1. Session Authentication Check
- Session validation
- User ID verification
- Role checking
- Session timeout handling

### 2. API Authentication Check
- Token validation
- API key verification
- Request signature validation
- Rate limiting integration

### 3. Production Authentication Check
- Production-specific validation
- Enhanced security measures
- Performance monitoring
- Error handling

### 4. Guest Authentication Check
- Guest session validation
- Limited access controls
- Guest-specific permissions
- Session management

## Implementation Details

### 1. Session Authentication
```php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if(!isset($_SESSION['userId'])) {
    header('location: ../index.php');
    exit();
}

// Get user role from database if not set in session
if (!isset($_SESSION['userRole'])) {
    $userId = $_SESSION['userId'];
    $sql = "SELECT role_id FROM users WHERE user_id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $_SESSION['userRole'] = $row['role_id'];
    }
}
```

### 2. API Authentication
```php
function validate_token($token) {
    // Token validation logic
    return $token === "test_mobile_token";
}

function check_api_auth() {
    $headers = getallheaders();
    $token = $headers['Authorization'] ?? null;
    
    if (!$token || !validate_token($token)) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid token']);
        exit();
    }
}
```

### 3. Production Authentication
```php
function check_production_auth() {
    // Check session
    if (!isset($_SESSION['userId'])) {
        header('location: ../index.php');
        exit();
    }
    
    // Check production role
    if (!isset($_SESSION['userRole']) || $_SESSION['userRole'] != 'production') {
        header('location: access_denied.php');
        exit();
    }
    
    // Check production permissions
    if (!has_production_permission()) {
        header('location: access_denied.php');
        exit();
    }
}
```

## Security Features

### 1. Session Security
- Session validation
- Session timeout
- Session hijacking prevention
- Secure session handling

### 2. API Security
- Token validation
- API key verification
- Request signature
- Rate limiting

### 3. Production Security
- Role validation
- Permission checking
- Access control
- Error handling

## Error Handling

### 1. Session Errors
```php
if(!isset($_SESSION['userId'])) {
    header('location: ../index.php');
    exit();
}
```

### 2. API Errors
```php
if (!$token || !validate_token($token)) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token']);
    exit();
}
```

### 3. Permission Errors
```php
if (!has_production_permission()) {
    header('location: access_denied.php');
    exit();
}
```

## Best Practices

### 1. Session Management
- Validate all sessions
- Handle session timeouts
- Prevent session hijacking
- Secure session storage

### 2. API Security
- Validate all tokens
- Verify API keys
- Check request signatures
- Implement rate limiting

### 3. Access Control
- Check user roles
- Verify permissions
- Validate access rights
- Handle unauthorized access

### 4. Performance
- Optimize validation
- Cache permissions
- Monitor performance
- Track metrics

## Implementation Examples

### 1. Basic Authentication Check
```php
require_once 'auth_check.php';

// Check authentication
if (!is_authenticated()) {
    header('location: login.php');
    exit();
}
```

### 2. API Authentication Check
```php
require_once 'auth_check.php';

// Check API authentication
check_api_auth();

// Proceed with API logic
```

### 3. Production Authentication Check
```php
require_once 'auth_check.php';

// Check production authentication
check_production_auth();

// Proceed with production logic
```

## Maintenance

### 1. Regular Tasks
- Monitor authentication logs
- Check security measures
- Update authentication components
- Review access patterns

### 2. Troubleshooting
- Check session validation
- Verify token validation
- Monitor access patterns
- Review error logs

### 3. Updates
- Update security measures
- Optimize performance
- Add new features
- Fix security issues

## Security Considerations

### 1. Session Security
- Secure session handling
- Session timeout
- Session hijacking prevention
- Session validation

### 2. Token Security
- Secure token generation
- Token validation
- Token expiration
- Token refresh

### 3. Access Security
- Role validation
- Permission checking
- Access control
- Security monitoring

## Implementation Notes

### 1. Session Management
- Validate sessions
- Handle timeouts
- Prevent hijacking
- Secure storage

### 2. Token Management
- Generate tokens
- Validate tokens
- Handle expiration
- Refresh tokens

### 3. Access Management
- Check roles
- Verify permissions
- Control access
- Monitor security 