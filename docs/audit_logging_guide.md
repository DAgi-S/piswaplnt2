# Audit Logging System Guide

## Overview
The audit logging system provides a comprehensive way to track and monitor system activities, configuration changes, and user actions. It includes both server-side and client-side components for flexible logging capabilities.

## Table Structure
The system uses the following database table:
```sql
CREATE TABLE audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    module VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
)
```

## Server-Side Usage (PHP)

### Basic Logging
```php
require_once 'php_action/audit_log.php';

$logger = new AuditLogger();
$logger->logAction('action_name', 'module_name', 'details');
```

### Logging Configuration Changes
```php
$logger = new AuditLogger();
$logger->logAction('config_update', 'email', [
    'old_value' => $oldConfig,
    'new_value' => $newConfig,
    'changed_by' => $_SESSION['userId']
]);
```

### Logging Security Events
```php
$logger = new AuditLogger();
$logger->logAction('login_failed', 'security', [
    'username' => $username,
    'ip_address' => $_SERVER['REMOTE_ADDR'],
    'reason' => 'Invalid credentials'
]);
```

## Client-Side Usage (JavaScript)

### Basic Logging
```javascript
await auditLogger.logAction('action_name', 'module_name', 'details');
```

### Logging Configuration Changes
```javascript
await auditLogger.logConfigChange('email', oldConfig, newConfig);
```

### Logging Security Events
```javascript
await auditLogger.logSecurityEvent('login_attempt', {
    username: username,
    success: false,
    reason: 'Invalid credentials'
});
```

### Logging User Actions
```javascript
await auditLogger.logUserAction('profile_update', {
    field: 'email',
    old_value: oldEmail,
    new_value: newEmail
});
```

## Viewing Logs

### Accessing the Log Viewer
1. Navigate to `audit_log_viewer.php`
2. Use the filters to narrow down results:
   - Module
   - Date range
   - Entries per page

### Filtering Options
- Module: Filter by specific system module
- Date Range: Filter by start and end dates
- Entries per Page: Choose between 25, 50, or 100 entries

## Best Practices

### What to Log
1. Configuration Changes
   - System settings updates
   - Email configuration changes
   - Database configuration changes
   - Backup settings modifications

2. Security Events
   - Login attempts (success/failure)
   - Permission changes
   - IP-based access attempts
   - Session management events

3. User Actions
   - Profile updates
   - Password changes
   - Role assignments
   - Permission modifications

### What Not to Log
1. Sensitive Data
   - Passwords
   - Encryption keys
   - Personal identification information

2. High-Frequency Events
   - Page views
   - Routine system checks
   - Performance metrics

## Integration Examples

### In Configuration Files
```php
// After saving configuration
$logger->logAction('config_save', 'system', [
    'config_type' => 'email',
    'changes' => $changes
]);
```

### In User Management
```php
// After role change
$logger->logAction('role_change', 'users', [
    'user_id' => $userId,
    'old_role' => $oldRole,
    'new_role' => $newRole
]);
```

### In Security Checks
```php
// After failed login attempt
$logger->logAction('login_failed', 'security', [
    'username' => $username,
    'ip' => $_SERVER['REMOTE_ADDR']
]);
```

## Troubleshooting

### Common Issues
1. Missing Logs
   - Check database connection
   - Verify user permissions
   - Check error logs

2. Performance Issues
   - Reduce log frequency
   - Optimize queries
   - Implement log rotation

3. Access Issues
   - Verify user permissions
   - Check session status
   - Validate IP restrictions

## Security Considerations

1. Access Control
   - Restrict log viewer access
   - Implement IP-based restrictions
   - Use role-based permissions

2. Data Protection
   - Encrypt sensitive log data
   - Implement log retention policies
   - Regular log backups

3. Monitoring
   - Set up alerts for critical events
   - Regular log review
   - Automated log analysis 