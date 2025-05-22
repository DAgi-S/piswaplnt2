# Email Configuration Documentation

## Overview
The email configuration system provides a robust framework for sending various types of emails within the application, including quotations, notifications, and reports. It uses PHPMailer for secure SMTP email delivery and includes features for email logging, template management, and configuration validation.

## File Structure

### Configuration Files
```
├── php_action/
│   ├── emailConfig.php         # Main email configuration
│   ├── emailTemplate.php       # Email template generator
│   ├── emailQuotation.php      # Quotation email handler
│   ├── emailDigitalSwap.php    # Digital swap email handler
│   ├── testEmailConfig.php     # Email configuration testing
│   └── validate_email_config.php # Email configuration validation
├── email_templates/
│   ├── quotation_email.php     # Quotation email template
│   └── quotation_email_template.php # Quotation template HTML
└── logs/
    └── email_logs/            # Email sending logs
```

## Configuration Settings

### SMTP Configuration
```php
define('SMTP_HOST', 'mail.lebawi.net');
define('SMTP_PORT', 465);
define('SMTP_USERNAME', 'swapcapital@lebawi.net');
define('SMTP_PASSWORD', 'swapcapital@0924');
define('SMTP_FROM_EMAIL', 'swapcapital@lebawi.net');
define('NOTIFICATION_EMAIL', 'swapcapital@lebawi.net');
```

### Security Settings
- SMTP Authentication: Enabled
- Encryption: SMTPS (SSL/TLS)
- Port: 465
- Timeout: 30 seconds
- Keep-Alive: Disabled

## Email Types

### 1. Quotation Emails
```php
function sendQuotationEmail($to, $subject, $message, $pdfContent, $filename, $cc = '') {
    // Implementation details
}
```

### 2. Digital Swap Emails
```php
function sendDigitalSwapEmail($swapData) {
    // Implementation details
}
```

### 3. Notification Emails
```php
function sendNotificationEmail($subject, $message) {
    // Implementation details
}
```

## Email Templates

### 1. Quotation Template
```php
function generateQuotationEmailTemplate($quotation, $items) {
    // Template generation
}
```

### 2. Digital Swap Template
```php
function getDigitalSwapEmailTemplate($data) {
    // Template generation
}
```

## Email Logging

### 1. Log Structure
```sql
CREATE TABLE email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quotation_id INT,
    sent_to VARCHAR(255),
    subject VARCHAR(255),
    message TEXT,
    status TINYINT(1),
    error_message TEXT,
    created_by INT,
    sent_at DATETIME
);
```

### 2. Log Viewing
```php
// View email logs implementation
```

## Configuration Testing

### 1. Test Process
```php
function testEmailConfig($config) {
    // Test implementation
}
```

### 2. Validation
```php
function validateEmailConfig($data) {
    // Validation implementation
}
```

## Error Handling

### 1. SMTP Errors
```php
try {
    // Email sending code
} catch (Exception $e) {
    error_log("Mail Error: {$mail->ErrorInfo}");
    return false;
}
```

### 2. Configuration Errors
```php
if (empty($mailServer) || empty($mailPort) || empty($mailUsername)) {
    throw new Exception('Required fields are missing');
}
```

## Security Features

### 1. SMTP Security
- SSL/TLS encryption
- Authentication required
- Secure password storage
- IP restrictions

### 2. Input Validation
- Email format validation
- XSS prevention
- SQL injection prevention
- File attachment validation

## Best Practices

### 1. Configuration
- Use environment variables for sensitive data
- Regular password rotation
- Secure SMTP settings
- Proper error logging

### 2. Email Sending
- Validate recipient addresses
- Use proper character encoding
- Include error handling
- Log all email attempts

### 3. Templates
- Use HTML and plain text versions
- Responsive design
- Proper encoding
- Include unsubscribe option

## Maintenance

### 1. Regular Tasks
- Monitor email logs
- Check SMTP configuration
- Update templates
- Review security settings

### 2. Troubleshooting
- Check SMTP connection
- Verify credentials
- Review error logs
- Test email delivery

## Implementation Notes

### 1. Configuration
```php
// Example configuration
$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = SMTP_HOST;
$mail->SMTPAuth = true;
$mail->Username = SMTP_USERNAME;
$mail->Password = SMTP_PASSWORD;
$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
$mail->Port = SMTP_PORT;
```

### 2. Template Usage
```php
// Example template usage
$template = getQuotationEmailTemplate($quotation, $items);
$mail->Body = $template;
$mail->AltBody = strip_tags($template);
```

### 3. Error Handling
```php
// Example error handling
try {
    $mail->send();
    logEmailSuccess($data);
} catch (Exception $e) {
    logEmailError($data, $e->getMessage());
    throw $e;
}
```

## Security Considerations

### 1. Data Protection
- Encrypt sensitive data
- Secure SMTP connection
- Validate all inputs
- Sanitize output

### 2. Access Control
- Restrict configuration access
- Log all changes
- Monitor usage
- Regular audits

### 3. Network Security
- Use secure protocols
- Validate certificates
- Monitor connections
- Rate limiting 