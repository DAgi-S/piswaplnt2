<?php
require_once 'core.php';
require_once 'emailConfig.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendDigitalSwapEmail($swapData) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = SMTP_PORT;

        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, 'Lebawi Net Trading PLC');
        $mail->addAddress(NOTIFICATION_EMAIL); // Add recipient email

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'New Digital Swap Transaction - ' . ucfirst($swapData['type']);
        
        // Email body
        $body = "
            <h2>Digital Swap Transaction Details</h2>
            <table style='border-collapse: collapse; width: 100%;'>
                <tr>
                    <th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Type</th>
                    <td style='border: 1px solid #ddd; padding: 8px;'>" . ucfirst($swapData['type']) . "</td>
                </tr>
                <tr>
                    <th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Name</th>
                    <td style='border: 1px solid #ddd; padding: 8px;'>{$swapData['name']}</td>
                </tr>
                <tr>
                    <th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Platform</th>
                    <td style='border: 1px solid #ddd; padding: 8px;'>{$swapData['platform']}</td>
                </tr>
                <tr>
                    <th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Amount</th>
                    <td style='border: 1px solid #ddd; padding: 8px;'>{$swapData['amount']}</td>
                </tr>
                <tr>
                    <th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Comment</th>
                    <td style='border: 1px solid #ddd; padding: 8px;'>{$swapData['comment']}</td>
                </tr>
                <tr>
                    <th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Date</th>
                    <td style='border: 1px solid #ddd; padding: 8px;'>{$swapData['created_at']}</td>
                </tr>
            </table>
        ";
        
        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
} 