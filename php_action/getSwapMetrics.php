<?php
require_once 'db_connect.php';

// Check for valid session/permissions here
if (!isset($_SESSION['userId'])) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

try {
    // Sanitize inputs
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
    $account_id = isset($_GET['account_id']) ? intval($_GET['account_id']) : 0;

    // Build account filter
    $account_filter = $account_id > 0 ? "AND account_id = ?" : "";
    
    // Prepare the SQL query
    $sql = "SELECT 
            COUNT(*) as total_transactions,
            COALESCE(SUM(amount), 0) as total_amount,
            COUNT(DISTINCT account_id) as active_accounts,
            COUNT(DISTINCT platform_id) as platforms_used,
            COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END), 0) as total_deposits,
            COALESCE(SUM(CASE WHEN type = 'withdrawal' THEN ABS(amount) ELSE 0 END), 0) as total_withdrawals
            FROM digitalswap 
            WHERE transaction_date BETWEEN ? AND ?
            $account_filter";

    $stmt = $connect->prepare($sql);
    
    if ($account_id > 0) {
        $stmt->bind_param("ssi", $start_date, $end_date, $account_id);
    } else {
        $stmt->bind_param("ss", $start_date, $end_date);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $metrics = $result->fetch_assoc();

    // Ensure all metrics have values
    $metrics = array_merge([
        'total_transactions' => 0,
        'total_amount' => 0,
        'active_accounts' => 0,
        'platforms_used' => 0,
        'total_deposits' => 0,
        'total_withdrawals' => 0
    ], $metrics ?: []);

    echo json_encode($metrics);

} catch (Exception $e) {
    error_log("Metrics Error: " . $e->getMessage());
    echo json_encode([
        'total_transactions' => 0,
        'total_amount' => 0,
        'active_accounts' => 0,
        'platforms_used' => 0,
        'total_deposits' => 0,
        'total_withdrawals' => 0
    ]);
} 