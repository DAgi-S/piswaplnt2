<?php
require_once 'core.php';
require_once 'classes/ConfigurationManager.php';

// Check permissions
if (!isset($_SESSION['userId']) || !isset($_SESSION['role_id'])) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

class EmailConfigValidator {
    private $config;
    private $errors = [];
    private $testResults = [];

    public function __construct() {
        $this->config = ConfigurationManager::getInstance();
    }

    public function validate($data) {
        // Validate email format
        if (!filter_var($data['mail_from'], FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = 'Invalid sender email format';
        }

        // Validate SMTP host
        if (empty($data['mail_host'])) {
            $this->errors[] = 'SMTP host is required';
        }

        // Validate SMTP port
        if (!is_numeric($data['mail_port']) || $data['mail_port'] < 1 || $data['mail_port'] > 65535) {
            $this->errors[] = 'Invalid SMTP port';
        }

        // Validate encryption type
        if (!in_array($data['mail_encryption'], ['ssl', 'tls', ''])) {
            $this->errors[] = 'Invalid encryption type';
        }

        // If no validation errors, test SMTP connection
        if (empty($this->errors)) {
            $this->testSmtpConnection($data);
        }

        return [
            'success' => empty($this->errors),
            'errors' => $this->errors,
            'test_results' => $this->testResults
        ];
    }

    private function testSmtpConnection($data) {
        try {
            // Create SMTP connection
            $smtp = new PHPMailer\PHPMailer\PHPMailer(true);
            $smtp->isSMTP();
            $smtp->Host = $data['mail_host'];
            $smtp->Port = $data['mail_port'];
            $smtp->SMTPAuth = true;
            $smtp->Username = $data['mail_username'];
            $smtp->Password = $data['mail_password'];
            
            if (!empty($data['mail_encryption'])) {
                $smtp->SMTPSecure = $data['mail_encryption'];
            }

            // Test connection
            $smtp->SMTPDebug = 2;
            $smtp->Debugoutput = function($str, $level) {
                $this->testResults[] = $str;
            };

            // Try to connect
            $smtp->connect();
            $this->testResults[] = 'SMTP Connection successful';

            // Test authentication
            if ($smtp->authenticate()) {
                $this->testResults[] = 'SMTP Authentication successful';
            } else {
                $this->errors[] = 'SMTP Authentication failed';
            }

            // Test sending email
            $smtp->setFrom($data['mail_from'], 'System Test');
            $smtp->addAddress($data['mail_from']); // Send test to self
            $smtp->Subject = 'Email Configuration Test';
            $smtp->Body = 'This is a test email to verify your email configuration.';
            
            if ($smtp->send()) {
                $this->testResults[] = 'Test email sent successfully';
            } else {
                $this->errors[] = 'Failed to send test email';
            }

            $smtp->smtpClose();

        } catch (Exception $e) {
            $this->errors[] = 'SMTP Error: ' . $e->getMessage();
            $this->testResults[] = 'Error details: ' . $e->getMessage();
        }
    }
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $validator = new EmailConfigValidator();
    $result = $validator->validate($_POST);
    
    header('Content-Type: application/json');
    echo json_encode($result);
} else {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
} 