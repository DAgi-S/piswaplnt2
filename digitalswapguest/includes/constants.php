<?php
/**
 * System-wide constants
 */

// User Status Constants
define('USER_STATUS_ACTIVE', 1);
define('USER_STATUS_INACTIVE', 0);
define('USER_STATUS_PENDING', 2);
define('USER_STATUS_SUSPENDED', 3);

// Transaction Types
define('TRANSACTION_TYPE_DEPOSIT', 'deposit');
define('TRANSACTION_TYPE_WITHDRAW', 'withdraw');

// Account Status
define('ACCOUNT_STATUS_ACTIVE', 1);
define('ACCOUNT_STATUS_INACTIVE', 0);
define('ACCOUNT_STATUS_SUSPENDED', 2);

// Access Levels
define('ACCESS_LEVEL_GUEST', 'guest');
define('ACCESS_LEVEL_ADMIN', 'admin');
define('ACCESS_LEVEL_SUPER_ADMIN', 'super_admin');

// Time Constants
define('ONE_HOUR', 3600);
define('ONE_DAY', 86400);
define('ONE_WEEK', 604800);
define('ONE_MONTH', 2592000);

// Response Status Codes
define('STATUS_SUCCESS', 200);
define('STATUS_CREATED', 201);
define('STATUS_BAD_REQUEST', 400);
define('STATUS_UNAUTHORIZED', 401);
define('STATUS_FORBIDDEN', 403);
define('STATUS_NOT_FOUND', 404);
define('STATUS_SERVER_ERROR', 500);

// Message Types
define('MESSAGE_SUCCESS', 'success');
define('MESSAGE_ERROR', 'danger');
define('MESSAGE_WARNING', 'warning');
define('MESSAGE_INFO', 'info');

// Report Types
define('REPORT_TYPE_DAILY', 'daily');
define('REPORT_TYPE_WEEKLY', 'weekly');
define('REPORT_TYPE_MONTHLY', 'monthly');
define('REPORT_TYPE_ANNUAL', 'annual');
define('REPORT_TYPE_CUSTOM', 'custom');

// Notification Types
define('NOTIFICATION_TYPE_EMAIL', 'email');
define('NOTIFICATION_TYPE_SMS', 'sms');
define('NOTIFICATION_TYPE_SYSTEM', 'system');

// Export Types
define('EXPORT_TYPE_PDF', 'pdf');
define('EXPORT_TYPE_EXCEL', 'excel');
define('EXPORT_TYPE_CSV', 'csv');
?> 