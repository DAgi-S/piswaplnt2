<?php
require_once 'core.php';
require_once 'db_connect.php';
require_once 'middleware.php';
require_once 'emailConfig.php';
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

if (!hasPermission('email_quotations')) {
    echo json_encode([
        'success' => false,
        'messages' => 'You do not have permission to email quotations'
    ]);
    exit();
}

if ($_POST) {
    $response = array();
    
    try {
        // Basic validation
        if (empty($_POST['quotationId']) || empty($_POST['emailTo'])) {
            throw new Exception('Required fields are missing');
        }

        if (!filter_var($_POST['emailTo'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }

        $quotationId = $_POST['quotationId'];
        $emailTo = $_POST['emailTo'];
        $emailSubject = $_POST['emailSubject'];
        $emailMessage = isset($_POST['emailMessage']) ? $_POST['emailMessage'] : '';

        // Fetch quotation details
        $sql = "SELECT q.*, c.company_name, c.email 
                FROM quotations q 
                LEFT JOIN clients c ON q.client_id = c.id 
                WHERE q.id = ?";
        
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("i", $quotationId);
        $stmt->execute();
        $result = $stmt->get_result();
        $quotation = $result->fetch_assoc();
        
        if (!$quotation) {
            throw new Exception('Quotation not found');
        }

        // Generate PDF
        ob_start();
        include 'printQuotation.php';
        $pdfContent = ob_get_clean();
        
        if (empty($pdfContent)) {
            throw new Exception('Failed to generate PDF content');
        }
        
        require_once '../vendor/dompdf/dompdf/autoload.inc.php';
        $dompdf = new Dompdf\Dompdf();
        $options = $dompdf->getOptions();
        $options->setIsHtml5ParserEnabled(true);
        $options->setIsRemoteEnabled(true);
        $dompdf->setOptions($options);
        $dompdf->loadHtml($pdfContent);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $pdfOutput = $dompdf->output();
        $pdfFilename = 'Quotation_' . $quotation['quotation_number'] . '.pdf';

        $mail = new PHPMailer(true);
        
        try {
            // Server settings - using same settings as the working digital swap email
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
            $mail->addAddress($emailTo);
            
            // Content
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $emailSubject;
            
            // Email body - using same structure as digital swap
            $body = "
                <h2>Quotation #{$quotation['quotation_number']}</h2>
                <p>Dear Valued Customer,</p>
                <p>Please find attached the quotation document for your reference.</p>
                " . (!empty($emailMessage) ? "<p>{$emailMessage}</p>" : "") . "
                <p>Best regards,</p>
                <p>LEBAWI NET TRADING PLC</p>
            ";
            
            $mail->Body = $body;
            $mail->AltBody = strip_tags(str_replace('<br>', "\n", $body));
            
            // Attachment
            $mail->addStringAttachment($pdfOutput, $pdfFilename, 'base64', 'application/pdf');
            
            // Send email
            if(!$mail->send()) {
                throw new Exception($mail->ErrorInfo);
            }
            
            // Log successful email
            $sql = "INSERT INTO email_logs (quotation_id, sent_to, subject, message, status, created_by, sent_at) 
                    VALUES (?, ?, ?, ?, 1, ?, NOW())";
            $stmt = $connect->prepare($sql);
            $createdBy = $_SESSION['userId'];
            $stmt->bind_param("isssi", $quotationId, $emailTo, $emailSubject, $emailMessage, $createdBy);
            $stmt->execute();
            
            $response['success'] = true;
            $response['messages'] = 'Quotation sent successfully to ' . $emailTo;
            
        } catch (Exception $e) {
            error_log("Mail Error: {$mail->ErrorInfo}");
            
            // Log failed email
            $sql = "INSERT INTO email_logs (quotation_id, sent_to, subject, message, status, error_message, created_by, sent_at) 
                    VALUES (?, ?, ?, ?, 0, ?, ?, NOW())";
            $stmt = $connect->prepare($sql);
            $createdBy = $_SESSION['userId'];
            $errorMessage = $e->getMessage();
            $stmt->bind_param("isssisi", $quotationId, $emailTo, $emailSubject, $emailMessage, $errorMessage, $createdBy);
            $stmt->execute();
            
            throw new Exception('Failed to send email: ' . $e->getMessage());
        }
        
    } catch (Exception $e) {
        $response['success'] = false;
        $response['messages'] = $e->getMessage();
        error_log("Quotation email error: " . $e->getMessage());
    }
    
    echo json_encode($response);
} 