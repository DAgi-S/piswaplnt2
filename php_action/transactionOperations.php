<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'core.php';
require_once 'db_connect.php';

ob_start();
ob_clean();
header('Content-Type: application/json');

// Log all incoming data
error_log("REQUEST: " . print_r($_REQUEST, true));
error_log("GET: " . print_r($_GET, true));
error_log("POST: " . print_r($_POST, true));

try {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'view':
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            error_log("Viewing transaction ID: " . $id);

            if ($id <= 0) {
                throw new Exception("Invalid transaction ID");
            }

            $sql = "SELECT 
                        d.*,
                        a.account_owner,
                        a.account_platform as platform,
                        t.name as type_name,
                        a.currency
                    FROM digitalswap d
                    JOIN accounts a ON d.account_id = a.id
                    JOIN transaction_types t ON d.type_id = t.id
                    WHERE d.id = ?";

            $stmt = $connect->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $connect->error);
            }

            $stmt->bind_param("i", $id);
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            $result = $stmt->get_result();
            $transaction = $result->fetch_assoc();

            error_log("Query result: " . print_r($transaction, true));

            if (!$transaction) {
                throw new Exception("Transaction not found");
            }

            // Process image path
            $receipt_url = null;
            if (!empty($transaction['image'])) {
                error_log("Receipt image from database: " . $transaction['image']);
                // Check if the image path is already a full URL
                if (filter_var($transaction['image'], FILTER_VALIDATE_URL)) {
                    $receipt_url = $transaction['image'];
                } else {
                    $receipt_url = $transaction['image'];
                }
                error_log("Receipt URL set to: " . $receipt_url);
            }

            echo json_encode([
                'success' => true,
                'data' => [
                    'id' => $transaction['id'],
                    'transaction_date' => date('Y-m-d H:i:s', strtotime($transaction['transaction_date'])),
                    'account_owner' => $transaction['account_owner'],
                    'platform' => $transaction['platform'],
                    'type' => $transaction['type_name'],
                    'amount' => $transaction['amount'],
                    'currency' => $transaction['currency'],
                    'status' => $transaction['status'],
                    'comment' => $transaction['comment'] ?? '',
                    'receipt_url' => $receipt_url,
                    'has_receipt' => !empty($receipt_url)
                ]
            ]);
            break;

        default:
            throw new Exception("Invalid action");
    }
} catch (Exception $e) {
    error_log("Transaction operation error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$connect->close(); 