<?php
require_once 'core.php';
require_once 'db_connect.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if($_POST) {
    $startDate = $_POST['startDate'];
    $endDate = $_POST['endDate'];
    $type = isset($_POST['type']) ? $_POST['type'] : '';
    $accountId = isset($_POST['accountId']) ? $_POST['accountId'] : '';
    $email = $_POST['email'];

    try {
        // Get report data
        $sql = "SELECT d.*, a.account_owner, a.account_platform 
                FROM digitalswap d 
                LEFT JOIN accounts a ON d.account_id = a.id 
                WHERE DATE(d.transaction_date) BETWEEN ? AND ?";
        $params = [$startDate, $endDate];
        $types = "ss";

        if($type) {
            $sql .= " AND d.type = ?";
            $params[] = $type;
            $types .= "s";
        }

        if($accountId) {
            $sql .= " AND d.account_id = ?";
            $params[] = $accountId;
            $types .= "i";
        }

        $sql .= " ORDER BY d.transaction_date DESC";

        $stmt = $connect->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        // Create email content
        $emailTable = '<table style="width:100%; border-collapse:collapse; margin-top:20px;">';
        $emailTable .= '<tr style="background-color:#f5f5f5;">
            <th style="border:1px solid #ddd; padding:8px;">Date</th>
            <th style="border:1px solid #ddd; padding:8px;">Type</th>
            <th style="border:1px solid #ddd; padding:8px;">Name</th>
            <th style="border:1px solid #ddd; padding:8px;">Platform</th>
            <th style="border:1px solid #ddd; padding:8px;">Account</th>
            <th style="border:1px solid #ddd; padding:8px; text-align:right;">Amount (ETB)</th>
            <th style="border:1px solid #ddd; padding:8px;">Comment</th>
        </tr>';

        $totalDeposit = 0;
        $totalWithdraw = 0;

        while($row = $result->fetch_assoc()) {
            $amount = $row['amount'];
            if($row['type'] == 'deposit') {
                $totalDeposit += $amount;
            } else {
                $totalWithdraw += $amount;
            }

            $emailTable .= sprintf(
                '<tr>
                    <td style="border:1px solid #ddd; padding:8px;">%s</td>
                    <td style="border:1px solid #ddd; padding:8px;">%s</td>
                    <td style="border:1px solid #ddd; padding:8px;">%s</td>
                    <td style="border:1px solid #ddd; padding:8px;">%s</td>
                    <td style="border:1px solid #ddd; padding:8px;">%s</td>
                    <td style="border:1px solid #ddd; padding:8px; text-align:right;">%s</td>
                    <td style="border:1px solid #ddd; padding:8px;">%s</td>
                </tr>',
                date('Y-m-d', strtotime($row['transaction_date'])),
                htmlspecialchars($row['type']),
                htmlspecialchars($row['name']),
                htmlspecialchars($row['account_platform']),
                htmlspecialchars($row['account_owner']),
                number_format($amount, 2),
                htmlspecialchars($row['comment'])
            );
        }

        // Add totals
        $emailTable .= sprintf('
            <tr style="background-color:#f5f5f5;">
                <td colspan="5" style="border:1px solid #ddd; padding:8px; text-align:right;"><strong>Total Deposit</strong></td>
                <td style="border:1px solid #ddd; padding:8px; text-align:right;"><strong>%s</strong></td>
                <td style="border:1px solid #ddd; padding:8px;"></td>
            </tr>
            <tr style="background-color:#f5f5f5;">
                <td colspan="5" style="border:1px solid #ddd; padding:8px; text-align:right;"><strong>Total Withdraw</strong></td>
                <td style="border:1px solid #ddd; padding:8px; text-align:right;"><strong>%s</strong></td>
                <td style="border:1px solid #ddd; padding:8px;"></td>
            </tr>
            <tr style="background-color:#f5f5f5;">
                <td colspan="5" style="border:1px solid #ddd; padding:8px; text-align:right;"><strong>Net Balance</strong></td>
                <td style="border:1px solid #ddd; padding:8px; text-align:right;"><strong>%s</strong></td>
                <td style="border:1px solid #ddd; padding:8px;"></td>
            </tr>',
            number_format($totalDeposit, 2),
            number_format($totalWithdraw, 2),
            number_format($totalDeposit - $totalWithdraw, 2)
        );
        
        $emailTable .= '</table>';

        // Send email with debug
        $mail = new PHPMailer(true);
        
        // Enable debugging
        $mail->SMTPDebug = 3;
        $debugOutput = [];
        $mail->Debugoutput = function($str, $level) use (&$debugOutput) {
            $debugOutput[] = $str;
        };
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'mail.lebawi.net';
            $mail->SMTPAuth = true;
            $mail->Username = 'swapcapital@lebawi.net';
            $mail->Password = 'swapcapital@0924';
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;
            
            // SSL Options
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            // Set timeout
            $mail->Timeout = 60;
            
            // Recipients
            $mail->setFrom('swapcapital@lebawi.net', 'Swap Capital');
            $mail->addAddress($email);
            
            // Content
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = "Digital Swap Report ($startDate to $endDate)";
            
            // Create email content with simplified table
            $emailContent = "
                <div style='font-family: Arial, sans-serif;'>
                    <h2 style='color: #333;'>Digital Swap Report</h2>
                    <p style='margin-bottom: 20px;'>Period: {$startDate} to {$endDate}</p>
                    {$emailTable}
                    <p style='margin-top: 20px; color: #666; font-size: 12px;'>Generated on: " . date('Y-m-d H:i:s') . "</p>
                </div>
            ";
            
            $mail->Body = $emailContent;
            $mail->AltBody = strip_tags(str_replace(['</tr>', '</td>'], ["\n", "\t"], $emailContent));

            // Try to send
            if(!$mail->send()) {
                throw new Exception($mail->ErrorInfo);
            }
            
            echo json_encode([
                'success' => true,
                'messages' => "Report sent successfully to " . $email,
                'debug' => $debugOutput
            ]);

        } catch (Exception $e) {
            error_log("Detailed Email Error: " . $e->getMessage());
            error_log("Debug Output: " . print_r($debugOutput, true));
            
            echo json_encode([
                'success' => false,
                'messages' => "Error sending email",
                'error_details' => $e->getMessage(),
                'debug' => $debugOutput
            ]);
        }

    } catch (Exception $e) {
        error_log("Query Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'messages' => "Error preparing report: " . $e->getMessage()
        ]);
    }
} 