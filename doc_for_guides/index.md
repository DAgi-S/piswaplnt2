# Index System Documentation

## Overview
The index system serves as the entry point and authentication gateway for the application. It handles user authentication, session management, role-based redirection, and provides the login interface for different user types.

## File Structure

```
├── index.php                    # Main index file
├── guest/
│   └── index.php               # Guest module index
├── api/
│   └── v2/
│       ├── auth/
│       │   └── login/
│       │       └── index.php   # API authentication endpoint
│       └── protected/
│           └── index.php       # Protected API endpoint
```

## Index Components

### 1. Main Index
- User authentication
- Session management
- Role-based redirection
- Login interface

### 2. Guest Index
- Guest authentication
- Guest session management
- Guest-specific redirection
- Guest login interface

### 3. API Index
- API authentication
- Token validation
- Rate limiting
- Protected endpoints

## Implementation Details

### 1. Main Index Implementation
```php
// Start session
session_start();

// Define base URL
$store_url = '';
if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    $store_url = "https://";
} else {
    $store_url = "http://";
}
$store_url .= $_SERVER['HTTP_HOST'];
$store_url .= rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/';

// Check session validity
if(isset($_SESSION['userId'])) {
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
        session_unset();
        session_destroy();
        header('location: ' . $store_url . 'index.php');
        exit();
    }
    $_SESSION['LAST_ACTIVITY'] = time();

    // Role-based redirection
    switch($_SESSION['roleId']) {
        case 1: // Super Admin
        case 2: // Admin
            header('location: ' . $store_url . 'dashboard.php');
            break;
        case 5: // Production Manager
        case 6: // Production Supervisor
            header('location: ' . $store_url . 'production/dashboard.php');
            break;
        case 7: // Production Operator
            header('location: ' . $store_url . 'production/orders.php');
            break;
        default:
            header('location: ' . $store_url . 'dashboard.php');
    }
    exit();
}
```

### 2. Guest Index Implementation
```php
session_start();

// Check guest authentication
if(isset($_SESSION['guest_id'])) {
    header('location: dashboard.php');
    exit();
}

// Check regular user authentication
if(isset($_SESSION['userId']) && !isset($_SESSION['guest_id'])) {
    header('location: ../dashboard.php');
    exit();
}

// Guest login interface
?>
<!DOCTYPE html>
<html>
<head>
    <title>Guest Portal Login</title>
    <!-- Include necessary styles and scripts -->
</head>
<body>
    <!-- Guest login form -->
</body>
</html>
```

### 3. API Index Implementation
```php
// Include required middleware
require_once __DIR__ . '/../../../includes/jwt/JWTMiddleware.php';
require_once __DIR__ . '/../../../includes/jwt/RateLimitMiddleware.php';
require_once __DIR__ . '/../../../includes/jwt/CSRFMiddleware.php';

// Apply security headers
require_once __DIR__ . '/../../../config/security.php';
apply_security_headers();

// Initialize middleware
$jwtMiddleware = new JWTMiddleware();
$rateLimitMiddleware = new RateLimitMiddleware();
$csrfMiddleware = new CSRFMiddleware();

try {
    // Verify JWT token
    $token = $jwtMiddleware->getTokenFromHeader();
    if (!$token || !$jwtMiddleware->validateToken($token)) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    // Check rate limit
    if (!$rateLimitMiddleware->checkRateLimit('api')) {
        http_response_code(429);
        echo json_encode(['error' => 'Rate limit exceeded']);
        exit;
    }
    
    // Verify CSRF token
    if ($_SERVER['REQUEST_METHOD'] !== 'GET' && !$csrfMiddleware->validateToken()) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF token validation failed']);
        exit;
    }
}
```

## Authentication Process

### 1. User Authentication
```php
if($_POST) {        
    $username = mysqli_real_escape_string($connect, $_POST['username']);
    $password = $_POST['password'];

    if(empty($username) || empty($password)) {
        if($username == "") {
            $errors[] = "Username is required";
        } 
        if($password == "") {
            $errors[] = "Password is required";
        }
    } else {
        $sql = "SELECT u.*, ur.role_id, ur.role_name, ur.status as role_status
                FROM users u
                JOIN user_roles ur ON u.role_id = ur.role_id
                WHERE u.username = ? AND u.status = 1";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            if($user['role_status'] != 1) {
                $errors[] = "Your role is currently inactive. Please contact administrator.";
            }
            else if(password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                
                $_SESSION['userId'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['roleId'] = $user['role_id'];
                $_SESSION['role'] = $user['role_name'];
                $_SESSION['LAST_ACTIVITY'] = time();
                $_SESSION['CREATED'] = time();
                $_SESSION['IP'] = $_SERVER['REMOTE_ADDR'];
                
                // Get user permissions
                $permSql = "SELECT p.permission_name 
                           FROM permissions p 
                           JOIN role_permissions rp ON p.permission_id = rp.permission_id 
                           WHERE rp.role_id = ?";
                $permStmt = $connect->prepare($permSql);
                $permStmt->bind_param("i", $user['role_id']);
                $permStmt->execute();
                $permResult = $permStmt->get_result();
                
                $permissions = array();
                while($row = $permResult->fetch_assoc()) {
                    $permissions[] = $row['permission_name'];
                }
                $_SESSION['permissions'] = $permissions;
                
                // Role-based redirection
                switch($user['role_id']) {
                    case 1: // Super Admin
                    case 2: // Admin
                        header('Location: ' . $store_url . 'dashboard.php');
                        break;
                    case 5: // Production Manager
                    case 6: // Production Supervisor
                        header('Location: ' . $store_url . 'production/dashboard.php');
                        break;
                    case 7: // Production Operator
                        header('Location: ' . $store_url . 'production/orders.php');
                        break;
                    default:
                        header('Location: ' . $store_url . 'dashboard.php');
                }
                exit();
            }
        }
    }
}
```

## Security Features

### 1. Session Security
- Session validation
- Session timeout
- Session hijacking prevention
- Secure session handling

### 2. Authentication Security
- Password hashing
- Role validation
- Permission checking
- CSRF protection

### 3. API Security
- JWT token validation
- Rate limiting
- CSRF protection
- Security headers

## Error Handling

### 1. Authentication Errors
```php
if(empty($username) || empty($password)) {
    if($username == "") {
        $errors[] = "Username is required";
    } 
    if($password == "") {
        $errors[] = "Password is required";
    }
}
```

### 2. API Errors
```php
if (!$token || !$jwtMiddleware->validateToken($token)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
```

### 3. Rate Limit Errors
```php
if (!$rateLimitMiddleware->checkRateLimit('api')) {
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded']);
    exit;
}
```

## Best Practices

### 1. Session Management
- Validate all sessions
- Handle session timeouts
- Prevent session hijacking
- Secure session storage

### 2. Authentication
- Use secure password hashing
- Implement role-based access
- Check permissions
- Validate inputs

### 3. API Security
- Validate tokens
- Implement rate limiting
- Use CSRF protection
- Apply security headers

### 4. Performance
- Optimize queries
- Cache results
- Monitor performance
- Track metrics

## Maintenance

### 1. Regular Tasks
- Monitor authentication logs
- Check security measures
- Update authentication components
- Review access patterns

### 2. Troubleshooting
- Check session validation
- Verify authentication
- Monitor error logs
- Review security logs

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

### 2. Authentication Security
- Password security
- Role validation
- Permission checking
- Access control

### 3. API Security
- Token security
- Rate limiting
- CSRF protection
- Security headers

## Implementation Notes

### 1. Session Management
- Validate sessions
- Handle timeouts
- Prevent hijacking
- Secure storage

### 2. Authentication
- Secure password handling
- Role validation
- Permission checking
- Access control

### 3. API Security
- Token validation
- Rate limiting
- CSRF protection
- Security headers 