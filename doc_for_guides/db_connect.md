# Database Connection Documentation

## Overview
The database connection system provides a standardized way to establish and manage database connections across different modules of the application. It includes error handling, security measures, and connection pooling.

## File Structure

```
├── includes/
│   └── db_connect.php              # Main database connection file
├── php_action/
│   └── db_connect.php              # Action-specific database connection
├── production/
│   ├── includes/
│   │   └── db_connect.php          # Production module connection
│   └── php_action/
│       └── db_connect.php          # Production actions connection
└── guest/
    └── php_action/
        └── db_connect.php          # Guest module connection
```

## Connection Types

### 1. Main Database Connection (includes/db_connect.php)
- Standard MySQLi connection
- UTF-8 character set support
- Error handling with user-friendly messages
- Connection pooling support

### 2. Action-Specific Connection (php_action/db_connect.php)
- Extended error handling for API requests
- Additional security measures
- Session management integration
- Transaction support

### 3. Production Module Connection (production/includes/db_connect.php)
- PDO-based connection
- Production-specific error handling
- Optimized for production operations
- Transaction management

### 4. Guest Module Connection (guest/php_action/db_connect.php)
- Simplified connection for guest operations
- Guest-specific validation
- Limited access controls
- Performance optimized

## Configuration Parameters

### Database Configuration
```php
$localhost = "localhost";
$username = "root";
$password = "";
$dbname = "pistocklnt";
```

### Connection Options
- Character Set: UTF-8
- Error Reporting: E_ALL
- Display Errors: Off (Production)
- Log Errors: On
- Timezone: UTC

## Security Measures

### 1. Connection Security
- Prepared statements
- Input sanitization
- Error message suppression in production
- Secure password handling

### 2. Access Control
- Role-based connection access
- Module-specific permissions
- IP-based restrictions (optional)
- Connection timeouts

### 3. Data Protection
- Encryption for sensitive data
- Secure password storage
- Session validation
- SQL injection prevention

## Error Handling

### 1. Connection Errors
```php
try {
    // Database operations
} catch(Exception $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    // User-friendly error message
}
```

### 2. Query Errors
```php
if($connect->connect_error) {
    throw new Exception("Connection Failed: " . $connect->connect_error);
}
```

### 3. API Error Responses
```php
if ($is_api_request) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}
```

## Best Practices

### 1. Connection Management
- Use persistent connections where appropriate
- Implement connection pooling
- Close connections when not in use
- Monitor connection usage

### 2. Error Handling
- Log all database errors
- Provide user-friendly messages
- Implement retry mechanisms
- Monitor error patterns

### 3. Security
- Use prepared statements
- Implement input validation
- Regular security audits
- Update credentials regularly

### 4. Performance
- Optimize connection settings
- Monitor query performance
- Implement caching where appropriate
- Regular maintenance

## Implementation Examples

### 1. Basic Connection
```php
try {
    $connect = new mysqli($localhost, $username, $password, $dbname);
    $connect->set_charset("utf8mb4");
} catch(Exception $e) {
    error_log("Database Connection Error: " . $e->getMessage());
}
```

### 2. PDO Connection
```php
try {
    $connect = new PDO(
        "mysql:host=$localhost;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        )
    );
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
}
```

### 3. API Connection
```php
if ($is_api_request) {
    header('Content-Type: application/json');
    if (!$connect) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit();
    }
}
```

## Maintenance

### 1. Regular Tasks
- Monitor connection performance
- Check error logs
- Update security measures
- Backup configurations

### 2. Troubleshooting
- Check connection parameters
- Verify database server status
- Review error logs
- Test connection timeouts

### 3. Updates
- Update database drivers
- Apply security patches
- Optimize connection settings
- Review and update credentials

## Security Considerations

### 1. Access Control
- Implement strict access controls
- Use secure authentication
- Monitor connection attempts
- Regular security audits

### 2. Data Protection
- Encrypt sensitive data
- Secure password storage
- Implement input validation
- Regular security updates

### 3. Error Handling
- Secure error messages
- Log security events
- Monitor suspicious activity
- Implement rate limiting

## Implementation Notes

### 1. Connection Pooling
- Configure connection limits
- Monitor pool usage
- Implement timeout handling
- Regular pool maintenance

### 2. Performance Optimization
- Optimize connection settings
- Implement caching
- Monitor query performance
- Regular performance reviews

### 3. Security Implementation
- Regular security updates
- Monitor security logs
- Implement access controls
- Regular security audits 