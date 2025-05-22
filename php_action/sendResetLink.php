<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connect.php';
require_once 'config.php';
require_once 'emailConfig.php';
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

error_log("SendResetLink.php accessed");
error_log("Error log file location: " . ini_get('error_log'));

$response = array();

function sendEmailSMTP($to, $subject, $message, $headers) {
    try {
        if (!defined('SMTP_HOST') || !defined('SMTP_PORT') || !defined('SMTP_USERNAME') || !defined('SMTP_PASSWORD')) {
            error_log("Email configuration missing");
            throw new Exception("Email configuration is incomplete");
        }
        error_log("==== Starting Email Send Process ====");
        error_log("Loading required files...");
        
        error_log("Email Details:");
        error_log("To: " . $to);
        error_log("Subject: " . $subject);
        error_log("SMTP Host: " . SMTP_HOST);
        
        error_log("Starting email send process...");
        error_log("To: " . $to);
        error_log("Subject: " . $subject);
        
        $mail = new PHPMailer(true);
        $mail->SMTPDebug = 2; // Enable verbose debug output
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = SMTP_PORT;
        
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        
        $sent = $mail->send();
        error_log("Email sent successfully: " . ($sent ? "Yes" : "No"));
        return $sent;
    } catch (Exception $e) {
        error_log("Mailer Error in sendEmailSMTP: " . $e->getMessage());
        error_log("Debug info: " . print_r($mail->ErrorInfo, true));
        return false;
    }
}

if($_POST) {
    try {
        error_log("POST request received with email: " . ($_POST['email'] ?? 'not set'));

        if(!isset($_POST['email'])) {
            throw new Exception("Email is required");
        }

        $email = mysqli_real_escape_string($connect, $_POST['email']);

        // Check if email exists
        $sql = "SELECT user_id, username FROM users WHERE email = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Generate unique token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Save reset token
            $sql = "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)";
            $stmt = $connect->prepare($sql);
            $stmt->bind_param("iss", $user['user_id'], $token, $expires);
            
            if($stmt->execute()) {
                // Create reset link with correct path
                $resetLink = $store_url . 'reset-password.php?token=' . $token;
                
                error_log("Generated reset link: " . $resetLink);
                
                // Email content with HTML formatting
                $to = $email;
                $subject = "Password Reset Request - Pi Stock";
                
                // HTML message
                $htmlMessage = "
                <html>
                <head>
                    <title>Password Reset</title>
                </head>
                <body style='font-family: Arial, sans-serif;'>
                    <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                        <h2>Password Reset Request</h2>
                        <p>Hello " . htmlspecialchars($user['username']) . ",</p>
                        <p>You have requested to reset your password. Click the link below to reset it:</p>
                        <p style='margin: 20px 0;'>
                            <a href='" . $resetLink . "' style='background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a>
                        </p>
                        <p>Or copy and paste this link in your browser:</p>
                        <p style='background-color: #f5f5f5; padding: 10px; word-break: break-all;'>" . $resetLink . "</p>
                        <p>This link will expire in 1 hour.</p>
                        <p>If you didn't request this, please ignore this email.</p>
                        <br>
                        <p>Regards,<br>Pi Stock System</p>
                    </div>
                </body>
                </html>";
                
                // Plain text version
                $textMessage = "Hello " . $user['username'] . ",\n\n" .
                             "You have requested to reset your password. Click the link below to reset it:\n\n" .
                             $resetLink . "\n\n" .
                             "This link will expire in 1 hour.\n\n" .
                             "If you didn't request this, please ignore this email.\n\n" .
                             "Regards,\nPi Stock System";
                
                // Email headers
                $headers = "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                $headers .= "From: Pi Stock System <" . SMTP_FROM . ">\r\n";
                $headers .= "Reply-To: " . SMTP_FROM . "\r\n";
                $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
                $headers .= "X-Priority: 1\r\n";
                
                if(sendEmailSMTP($to, $subject, $htmlMessage, $headers)) {
                    $response['success'] = true;
                    $response['messages'] = "Password reset link has been sent to your email.";
                    error_log("Reset email sent to: " . $email . " with token: " . $token);
                    error_log("Reset email sent successfully to: " . $email);
                    error_log("Reset link generated: " . $resetLink);
                } else {
                    error_log("Failed to send email to: " . $email);
                    error_log("Mail error: " . error_get_last()['message']);
                    throw new Exception("Error sending email. Please try again.");
                }
            } else {
                throw new Exception("Error generating reset token.");
            }
        } else {
            // For security, don't reveal if email exists or not
            $response['success'] = true;
            $response['messages'] = "If your email exists in our system, you will receive a password reset link.";
        }
    } catch (Exception $e) {
        error_log("Reset password error: " . $e->getMessage());
        $response['success'] = false;
        $response['messages'] = "Error: " . $e->getMessage();
        echo json_encode($response);
        exit();
    }
} else {
    $response['success'] = false;
    $response['messages'] = "Invalid request.";
}

echo json_encode($response); 

error_log("==== Email Configuration ====");
error_log("SMTP_HOST: " . SMTP_HOST);
error_log("SMTP_PORT: " . SMTP_PORT);
error_log("SMTP_USERNAME: " . SMTP_USERNAME);
error_log("BASE_URL: " . BASE_URL);