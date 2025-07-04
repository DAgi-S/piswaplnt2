<?php
require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Email Configuration
define('SMTP_HOST', 'mail.lebawi.net');
define('SMTP_PORT', 465);
define('SMTP_USERNAME', 'swapcapital@lebawi.net');
define('SMTP_PASSWORD', 'swapcapital@0924');
define('SMTP_FROM_EMAIL', 'swapcapital@lebawi.net');
define('NOTIFICATION_EMAIL', 'swapcapital@lebawi.net');

// Email Templates
function getDepositEmailTemplate($data) {
    return "
        <h2>New Digital Swap Deposit</h2>
        <p>Details:</p>
        <ul>
            <li>Name: {$data['name']}</li>
            <li>Platform: {$data['platform']}</li>
            <li>Amount: {$data['amount']} ETB</li>
            <li>Date: {$data['created_at']}</li>
            <li>Comment: {$data['comment']}</li>
        </ul>
    ";
}

function getWithdrawEmailTemplate($data) {
    return "
        <h2>New Digital Swap Withdrawal</h2>
        <p>Details:</p>
        <ul>
            <li>Name: {$data['name']}</li>
            <li>Platform: {$data['platform']}</li>
            <li>Amount: {$data['amount']} ETB</li>
            <li>Date: {$data['created_at']}</li>
            <li>Comment: {$data['comment']}</li>
        </ul>
    ";
}

function getQuotationEmailTemplate($quotationNumber, $message = '') {
    return "
        <h2>Quotation #{$quotationNumber}</h2>
        <p>Dear Valued Customer,</p>
        <p>Please find attached the quotation document for your reference.</p>
        " . (!empty($message) ? "<p>{$message}</p>" : "") . "
        <p>Best regards,</p>
        <p>LEBAWI NET TRADING PLC</p>
    ";
}

function sendQuotationEmail($to, $subject, $message, $pdfContent, $filename, $cc = '') {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->SMTPDebug = SMTP::DEBUG_OFF;
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, 'LEBAWI NET TRADING PLC');
        $mail->addAddress($to);
        
        // Add CC if provided
        if (!empty($cc)) {
            $ccAddresses = array_map('trim', explode(',', $cc));
            foreach ($ccAddresses as $ccEmail) {
                if (filter_var($ccEmail, FILTER_VALIDATE_EMAIL)) {
                    $mail->addCC($ccEmail);
                }
            }
        }
        
        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = strip_tags(str_replace('<br>', "\n", $message));
        
        // Attachment
        $mail->addStringAttachment($pdfContent, $filename, 'base64', 'application/pdf');
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mail Error: {$mail->ErrorInfo}");
        return false;
    }
}

function sendNotificationEmail($subject, $message, $to = null) {    
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->SMTPDebug = SMTP::DEBUG_OFF; // Change to DEBUG_OFF for production
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, 'Piswap System');
        $mail->addAddress($to ? $to : NOTIFICATION_EMAIL);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mail Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>