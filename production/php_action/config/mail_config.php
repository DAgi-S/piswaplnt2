<?php
// Email Configuration
define('MAIL_SERVER', 'mail.lebawi.net');
define('MAIL_PORT', 465); // SSL port
define('MAIL_USERNAME', 'swapcapital@lebawi.net');
define('MAIL_PASSWORD', 'swapcapital@0924');
define('MAIL_DEFAULT_SENDER', 'swapcapital@lebawi.net');
define('MAIL_ENCRYPTION', 'ssl'); // Using SSL encryption
define('MAIL_DEBUG', true); // Enable debug output
define('MAIL_VERIFY_SSL', false); // Disable SSL verification for testing
define('MAIL_DEBUG_LEVEL', 2); // Detailed debug level

// Admin notification settings
define('NOTIFY_ADMIN_ON_FAILURES', true);
define('FAILURE_NOTIFICATION_THRESHOLD', 3); // Minimum failures before notifying
define('ADMIN_EMAIL_RECIPIENTS', [
    'swapcapital@lebawi.net' // Add more admin emails as needed
]);

// Error logging settings
define('LOG_EMAIL_ERRORS', true);
define('EMAIL_ERROR_LOG_FILE', __DIR__ . '/../../logs/email_errors.log'); 