<?php
require_once 'db_connect.php';

/**
 * Get transaction status label
 * @param int $status
 * @return string
 */
function getStatusLabel($status) {
    switch ($status) {
        case 1:
            return '<span class="badge badge-success">Active</span>';
        case 0:
            return '<span class="badge badge-danger">Inactive</span>';
        default:
            return '<span class="badge badge-secondary">Unknown</span>';
    }
}

/**
 * Format amount with currency symbol
 * @param float $amount
 * @return string
 */
function formatAmount($amount) {
    return '$ ' . number_format($amount, 2);
}

/**
 * Get account summary
 * @param int $account_id
 * @param string $start_date
 * @param string $end_date
 * @return array
 */
function getAccountSummary($account_id, $start_date, $end_date) {
    global $connect;
    
    $sql = "SELECT 
            COUNT(*) as total_transactions,
            SUM(amount) as total_amount,
            MIN(transaction_date) as first_transaction,
            MAX(transaction_date) as last_transaction
            FROM digitalswap
            WHERE account_id = ?
            AND transaction_date BETWEEN ? AND ?";
            
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("iss", $account_id, $start_date, $end_date);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Validate date range
 * @param string $start_date
 * @param string $end_date
 * @return bool
 */
function validateDateRange($start_date, $end_date) {
    $start = strtotime($start_date);
    $end = strtotime($end_date);
    
    if (!$start || !$end) {
        return false;
    }
    
    return $start <= $end;
}

/**
 * Get platform list
 * @return array
 */
function getPlatforms() {
    global $connect;
    
    $sql = "SELECT id, name FROM platforms WHERE status = 1 ORDER BY name";
    $result = $connect->query($sql);
    
    $platforms = [];
    while ($row = $result->fetch_assoc()) {
        $platforms[] = $row;
    }
    
    return $platforms;
} 