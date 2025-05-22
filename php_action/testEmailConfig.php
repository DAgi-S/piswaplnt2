<?php
require_once 'core.php';
require_once 'classes/ConfigValidator.php';

// Check if user has permission
if (!isset($_SESSION['userId']) || !hasPermission('edit_system_config')) {
    echo json_encode([
        'success' => false,
        'messages' => ['You do not have permission to perform this action']
    ]);
    exit();
}

// Check if it's an AJAX request
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    
    // Get POST data
    $mailServer = $_POST['mail_server'] ?? '';
    $mailPort = $_POST['mail_port'] ?? '';
    $mailUsername = $_POST['mail_username'] ?? '';
    $mailPassword = $_POST['mail_password'] ?? '';
    $mailDefaultSender = $_POST['mail_default_sender'] ?? '';

    // Create config array
    $config = [
        'MAIL_SERVER' => $mailServer,
        'MAIL_PORT' => $mailPort,
        'MAIL_USERNAME' => $mailUsername,
        'MAIL_PASSWORD' => $mailPassword,
        'MAIL_DEFAULT_SENDER' => $mailDefaultSender
    ];

    // Initialize validator
    $validator = new ConfigValidator();
    $emailSent = false;

    // Validate configuration
    $isValid = $validator->validateEmailConfig($config);
    $canConnect = false;
    
    if ($isValid) {
        // Test SMTP connection
        $canConnect = $validator->testSmtpConnection($config);
        
        if ($canConnect && isset($_POST['send_test'])) {
            // Send test email if requested
            $emailSent = $validator->sendTestEmail($config);
            
            // Log the attempt
            error_log("Test email attempt - Success: " . ($emailSent ? 'Yes' : 'No'));
            
            if ($emailSent) {
                $validator->success[] = "Please check your inbox at " . $config['MAIL_DEFAULT_SENDER'] . " for the test email.";
            }
        }
    }

    // Prepare response
    $response = [
        'success' => $isValid && $canConnect && (!isset($_POST['send_test']) || $emailSent),
        'errors' => $validator->getErrors(),
        'messages' => $validator->getSuccess(),
        'emailSent' => $emailSent,
        'validationPassed' => $isValid,
        'connectionPassed' => $canConnect
    ];

    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
} else {
    // Not an AJAX request
    echo json_encode([
        'success' => false,
        'messages' => ['Invalid request method']
    ]);
    exit();
} 