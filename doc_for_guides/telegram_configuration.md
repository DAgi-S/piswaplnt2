# Telegram Bot Configuration Documentation

## Overview
The Telegram Bot integration provides a robust notification system for sending real-time alerts and updates to Telegram channels or groups. It includes features for bot management, template-based notifications, and comprehensive logging.

## File Structure

### Core Files
```
├── php_action/
│   ├── telegram_bot_management.php    # Bot management functions
│   ├── telegram_notification.php      # Notification handling
│   ├── telegram_notification_helper.php # Helper functions
│   └── telegram_helper.php            # Production-specific helper
├── assets/
│   └── js/
│       └── telegram_bot.js            # Frontend bot management
├── sql/
│   └── telegram_bot_settings.sql      # Database schema
└── examples/
    ├── telegram_notification_example.php
    └── telegram_notification_handler.php
```

## Database Structure

### 1. Bot Settings Table
```sql
CREATE TABLE telegram_bot_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bot_name VARCHAR(100) NOT NULL,
    bot_token VARCHAR(255) NOT NULL,
    chat_id VARCHAR(100) NOT NULL,
    webhook_url VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT
);
```

### 2. Notification Templates Table
```sql
CREATE TABLE telegram_notification_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(100) NOT NULL,
    template_key VARCHAR(50) NOT NULL UNIQUE,
    message_template TEXT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT
);
```

### 3. Notification Logs Table
```sql
CREATE TABLE telegram_notification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT,
    message TEXT NOT NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    error_message TEXT,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Bot Configuration

### 1. Default Bot Settings
```php
$defaultBot = [
    'bot_name' => 'pistocklnt_bot',
    'bot_token' => '7276849358:AAEpEL3QO5JRHbAMs4rMTzA9S-JQOp6V0t8',
    'chat_id' => '317393086',
    'is_active' => 1
];
```

### 2. Production Bot Settings
```php
$productionBot = [
    'bot_token' => '8096776402:AAE6RwnKc78oxJHZqx-0aWtU9eLVijCqYUw',
    'chat_id' => '317393086'
];
```

## Notification Templates

### 1. Default Templates
- New Order
- Low Stock Alert
- Payment Received
- New User Registration
- System Error

### 2. Template Variables
```php
// Example template with variables
$template = 'New order #{{order_id}} has been placed
Client: {{client_name}}
Amount: {{amount}}
Date: {{order_date}}';
```

## API Integration

### 1. Sending Messages
```php
function sendTelegramMessage($botToken, $chatId, $message) {
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    // Implementation
}
```

### 2. Template Processing
```php
function processTemplate($template, $data) {
    foreach($data as $key => $value) {
        $template = str_replace('{{' . $key . '}}', $value, $template);
    }
    return $template;
}
```

## Security Features

### 1. Access Control
- Permission-based access (`manage_telegram_bot`)
- Role-based restrictions
- Secure token storage

### 2. Data Protection
- Encrypted bot tokens
- Secure chat ID storage
- Input validation
- XSS prevention

## Error Handling

### 1. Notification Errors
```php
try {
    // Send notification
} catch (Exception $e) {
    logNotification($templateId, $message, 'failed', $e->getMessage());
    return false;
}
```

### 2. Connection Errors
```php
if ($result === false) {
    error_log("Failed to send Telegram notification: " . error_get_last()['message']);
    return false;
}
```

## Logging System

### 1. Log Structure
- Template ID
- Message content
- Status (pending/sent/failed)
- Error messages
- Timestamps

### 2. Log Management
- View recent logs
- Filter by status
- Search by template
- Export functionality

## Best Practices

### 1. Configuration
- Use environment variables for sensitive data
- Regular token rotation
- Secure webhook configuration
- Proper error logging

### 2. Notifications
- Use templates for consistency
- Include relevant information
- Keep messages concise
- Test before deployment

### 3. Security
- Regular security audits
- Monitor access logs
- Update tokens regularly
- Validate all inputs

## Implementation Examples

### 1. Sending a Notification
```php
$data = [
    'order_id' => '12345',
    'client_name' => 'Company ABC',
    'amount' => '$1,500.00',
    'order_date' => date('Y-m-d H:i:s')
];

sendTemplateNotification('new_order', $data);
```

### 2. Testing Connection
```php
function testBotConnection($botToken, $chatId) {
    $url = "https://api.telegram.org/bot{$botToken}/getMe";
    // Implementation
}
```

## Troubleshooting

### 1. Common Issues
- Invalid bot token
- Incorrect chat ID
- Network connectivity
- Template errors

### 2. Resolution Steps
1. Verify bot token
2. Check chat ID
3. Test connection
4. Review error logs
5. Check template syntax

## Maintenance

### 1. Regular Tasks
- Monitor notification logs
- Check bot status
- Update templates
- Review security settings

### 2. Updates
- Keep bot token secure
- Update webhook URLs
- Refresh templates
- Monitor API changes 