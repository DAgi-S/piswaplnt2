<?php
// Ensure clean output
ob_start();

header('Content-Type: application/json');

require_once 'core.php';
require_once 'db_connect.php';
require_once 'middleware.php';
require_once 'emailTemplate.php';
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\{PHPMailer, SMTP, Exception};

$response = array();

try {
    if (!isset($_POST['quotationId'], $_POST['emailTo'], $_POST['emailSubject'])) {
        throw new Exception('Required fields are missing');
    }

    $quotationId = (int)$_POST['quotationId'];
    $emailTo = filter_var(trim($_POST['emailTo']), FILTER_SANITIZE_EMAIL);
    $emailSubject = trim($_POST['emailSubject']);
    $emailMessage = $_POST['emailMessage'] ?? '';

    if (!filter_var($emailTo, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address');
    }

    $stmt = $connect->prepare("SELECT q.*, c.*, u.username as created_by_name FROM quotations q LEFT JOIN clients c ON q.client_id = c.id LEFT JOIN users u ON q.created_by = u.user_id WHERE q.id = ?");
    $stmt->bind_param("i", $quotationId);
    $stmt->execute();
    $quotation = $stmt->get_result()->fetch_assoc() ?: throw new Exception('Quotation not found');

    $stmt = $connect->prepare("SELECT qi.*, p.name as product_name FROM quotation_items qi LEFT JOIN products p ON qi.product_id = p.product_id WHERE qi.quotation_id = ?");
    $stmt->bind_param("i", $quotationId);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'mail.lebawi.net';
    $mail->SMTPAuth = true;
    $mail->Username = 'swapcapital@lebawi.net';
    $mail->Password = 'swapcapital@0924';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;
    $mail->setFrom('swapcapital@lebawi.net', 'LEBAWI NET TRADING PLC');
    $mail->addAddress($emailTo);
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = $emailSubject;

    $htmlContent = generateQuotationEmailTemplate($quotation, $items);
    if ($emailMessage) {
        $htmlContent = sprintf('<div style="margin-bottom:20px;padding:15px;background:#f8f9fa;border:1px solid #ddd">%s</div>%s', 
            nl2br(htmlspecialchars($emailMessage)), 
            $htmlContent
        );
    }

    $mail->Body = $htmlContent;
    $mail->AltBody = strip_tags($emailMessage) . "\n\nPlease view in HTML email client for full quotation details.";

    if (!$mail->send()) {
        throw new Exception('Mailer Error: ' . $mail->ErrorInfo);
    }

    $userId = $_SESSION['userId'];
    $stmt = $connect->prepare("INSERT INTO email_logs (quotation_id, sent_to, subject, status, sent_by, sent_at) VALUES (?, ?, ?, 'sent', ?, NOW())");
    $stmt->bind_param("issi", $quotationId, $emailTo, $emailSubject, $userId);
    $stmt->execute();

    $response = array(
        'success' => true,
        'messages' => "Quotation sent successfully to $emailTo"
    );

} catch (Exception $e) {
    error_log("Email Error: " . $e->getMessage());
    
    if (isset($quotationId)) {
        $userId = $_SESSION['userId'];
        $errorMessage = $e->getMessage();
        $stmt = $connect->prepare("INSERT INTO email_logs (quotation_id, sent_to, subject, status, error_message, sent_by, sent_at) VALUES (?, ?, ?, 'failed', ?, ?, NOW())");
        $stmt->bind_param("isssi", $quotationId, $emailTo, $emailSubject, $errorMessage, $userId);
        $stmt->execute();
    }

    $response = array(
        'success' => false,
        'messages' => 'Error sending email: ' . $e->getMessage()
    );
}

// Clean output buffer and send JSON response
ob_end_clean();
echo json_encode($response);
exit; 