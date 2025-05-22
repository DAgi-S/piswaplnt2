<?php
require_once 'core.php';
require_once 'config/mail_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if (!isset($_SESSION['userId'])) {
    echo json_encode([
        'success' => false,
        'messages' => 'User not logged in'
    ]);
    exit();
}

// Function to log email errors
function logEmailError($message, $context = []) {
    if (defined('LOG_EMAIL_ERRORS') && LOG_EMAIL_ERRORS) {
        $logMessage = date('[Y-m-d H:i:s] ') . $message . "\n";
        if (!empty($context)) {
            $logMessage .= "Context: " . json_encode($context) . "\n";
        }
        $logMessage .= "----------------------------------------\n";
        error_log($logMessage, 3, EMAIL_ERROR_LOG_FILE);
    }
}

try {
    // Check if PHPMailer is available
    if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
        throw new Exception("PHPMailer not found. Please run 'composer install'");
    }
    
    require_once __DIR__ . '/../vendor/autoload.php';
    
    // Create PHPMailer instance with debug enabled
    $mail = new PHPMailer(true);
    
    // Enable debug output if configured
    if (defined('MAIL_DEBUG') && MAIL_DEBUG) {
        $mail->SMTPDebug = defined('MAIL_DEBUG_LEVEL') ? MAIL_DEBUG_LEVEL : SMTP::DEBUG_SERVER;
        $mail->Debugoutput = function($str, $level) {
            logEmailError("Debug ($level): $str");
        };
    }
    
    // Server settings
    $mail->isSMTP();
    $mail->Host = MAIL_SERVER;
    $mail->Port = MAIL_PORT;
    $mail->SMTPAuth = true;
    $mail->Username = MAIL_USERNAME;
    $mail->Password = MAIL_PASSWORD;
    
    // SSL/TLS Settings
    if (defined('MAIL_ENCRYPTION')) {
        if (strtolower(MAIL_ENCRYPTION) === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else if (strtolower(MAIL_ENCRYPTION) === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }
    }

    // SSL verification settings
    if (defined('MAIL_VERIFY_SSL') && !MAIL_VERIFY_SSL) {
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
    }
    
    // Set sender
    $mail->setFrom(MAIL_DEFAULT_SENDER, 'Notification System');
    
    // Get user email
    $sql = "SELECT email FROM users WHERE user_id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $_SESSION['userId']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user || !$user['email']) {
        throw new Exception("User email not found");
    }
    
    // Add recipient
    $mail->addAddress($user['email']);
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Test Email from Notification System';
    $mail->Body = '<h2>Test Email</h2><p>This is a test email from your notification system.</p><p>Time: ' . date('Y-m-d H:i:s') . '</p>';
    $mail->AltBody = 'This is a test email from your notification system. Time: ' . date('Y-m-d H:i:s');
    
    // Start output buffering to capture debug info
    ob_start();
    
    // Attempt to send
    $success = $mail->send();
    $debugOutput = ob_get_clean();
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'messages' => 'Test email sent successfully',
            'debug' => $debugOutput,
            'recipient' => $user['email']
        ]);
    } else {
        throw new Exception("Email could not be sent");
    }
    
} catch (Exception $e) {
    $debugOutput = ob_get_clean();
    $errorMessage = $e->getMessage();
    
    // Log detailed error information
    $errorContext = [
        'error' => $errorMessage,
        'debug_output' => $debugOutput,
        'smtp_settings' => [
            'host' => MAIL_SERVER,
            'port' => MAIL_PORT,
            'encryption' => defined('MAIL_ENCRYPTION') ? MAIL_ENCRYPTION : 'none',
            'username' => MAIL_USERNAME,
            'verify_ssl' => defined('MAIL_VERIFY_SSL') ? MAIL_VERIFY_SSL : true
        ]
    ];
    logEmailError("Test email error: " . $errorMessage, $errorContext);
    
    echo json_encode([
        'success' => false,
        'messages' => 'Failed to send test email: ' . $errorMessage,
        'debug' => $debugOutput,
        'smtp_info' => [
            'host' => MAIL_SERVER,
            'port' => MAIL_PORT,
            'encryption' => defined('MAIL_ENCRYPTION') ? MAIL_ENCRYPTION : 'none',
            'username' => MAIL_USERNAME
        ]
    ]);
}