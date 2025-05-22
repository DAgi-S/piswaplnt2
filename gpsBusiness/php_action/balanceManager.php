<?php
session_start();

// Include database connection
require_once '../../php_action/core.php';

/**
 * Create a balance transaction record
 * @param string $accountType Type of account (investor/receivable)
 * @param int $referenceId ID of the investor or customer
 * @param string $transactionType Type of transaction (payment/sale/purchase/etc)
 * @param int $transactionId ID of the related transaction
 * @param string $currency Currency of the transaction
 * @param float $rate Exchange rate
 * @param float $amount Transaction amount
 * @param string $description Transaction description
 * @return array Response with success/error status
 */
function createBalanceTransaction($accountType, $referenceId, $transactionType, $transactionId, $currency, $rate, $amount, $description = '') {
    global $connect;
    
    try {
        // Start transaction
        $connect->begin_transaction();

        // Get current balance
        $currentBalance = getCurrentBalance($accountType, $referenceId);
        
        // Calculate new balance
        $newBalance = $currentBalance + $amount;

        // Insert balance transaction
        $sql = "INSERT INTO gps_balance_accounts (
                    account_type, 
                    reference_id, 
                    transaction_type,
                    transaction_id,
                    currency,
                    rate,
                    amount,
                    balance,
                    description,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $connect->prepare($sql);
        $stmt->bind_param("sisisddds", 
            $accountType,
            $referenceId,
            $transactionType,
            $transactionId,
            $currency,
            $rate,
            $amount,
            $newBalance,
            $description
        );

        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            // Update the balance in the respective table
            updateAccountBalance($accountType, $referenceId, $newBalance);
            
            $connect->commit();
            return array('success' => true, 'balance' => $newBalance);
        } else {
            throw new Exception("Failed to create balance transaction");
        }
    } catch (Exception $e) {
        $connect->rollback();
        return array('success' => false, 'error' => $e->getMessage());
    }
}

/**
 * Get current balance for an account
 * @param string $accountType Type of account
 * @param int $referenceId ID of the account holder
 * @return float Current balance
 */
function getCurrentBalance($accountType, $referenceId) {
    global $connect;
    
    $balance = 0;
    
    // Get the latest balance transaction
    $sql = "SELECT balance FROM gps_balance_accounts 
            WHERE account_type = ? AND reference_id = ? 
            ORDER BY created_at DESC LIMIT 1";
            
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("si", $accountType, $referenceId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $balance = $row['balance'];
    }
    
    return $balance;
}

/**
 * Update account balance in the respective table
 * @param string $accountType Type of account
 * @param int $referenceId ID of the account holder
 * @param float $newBalance New balance to set
 * @return bool Success status
 */
function updateAccountBalance($accountType, $referenceId, $newBalance) {
    global $connect;
    
    switch ($accountType) {
        case 'investor':
            $sql = "UPDATE gps_investors SET balance = ? WHERE id = ?";
            break;
        case 'receivable':
            $sql = "UPDATE gps_customers SET balance = ? WHERE id = ?";
            break;
        default:
            throw new Exception("Invalid account type");
    }
    
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("di", $newBalance, $referenceId);
    return $stmt->execute();
}

/**
 * Get balance transaction history
 * @param string $accountType Type of account
 * @param int $referenceId ID of the account holder
 * @return array List of transactions
 */
function getBalanceHistory($accountType, $referenceId) {
    global $connect;
    
    $sql = "SELECT * FROM gps_balance_accounts 
            WHERE account_type = ? AND reference_id = ? 
            ORDER BY created_at DESC";
            
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("si", $accountType, $referenceId);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $transactions = array();
    
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    
    return $transactions;
}