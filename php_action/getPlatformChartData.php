<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'db_connect.php';

header('Content-Type: application/json');

try {
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
    $account_id = isset($_GET['account_id']) ? intval($_GET['account_id']) : 0;

    $account_filter = $account_id > 0 ? "AND d.account_id = ?" : "";

    $sql = "SELECT 
            p.platform_name,
            COUNT(*) as transaction_count,
            SUM(CASE WHEN d.type = 'deposit' THEN d.amount ELSE 0 END) as deposits,
            SUM(CASE WHEN d.type = 'withdrawal' THEN ABS(d.amount) ELSE 0 END) as withdrawals,
            SUM(CASE 
                WHEN d.type = 'deposit' THEN d.amount 
                WHEN d.type = 'withdrawal' THEN -ABS(d.amount)
                ELSE 0 
            END) as net_amount
            FROM digitalswap d
            JOIN platforms p ON d.platform_id = p.id
            WHERE d.transaction_date BETWEEN ? AND ?
            $account_filter
            GROUP BY p.platform_name
            ORDER BY net_amount DESC";

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
    $colors = [
        '#4CAF50', // Green for positive net
        '#F44336', // Red for negative net
        '#2196F3', // Blue
        '#FFC107', // Amber
        '#9C27B0', // Purple
        '#FF5722', // Deep Orange
        '#00BCD4', // Cyan
        '#795548', // Brown
        '#607D8B', // Blue Grey
        '#E91E63'  // Pink
    ];

    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['platform_name'];
        $deposits[] = floatval($row['deposits']);
        $withdrawals[] = floatval($row['withdrawals']);
    }

    echo json_encode([
        'labels' => $labels,
        'datasets' => [
            [
                'label' => 'Deposits',
                'data' => $deposits,
                'backgroundColor' => '#4CAF50',
                'borderColor' => '#388E3C',
                'borderWidth' => 1
            ],
            [
                'label' => 'Withdrawals',
                'data' => $withdrawals,
                'backgroundColor' => '#F44336',
                'borderColor' => '#D32F2F',
                'borderWidth' => 1
            ]
        ]
    ]);

} catch (Exception $e) {
    error_log("Platform Chart Error: " . $e->getMessage());
    echo json_encode([
        'error' => true,
        'message' => 'An error occurred while fetching platform data',
        'debug' => $e->getMessage()
    ]);
} 