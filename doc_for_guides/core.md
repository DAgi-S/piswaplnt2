# Core System Documentation

## Overview
The core system provides fundamental functionality and initialization for the entire application. It handles session management, database connections, error handling, and basic security checks across different modules.

## File Structure

```
├── core.php                    # Main core file
├── php_action/
│   └── core.php               # Action-specific core functionality
├── production/
│   └── php_action/
│       └── core.php           # Production module core
├── guest/
│   └── php_action/
│       └── core.php           # Guest module core
└── gpsBusiness/
    └── php_action/
        └── core.php           # GPS Business module core
```

## Core Components

### 1. Session Management
- Session initialization
- Session validation
- User authentication
- Session security

### 2. Database Connection
- Database initialization
- Connection management
- Query execution
- Error handling

### 3. Error Handling
- Error reporting configuration
- Error logging
- Custom error handling
- Debug mode management

### 4. Security
- Permission checking
- Access control
- Input validation
- Security headers

## Implementation Details

### 1. Main Core Implementation
```php
// Start session
session_start();

// Include database connection
require_once 'php_action/db_connect.php';

// Check permissions if enabled
if (defined('CHECK_PERMISSIONS') && CHECK_PERMISSIONS === true) {
    require_once 'php_action/middleware.php';
    
    if (!hasPermission('create_letter')) {
        echo json_encode([
            'success' => false,
            'messages' => 'Permission denied'
        ]);
        exit();
    }
}

// Check user authentication
if(!isset($_SESSION['userId'])) {
    header('location: login.php');
    exit();
}
```

### 2. Action Core Implementation
```php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration
require_once 'config.php';

// Configure error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Include required files
require_once 'db_connect.php';
require_once 'middleware.php';

// Update session information
function updateSessionInfo() {
    global $connect;
    
    if (!isset($_SESSION['userId'])) {
        return;
    }
    
    $session_id = session_id();
    $user_id = $_SESSION['userId'];
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $sql = "REPLACE INTO sessions (session_id, user_id, ip_address, user_agent, last_activity) 
            VALUES (?, ?, ?, ?, NOW())";
            
    try {
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("siss", $session_id, $user_id, $ip_address, $user_agent);
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Failed to update session info: " . $e->getMessage());
    }
}
```

### 3. Production Core Implementation
```php
// Include configuration
require_once 'config.php';

// Configure error reporting based on environment
if ($is_local) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', 'php_errors.log');
}

// Start output buffering
ob_start();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base paths
define('BASE_PATH', dirname(dirname(__FILE__)));
define('PRODUCTION_PATH', dirname(__FILE__));

// Include database connection
require_once PRODUCTION_PATH . '/db_connect.php';

// Set timezone
date_default_timezone_set('Asia/Manila');
```

## Core Functions

### 1. Session Management
```php
function isLoggedIn() {
    if(!isset($_SESSION['userId'])) {
        $currentScript = basename($_SERVER['PHP_SELF']);
        error_log("Session data in isLoggedIn: " . json_encode($_SESSION));
        
        if (!in_array($currentScript, ['login.php', 'index.php'])) {
            header('location: ' . $store_url . 'login.php');
            exit();
        }
        return false;
    }
    error_log("Current user ID: " . $_SESSION['userId']);
    return true;
}
```

### 2. Database Operations
```php
function executeQuery($sql, $params = [], $types = '') {
    global $connect;
    
    try {
        $stmt = $connect->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        return $stmt->get_result();
    } catch (Exception $e) {
        error_log("Query Error: " . $e->getMessage());
        return false;
    }
}
```

### 3. User Management
```php
function getCurrentUserId() {
    global $connect;
    
    // Try session userId
    if (isset($_SESSION['userId']) && !empty($_SESSION['userId'])) {
        return intval($_SESSION['userId']);
    }
    
    // Try alternative session variables
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        return intval($_SESSION['user_id']);
    }
    
    // Try username
    if (isset($_SESSION['username']) && !empty($_SESSION['username'])) {
        try {
            $stmt = $connect->prepare("SELECT user_id FROM users WHERE username = ?");
            $stmt->bind_param("s", $_SESSION['username']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                return intval($row['user_id']);
            }
        } catch (Exception $e) {
            error_log("Error finding user by username: " . $e->getMessage());
        }
    }
    
    // Try admin user
    try {
        $result = $connect->query("SELECT user_id FROM users WHERE role = 'admin' OR username = 'admin' LIMIT 1");
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return intval($row['user_id']);
        }
    } catch (Exception $e) {
        error_log("Error finding admin user: " . $e->getMessage());
    }
    
    return 1;
}
```

## Security Features

### 1. Session Security
- Session validation
- Session timeout
- Session hijacking prevention
- Secure session handling

### 2. Database Security
- Prepared statements
- Parameter binding
- Error handling
- Connection security

### 3. Access Control
- Permission checking
- Role validation
- Module access
- API protection

## Error Handling

### 1. Database Errors
```php
if ($connect->connect_error) {
    error_log("Database connection failed: " . $connect->connect_error);
    if ($is_api_request) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit();
    } else {
        die("Connection failed: " . $connect->connect_error);
    }
}
```

### 2. Permission Errors
```php
if (!hasPermission('create_letter')) {
    echo json_encode([
        'success' => false,
        'messages' => 'Permission denied'
    ]);
    exit();
}
```

### 3. Authentication Errors
```php
if(!isset($_SESSION['userId'])) {
    header('location: login.php');
    exit();
}
```

## Best Practices

### 1. Session Management
- Validate all sessions
- Handle session timeouts
- Prevent session hijacking
- Secure session storage

### 2. Database Operations
- Use prepared statements
- Implement error handling
- Validate all inputs
- Secure connections

### 3. Security
- Check permissions
- Validate access
- Handle errors
- Monitor security

### 4. Performance
- Optimize queries
- Cache results
- Monitor performance
- Track metrics

## Maintenance

### 1. Regular Tasks
- Monitor error logs
- Check security measures
- Update core components
- Review access patterns

### 2. Troubleshooting
- Check session validation
- Verify database connections
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

### 2. Database Security
- Secure connections
- Input validation
- Error handling
- Access control

### 3. Access Security
- Permission checking
- Role validation
- Module access
- API protection

## Implementation Notes

### 1. Session Management
- Validate sessions
- Handle timeouts
- Prevent hijacking
- Secure storage

### 2. Database Operations
- Use prepared statements
- Handle errors
- Validate inputs
- Secure connections

### 3. Security
- Check permissions
- Validate access
- Handle errors
- Monitor security 