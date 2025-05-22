<?php
require_once 'core.php';
require_once 'db_connect.php';

function updateBalanceOnPayment($paymentId) {
    global $connect;
    
    try {
        // Start transaction
        $connect->begin_transaction();

        // Get payment details
        $paymentQuery = "SELECT p.*, o.order_number 
                        FROM gps_payments p 
                        LEFT JOIN gps_orders o ON p.gps_order_id = o.id 
                        WHERE p.id = ?";
        $stmt = $connect->prepare($paymentQuery);
        $stmt->bind_param("i", $paymentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $payment = $result->fetch_assoc();

        if (!$payment) {
            throw new Exception("Payment not found");
        }

        // Get current balance for the investor
        $balanceQuery = "SELECT balance FROM gps_investors WHERE id = ?";
        $stmt = $connect->prepare($balanceQuery);
        $stmt->bind_param("i", $payment['paid_by']);
        $stmt->execute();
        $result = $stmt->get_result();
        $investor = $result->fetch_assoc();

        if (!$investor) {
            throw new Exception("Investor not found");
        }

        // Calculate new balance
        $currentBalance = $investor['balance'];
        $newBalance = $currentBalance - ($payment['paid_amount'] * $payment['rate']);

        // Update investor balance
        $updateInvestorQuery = "UPDATE gps_investors SET balance = ? WHERE id = ?";
        $stmt = $connect->prepare($updateInvestorQuery);
        $stmt->bind_param("di", $newBalance, $payment['paid_by']);
        $stmt->execute();

        // Create balance transaction record
        $description = "Payment for order " . ($payment['order_number'] ?? 'N/A');
        $insertBalanceQuery = "INSERT INTO gps_balance_accounts 
            (account_type, reference_id, transaction_type, transaction_id, 
             currency, rate, amount, balance, description) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $accountType = 'investor';
        $transactionType = 'payment';
        
        $stmt = $connect->prepare($insertBalanceQuery);
        $stmt->bind_param("sssssddds", 
            $accountType,
            $payment['paid_by'],
            $transactionType,
            $paymentId,
            $payment['currency'],
            $payment['rate'],
            $payment['paid_amount'],
            $newBalance,
            $description
        );
        $stmt->execute();

        // Commit transaction
        $connect->commit();
        return array('status' => true, 'message' => 'Balance updated successfully');

    } catch (Exception $e) {
        // Rollback transaction on error
        $connect->rollback();
        return array('status' => false, 'message' => 'Error: ' . $e->getMessage());
    }
}

// Handle AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = array();
    
    if (isset($_POST['payment_id'])) {
        $paymentId = $_POST['payment_id'];
        $response = updateBalanceOnPayment($paymentId);
    } else {
        $response = array('status' => false, 'message' => 'Payment ID is required');
    }
    
    echo json_encode($response);
    exit();
}
?> 