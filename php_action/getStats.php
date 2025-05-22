<?php
require_once 'db_connect.php';
header('Content-Type: application/json');

try {
    $conditions = [];
    $params = [];

    if (!empty($_GET['start_date'])) {
        $conditions[] = "transaction_date >= ?";
        $params[] = $_GET['start_date'];
    }
    if (!empty($_GET['end_date'])) {
        $conditions[] = "transaction_date <= ?";
        $params[] = $_GET['end_date'];
    }
    if (!empty($_GET['account_id'])) {
        $conditions[] = "account_id = ?";
        $params[] = $_GET['account_id'];
    }
    if (!empty($_GET['platform'])) {
        $conditions[] = "platform = ?";
        $params[] = $_GET['platform'];
    }

    $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

    $query = "SELECT 
        COUNT(*) as total_transactions,
        SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END) as total_deposits,
        SUM(CASE WHEN type = 'withdraw' THEN ABS(amount) ELSE 0 END) as total_withdrawals,
        COUNT(DISTINCT platform) as active_platforms
        FROM digitalswap
        $whereClause";

    $stmt = $connect->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();

    echo json_encode([
        'success' => true,
        'data' => [
            'totalTransactions' => (int)$stats['total_transactions'],
            'totalDeposits' => (float)$stats['total_deposits'],
            'totalWithdrawals' => (float)$stats['total_withdrawals'],
            'activePlatforms' => (int)$stats['active_platforms']
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 