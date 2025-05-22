<?php
require_once 'core.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    // Get report content first
    $startDate = isset($_POST['startDate']) ? mysqli_real_escape_string($connect, $_POST['startDate']) : '';
    $endDate = isset($_POST['endDate']) ? mysqli_real_escape_string($connect, $_POST['endDate']) : '';
    $emailTo = isset($_POST['email']) ? filter_var($_POST['email'], FILTER_SANITIZE_EMAIL) : '';

    if (!filter_var($emailTo, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address');
    }

    // Convert dates to MySQL format
    $startDate = date('Y-m-d', strtotime($startDate));
    $endDate = date('Y-m-d', strtotime($endDate));

    // Generate report content - Using correct table structure
    $sql = "SELECT o.*, u.username as created_by 
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.user_id 
            WHERE DATE(o.order_date) BETWEEN ? AND ?
            ORDER BY o.order_date DESC";
            
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();

    // Generate HTML content
    $emailContent = generateEmailReport($result, $startDate, $endDate);

    // Configure PHPMailer
    $mail = new PHPMailer(true);
    
    // Debug mode
    $mail->SMTPDebug = 3; // Enable verbose debug output
    $mail->Debugoutput = function($str, $level) {
        error_log("PHPMailer: " . $str);
    };

    // Server settings
    $mail->isSMTP();
    $mail->Host = 'mail.lebawi.net';
    $mail->SMTPAuth = true;
    $mail->Username = 'swapcapital@lebawi.net';
    $mail->Password = 'swapcapital@0924';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Use SMTPS for port 465
    $mail->Port = 465;

    // Error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    // Recipients
    $mail->setFrom('swapcapital@lebawi.net', 'Lebawi Net Trading PLC');
    $mail->addAddress($emailTo);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Sales Report ' . date('d/m/Y', strtotime($startDate)) . 
                    ' - ' . date('d/m/Y', strtotime($endDate));
    $mail->Body = $emailContent;

    // Attempt to send email
    if (!$mail->send()) {
        throw new Exception('Email could not be sent. Mailer Error: ' . $mail->ErrorInfo);
    }

    // Log success
    error_log("Email sent successfully to: " . $emailTo);
    
    echo json_encode([
        'success' => true,
        'messages' => 'Report has been sent successfully to ' . $emailTo
    ]);

} catch (Exception $e) {
    error_log("Email sending failed: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'messages' => 'Error sending email',
        'error_details' => $e->getMessage(),
        'debug' => [
            'smtp_host' => $mail->Host ?? 'not set',
            'smtp_port' => $mail->Port ?? 'not set',
            'smtp_user' => $mail->Username ?? 'not set',
            'smtp_secure' => $mail->SMTPSecure ?? 'not set',
            'to_email' => $emailTo ?? 'not set',
            'error_info' => $mail->ErrorInfo ?? 'not set'
        ]
    ]);
}

function generateEmailReport($result, $startDate, $endDate) {
    $html = '
    <div style="font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto;">
        <div style="text-align: center; padding: 20px;">
            <h2>Lebawi Net Trading PLC</h2>
            <p>TIN: 0073021029</p>
            <p>Phone: +251901000251</p>
        </div>
        
        <h3 style="text-align: center;">Sales Report</h3>';

    if ($result->num_rows == 0) {
        $html .= '<div style="text-align: center; padding: 20px;">
                    <p>No sales recorded between '.date('d/m/Y', strtotime($startDate)).' and '.date('d/m/Y', strtotime($endDate)).'</p>
                 </div>';
    } else {
        $html .= '<table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
            <thead>
                <tr style="background-color: #f8f9fa;">
                    <th style="padding: 8px; border: 1px solid #dee2e6; text-align: left;">Order Date</th>
                    <th style="padding: 8px; border: 1px solid #dee2e6; text-align: left;">FS Number</th>
                    <th style="padding: 8px; border: 1px solid #dee2e6; text-align: left;">Client Name</th>
                    <th style="padding: 8px; border: 1px solid #dee2e6; text-align: left;">Contact</th>
                    <th style="padding: 8px; border: 1px solid #dee2e6; text-align: right;">Sub Total</th>
                    <th style="padding: 8px; border: 1px solid #dee2e6; text-align: right;">VAT</th>
                    <th style="padding: 8px; border: 1px solid #dee2e6; text-align: right;">Grand Total</th>
                    <th style="padding: 8px; border: 1px solid #dee2e6; text-align: center;">Payment Status</th>
                </tr>
            </thead>
            <tbody>';

        $totalSubTotal = 0;
        $totalVat = 0;
        $totalGrandTotal = 0;

        while ($row = $result->fetch_assoc()) {
            $html .= '<tr>
                <td style="padding: 8px; border: 1px solid #dee2e6;">' . date('d/m/Y', strtotime($row['order_date'])) . '</td>
                <td style="padding: 8px; border: 1px solid #dee2e6;">' . htmlspecialchars($row['fsnum']) . '</td>
                <td style="padding: 8px; border: 1px solid #dee2e6;">' . htmlspecialchars($row['client_name']) . '</td>
                <td style="padding: 8px; border: 1px solid #dee2e6;">' . htmlspecialchars($row['client_contact']) . '</td>
                <td style="padding: 8px; border: 1px solid #dee2e6; text-align: right;">' . number_format($row['sub_total'], 2) . '</td>
                <td style="padding: 8px; border: 1px solid #dee2e6; text-align: right;">' . number_format($row['vat'], 2) . '</td>
                <td style="padding: 8px; border: 1px solid #dee2e6; text-align: right;">' . number_format($row['grand_total'], 2) . '</td>
                <td style="padding: 8px; border: 1px solid #dee2e6; text-align: center;">' . ($row['payment_status'] ?? '0') . '</td>
            </tr>';
            
            $totalSubTotal += $row['sub_total'];
            $totalVat += $row['vat'];
            $totalGrandTotal += $row['grand_total'];
        }

        $html .= '<tr style="background-color: #f8f9fa;">
                <td colspan="4" style="padding: 8px; border: 1px solid #dee2e6; text-align: right;"><strong>Totals:</strong></td>
                <td style="padding: 8px; border: 1px solid #dee2e6; text-align: right;"><strong>' . number_format($totalSubTotal, 2) . '</strong></td>
                <td style="padding: 8px; border: 1px solid #dee2e6; text-align: right;"><strong>' . number_format($totalVat, 2) . '</strong></td>
                <td style="padding: 8px; border: 1px solid #dee2e6; text-align: right;"><strong>' . number_format($totalGrandTotal, 2) . '</strong></td>
                <td style="padding: 8px; border: 1px solid #dee2e6;"></td>
            </tr>
        </tbody></table>';
    }

    $html .= '<div style="margin-top: 20px; text-align: center; color: #666;">
        <p>Generated on: ' . date('d/m/Y H:i:s') . '</p>
    </div>';

    return $html;
}

// Add this function to verify the date range and data
function debugOrderData($connect, $startDate, $endDate) {
    $sql = "SELECT COUNT(*) as count FROM orders 
            WHERE DATE(order_date) BETWEEN ? AND ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    
    error_log("Debug: Found {$count} orders between {$startDate} and {$endDate}");
    return $count;
}
?> 