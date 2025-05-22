<?php
require_once 'core.php';
require_once 'db_connect.php';

function updateBalanceOnSale($saleId) {
    global $connect;
    
    try {
        // Start transaction
        $connect->begin_transaction();

        // Get sale details
        $saleQuery = "SELECT s.*, c.id as customer_id, c.name as customer_name 
                     FROM gps_sales s 
                     LEFT JOIN gps_customers c ON s.buyer_name = c.name 
                     WHERE s.id = ?";
        $stmt = $connect->prepare($saleQuery);
        $stmt->bind_param("i", $saleId);
        $stmt->execute();
        $result = $stmt->get_result();
        $sale = $result->fetch_assoc();

        if (!$sale) {
            throw new Exception("Sale not found");
        }

        // Calculate sale amount in ETB
        $saleAmountETB = $sale['total'] * $sale['rate'];

        // Create receivable account if customer doesn't exist in balance_accounts
        $checkReceiverQuery = "SELECT COUNT(*) as count FROM gps_balance_accounts 
                             WHERE account_type = 'receivable' 
                             AND reference_id = ?";
        $stmt = $connect->prepare($checkReceiverQuery);
        $stmt->bind_param("i", $sale['customer_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $receivableExists = $result->fetch_assoc()['count'] > 0;

        // Get current balance for receivable account
        $currentBalance = 0;
        if ($receivableExists) {
            $balanceQuery = "SELECT balance FROM gps_balance_accounts 
                           WHERE account_type = 'receivable' 
                           AND reference_id = ? 
                           ORDER BY created_at DESC LIMIT 1";
            $stmt = $connect->prepare($balanceQuery);
            $stmt->bind_param("i", $sale['customer_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $balanceRow = $result->fetch_assoc();
            if ($balanceRow) {
                $currentBalance = $balanceRow['balance'];
            }
        }

        // Calculate new balance
        $newBalance = $currentBalance + $saleAmountETB;

        // Create balance transaction record
        $description = "Sale to " . $sale['buyer_name'] . " - " . $sale['sales_type'];
        $insertBalanceQuery = "INSERT INTO gps_balance_accounts 
            (account_type, reference_id, transaction_type, transaction_id, 
             currency, rate, amount, balance, description) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $accountType = 'receivable';
        $transactionType = 'sale';
        
        $stmt = $connect->prepare($insertBalanceQuery);
        $stmt->bind_param("sssssddds", 
            $accountType,
            $sale['customer_id'],
            $transactionType,
            $saleId,
            $sale['currency'],
            $sale['rate'],
            $sale['total'],
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
    
    if (isset($_POST['sale_id'])) {
        $saleId = $_POST['sale_id'];
        $response = updateBalanceOnSale($saleId);
    } else {
        $response = array('status' => false, 'message' => 'Sale ID is required');
    }
    
    echo json_encode($response);
    exit();
}
?> 