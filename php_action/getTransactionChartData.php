<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'db_connect.php';

header('Content-Type: application/json');

try {
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
    $account_id = isset($_GET['account_id']) ? intval($_GET['account_id']) : 0;
    $period = isset($_GET['period']) ? $_GET['period'] : 'daily';

    $account_filter = $account_id > 0 ? "AND d.account_id = ?" : "";

    // Adjust grouping based on period
    $group_by = match($period) {
        'weekly' => "DATE_FORMAT(d.transaction_date, '%Y-%u')",
        'monthly' => "DATE_FORMAT(d.transaction_date, '%Y-%m')",
        default => "DATE(d.transaction_date)" // daily
    };

    $sql = "SELECT 
            $group_by as date,
            SUM(CASE WHEN d.type = 'deposit' THEN d.amount ELSE 0 END) as deposits,
            SUM(CASE WHEN d.type = 'withdrawal' THEN ABS(d.amount) ELSE 0 END) as withdrawals
            FROM digitalswap d
            WHERE d.transaction_date BETWEEN ? AND ?
            $account_filter
            GROUP BY date
            ORDER BY date";

    $stmt = $connect->prepare($sql);
    
    if ($account_id > 0) {
        $stmt->bind_param("ssi", $start_date, $end_date, $account_id);
    } else {
        $stmt->bind_param("ss", $start_date, $end_date);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $labels = [];
    $deposits = [];
    $withdrawals = [];

    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['date'];
        $deposits[] = floatval($row['deposits']);
        $withdrawals[] = floatval($row['withdrawals']);
    }

    echo json_encode([
        'labels' => $labels,
        'datasets' => [
            [
                'label' => 'Deposits',
                'data' => $deposits,
                'borderColor' => '#4CAF50',
                'backgroundColor' => 'rgba(76, 175, 80, 0.1)',
                'borderWidth' => 2,
                'fill' => true
            ],
            [
                'label' => 'Withdrawals',
                'data' => $withdrawals,
                'borderColor' => '#F44336',
                'backgroundColor' => 'rgba(244, 67, 54, 0.1)',
                'borderWidth' => 2,
                'fill' => true
            ]
        ]
    ]);

} catch (Exception $e) {
    error_log("Transaction Chart Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'An error occurred while fetching chart data',
        'debug' => $e->getMessage()
    ]);
} 