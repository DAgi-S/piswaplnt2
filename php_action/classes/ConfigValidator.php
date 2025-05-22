<?php
require_once 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class ConfigValidator {
    private $errors = [];
    private $success = [];

    /**
     * Validate email configuration
     * @param array $config Email configuration array
     * @return bool Returns true if validation passes
     */
    public function validateEmailConfig($config) {
        $required = ['MAIL_SERVER', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_DEFAULT_SENDER'];
        
        // Check required fields
        foreach ($required as $field) {
            if (!isset($config[$field]) || empty($config[$field])) {
                $this->errors[] = "Field {$field} is required";
                return false;
            }
        }

        // Validate email format
        if (!filter_var($config['MAIL_DEFAULT_SENDER'], FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = "Invalid email format for Default Sender";
            return false;
        }

        // Validate port number
        if (!is_numeric($config['MAIL_PORT']) || $config['MAIL_PORT'] < 1 || $config['MAIL_PORT'] > 65535) {
            $this->errors[] = "Invalid port number. Must be between 1 and 65535";
            return false;
        }

        return true;
    }

    /**
     * Test SMTP Connection
     * @param array $config Email configuration array
     * @return bool Returns true if connection successful
     */
    public function testSmtpConnection($config) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->SMTPDebug = 0;
            $mail->Host = $config['MAIL_SERVER'];
            $mail->Port = $config['MAIL_PORT'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['MAIL_USERNAME'];
            $mail->Password = $config['MAIL_PASSWORD'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

            // Try to connect without sending
            if ($mail->smtpConnect()) {
                $mail->smtpClose();
                $this->success[] = "Successfully connected to SMTP server";
                return true;
            } else {
                $this->errors[] = "Failed to connect to SMTP server";
                return false;
            }
        } catch (Exception $e) {
            $this->errors[] = "SMTP connection error: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Send test email
     * @param array $config Email configuration array
     * @return bool Returns true if email sent successfully
     */
    public function sendTestEmail($config) {
        try {
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->SMTPDebug = 0;
            $mail->Host = $config['MAIL_SERVER'];
            $mail->Port = $config['MAIL_PORT'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['MAIL_USERNAME'];
            $mail->Password = $config['MAIL_PASSWORD'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            
            // Recipients
            $mail->setFrom($config['MAIL_DEFAULT_SENDER'], 'System Configuration');
            $mail->addAddress($config['MAIL_DEFAULT_SENDER']);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Test Email Configuration';
            
            // Email content
            $message = "<html><body>";
            $message .= "<h2>Email Configuration Test</h2>";
            $message .= "<p>This is a test email to verify your email configuration.</p>";
            $message .= "<p>Configuration used:</p>";
            $message .= "<ul>";
            $message .= "<li>Server: " . $config['MAIL_SERVER'] . "</li>";
            $message .= "<li>Port: " . $config['MAIL_PORT'] . "</li>";
            $message .= "<li>Username: " . $config['MAIL_USERNAME'] . "</li>";
            $message .= "<li>Sender: " . $config['MAIL_DEFAULT_SENDER'] . "</li>";
            $message .= "</ul>";
            $message .= "<p>If you received this email, your email configuration is working correctly.</p>";
            $message .= "<p>Time sent: " . date('Y-m-d H:i:s') . "</p>";
            $message .= "</body></html>";
            
            $mail->Body = $message;
            $mail->AltBody = strip_tags($message);
            
            // Send email
            if($mail->send()) {
                $this->success[] = "Test email sent successfully to " . $config['MAIL_DEFAULT_SENDER'];
                
                // Log the successful email send
                error_log("Test email sent successfully to " . $config['MAIL_DEFAULT_SENDER']);
                
                return true;
            } else {
                $this->errors[] = "Failed to send test email";
                return false;
            }
        } catch (Exception $e) {
            $this->errors[] = "Error sending test email: " . $e->getMessage();
            error_log("Email send error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get validation errors
     * @return array Array of error messages
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Get success messages
     * @return array Array of success messages
     */
    public function getSuccess() {
        return $this->success;
    }

    /**
     * Clear all messages
     */
    public function clearMessages() {
        $this->errors = [];
        $this->success = [];
    }
} 