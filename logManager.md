# LogManager Documentation

## Overview
The LogManager is a centralized logging system designed to track activities, changes, and audit trails within the application. It provides consistent logging across different modules and maintains a detailed history of user actions.

## File Structure
```
production/
├── php_action/
│   ├── includes/
│   │   └── LogManager.php    # Main LogManager class
│   ├── createSalesPayment.php
│   ├── updateSalesPayment.php
│   └── other action files...
└── database/
    └── tables/
        ├── activity_log.sql
        └── audit_log.sql
```

## Database Structure

### Activity Log Table
```sql
CREATE TABLE `activity_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `module` varchar(100) NOT NULL,
  `reference_id` bigint(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_module` (`module`),
  KEY `idx_reference` (`reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Audit Log Table
```sql
CREATE TABLE `audit_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `activity_type` varchar(100) NOT NULL,
  `description` text,
  `old_value` text,
  `new_value` text,
  `reference_id` bigint(20) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_activity_type` (`activity_type`),
  KEY `idx_reference` (`reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## LogManager Class Methods

### Constructor
```php
public function __construct($db, $userId)
```
Initializes the LogManager with database connection and user ID.

### Core Logging Methods

1. **logActivity**
```php
public function logActivity($action, $module, $referenceId)
```
Logs basic user activities.

2. **logAudit**
```php
public function logAudit($activityType, $description, $oldValue, $newValue, $referenceId)
```
Logs detailed audit information including before/after values.

### Specialized Logging Methods

3. **logPaymentStatusChange**
```php
public function logPaymentStatusChange($paymentId, $oldStatus, $newStatus, $notes = '')
```
Logs payment status changes.

4. **logPaymentCreation**
```php
public function logPaymentCreation($paymentId, $paymentData)
```
Logs new payment creation.

5. **logPaymentUpdate**
```php
public function logPaymentUpdate($paymentId, $oldData, $newData)
```
Logs payment updates with before/after data.

## Implementation Guide

### 1. Initialize LogManager
```php
require_once 'includes/LogManager.php';

// Initialize with database connection and user ID
$logManager = new LogManager($connect, $_SESSION['userId']);
```

### 2. Basic Activity Logging
```php
// Log a simple activity
$logManager->logActivity(
    "User logged in",
    "auth",
    $userId
);
```

### 3. Audit Trail Logging
```php
// Log detailed changes
$logManager->logAudit(
    "user_profile_update",
    "User profile updated",
    $oldProfileData,
    $newProfileData,
    $userId
);
```

### 4. Payment-Related Logging
```php
// Log payment creation
$logManager->logPaymentCreation($paymentId, $paymentData);

// Log payment status change
$logManager->logPaymentStatusChange($paymentId, 'pending', 'confirmed');

// Log payment update
$logManager->logPaymentUpdate($paymentId, $oldPaymentData, $newPaymentData);
```

## Best Practices

1. **Error Handling**
   - Always wrap logging calls in try-catch blocks
   - Log any errors that occur during logging
   ```php
   try {
       $logManager->logActivity(...);
   } catch (Exception $e) {
       error_log("Logging error: " . $e->getMessage());
   }
   ```

2. **Transaction Management**
   - Include logging in the same transaction as the main operation
   ```php
   try {
       $connect->beginTransaction();
       
       // Perform main operation
       // ...
       
       // Log the operation
       $logManager->logActivity(...);
       
       $connect->commit();
   } catch (Exception $e) {
       $connect->rollback();
       throw $e;
   }
   ```

3. **Data Sanitization**
   - Ensure sensitive data is properly sanitized before logging
   - Remove passwords and other sensitive information
   ```php
   $sanitizedData = array_diff_key($userData, array_flip(['password', 'token']));
   $logManager->logAudit(..., $sanitizedData);
   ```

## Integration Examples

### Sales Payment Creation
```php
// In createSalesPayment.php
try {
    $connect->beginTransaction();
    
    // Create payment record
    // ...
    
    // Log the payment creation
    $paymentData = [
        'payment_id' => $paymentId,
        'order_id' => $orderId,
        'amount' => $amount,
        // ... other payment details
    ];
    $logManager->logPaymentCreation($paymentId, $paymentData);
    
    $connect->commit();
} catch (Exception $e) {
    $connect->rollback();
    throw $e;
}
```

### Sales Payment Update
```php
// In updateSalesPayment.php
try {
    $connect->beginTransaction();
    
    // Get current payment data
    $currentPayment = // ... fetch current data
    
    // Update payment
    // ...
    
    // Get updated payment data
    $updatedPayment = // ... fetch updated data
    
    // Log the update
    $logManager->logPaymentUpdate($paymentId, $currentPayment, $updatedPayment);
    
    $connect->commit();
} catch (Exception $e) {
    $connect->rollback();
    throw $e;
}
```

## Querying Logs

### Activity Log Query Examples
```sql
-- Get recent activities for a user
SELECT * FROM activity_log 
WHERE user_id = ? 
ORDER BY created_at DESC 
LIMIT 10;

-- Get activities for a specific module
SELECT * FROM activity_log 
WHERE module = ? 
ORDER BY created_at DESC;
```

### Audit Log Query Examples
```sql
-- Get audit trail for a specific record
SELECT * FROM audit_log 
WHERE reference_id = ? 
ORDER BY created_at DESC;

-- Get all changes by a user
SELECT * FROM audit_log 
WHERE user_id = ? 
ORDER BY created_at DESC;
```

## Maintenance

1. **Log Rotation**
   - Implement a log rotation strategy for older records
   - Archive or delete logs older than a certain period

2. **Performance Optimization**
   - Index frequently queried columns
   - Regularly analyze and optimize log tables
   - Consider partitioning for large log tables

3. **Monitoring**
   - Monitor log table sizes
   - Set up alerts for unusual activity patterns
   - Regular backup of log data 