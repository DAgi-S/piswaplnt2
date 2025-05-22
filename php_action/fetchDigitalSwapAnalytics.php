<?php
require_once 'core.php';
require_once 'db_connect.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch($action) {
    case 'summary':
        getSummaryData();
        break;
    case 'monthly':
        getMonthlyData();
        break;
    case 'transactions':
        getTransactions();
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}

function getSummaryData() {
    global $connect;
    
    // Get total deposits
    $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM digitalswap WHERE type = 'deposit' AND status = 1";
    $result = $connect->query($sql);
    $totalDeposits = $result->fetch_object()->total;

    // Get total withdrawals
    $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM digitalswap WHERE type = 'withdraw' AND status = 1";
    $result = $connect->query($sql);
    $totalWithdraws = $result->fetch_object()->total;

    // Calculate current balance
    $currentBalance = $totalDeposits - $totalWithdraws;

    echo json_encode([
        'totalDeposits' => $totalDeposits,
        'totalWithdraws' => $totalWithdraws,
        'currentBalance' => $currentBalance
    ]);
}

function getMonthlyData() {
    global $connect;
    
    // Get data for the last 12 months
    $sql = "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                type,
                SUM(amount) as total
            FROM digitalswap 
            WHERE status = 1 
                AND created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m'), type
            ORDER BY month ASC";
    
    $result = $connect->query($sql);
    
    $months = [];
    $deposits = [];
    $withdraws = [];
    
    // Initialize arrays with zeros
    for($i = 11; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $months[] = date('M Y', strtotime($month));
        $monthData[$month] = ['deposit' => 0, 'withdraw' => 0];
    }
    
    // Fill in actual data
    while($row = $result->fetch_assoc()) {
        $monthData[$row['month']][$row['type']] = $row['total'];
    }
    
    // Format data for Chart.js
    foreach($monthData as $data) {
        $deposits[] = $data['deposit'];
        $withdraws[] = $data['withdraw'];
    }
    
    echo json_encode([
        'labels' => $months,
        'deposits' => $deposits,
        'withdraws' => $withdraws
    ]);
}

function getTransactions() {
    global $connect;
    
    $sql = "SELECT 
                created_at,
                type,
                name,
                platform,
                amount,
                comment
            FROM digitalswap 
            WHERE status = 1 
            ORDER BY created_at DESC";
    
    $result = $connect->query($sql);
    $data = [];
    
    while($row = $result->fetch_assoc()) {
        $data[] = [
            date('Y-m-d H:i', strtotime($row['created_at'])),
            ucfirst($row['type']),
            $row['name'],
            $row['platform'],
            number_format($row['amount'], 2),
            $row['comment']
        ];
    }
    
    echo json_encode(['data' => $data]);
} 