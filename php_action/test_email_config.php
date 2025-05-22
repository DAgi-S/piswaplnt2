<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set CORS headers
header('Access-Control-Allow-Origin: ' . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*'));
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Required files
require_once '../vendor/autoload.php';

// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Basic security check
if (!isset($_SESSION['userId'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get POST data
    $mailServer = $_POST['mail_server'] ?? '';
    $mailPort = intval($_POST['mail_port'] ?? 0);
    $mailUsername = $_POST['mail_username'] ?? '';
    $mailPassword = $_POST['mail_password'] ?? '';
    $defaultSender = $_POST['default_sender'] ?? '';
    $testRecipient = $_POST['test_recipient'] ?? 'salem@lebawi.net';

    if (empty($mailServer) || empty($mailPort) || empty($mailUsername) || empty($mailPassword) || empty($defaultSender)) {
        throw new Exception('Please fill all required fields with valid values');
    }

    // Validate test recipient email
    if (!filter_var($testRecipient, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid test recipient email address');
    }

    // Create mailer instance
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Enable verbose debug output temporarily
        $mail->isSMTP();
        $mail->Host = $mailServer;
        $mail->SMTPAuth = true;
        $mail->Username = $mailUsername;
        $mail->Password = $mailPassword;
        
        // Use SSL for port 465, TLS for port 587
        if ($mailPort == 465) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }
        $mail->Port = $mailPort;
        
        // Set timeout values
        $mail->Timeout = 30;  // Increased timeout for SMTP connection (in seconds)
        $mail->SMTPKeepAlive = false; // Don't keep connection alive
        
        // Enable debug logging
        ob_start();
        
        // Recipients
        $mail->setFrom($defaultSender, 'System Test');
        $mail->addAddress($testRecipient);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Email Configuration Test';
        $mail->Body = '
            <h2>Email Configuration Test</h2>
            <p>This is a test email to verify your SMTP configuration.</p>
            <p>If you received this email, it means your email configuration is working correctly.</p>
            <hr>
            <p><small>Sent from ' . htmlspecialchars($defaultSender) . '</small></p>
        ';
        $mail->AltBody = 'This is a test email to verify your SMTP configuration. If you received this email, it means your email configuration is working correctly.';

        $sent = $mail->send();
        
        // Get debug output
        $debugOutput = ob_get_clean();
        error_log("SMTP Debug Output: " . $debugOutput);
        
        if (!$sent) {
            throw new Exception('Failed to send email: ' . $mail->ErrorInfo . "\nDebug log: " . $debugOutput);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Test email sent successfully to ' . $testRecipient
        ]);

    } catch (Exception $e) {
        $debugOutput = ob_get_clean();
        error_log("SMTP Error: " . $e->getMessage() . "\nDebug Output: " . $debugOutput);
        http_response_code(400);
        
        // Extract the most relevant error message
        $errorMessage = $e->getMessage();
        if (strpos($errorMessage, 'Debug log:') !== false) {
            $errorMessage = substr($errorMessage, 0, strpos($errorMessage, 'Debug log:'));
        }
        
        echo json_encode([
            'success' => false,
            'message' => 'Email test failed: ' . $errorMessage
        ]);
    }

} catch (Exception $e) {
    error_log("Error in test_email_config.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Configuration error: ' . $e->getMessage()
    ]);
} 