<?php
require_once '../includes/db_connect.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['guest_id'])) {
    echo json_encode(['error' => true, 'message' => 'Not logged in']);
    exit();
}

// Get POST data
$year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');
$month = isset($_POST['month']) ? intval($_POST['month']) : date('n');

// Validate input
if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
    echo json_encode(['error' => true, 'message' => 'Invalid date range']);
    exit();
}

// Get start and end dates for the month
$startDate = "$year-$month-01";
$endDate = date('Y-m-t', strtotime($startDate));

try {
    // Get account ID from session
    $accountId = $_SESSION['guest_account_id'];
    
    // Initialize response array
    $response = [
        'summary' => [
            'totalTransactions' => 0,
            'totalDeposits' => 0,
            'totalWithdrawals' => 0,
            'netChange' => 0
        ],
        'daily' => [],
        'transactions' => []
    ];

    // Get monthly summary
    $summaryQuery = "SELECT 
        COUNT(*) as totalTransactions,
        SUM(CASE WHEN amount >= 0 THEN amount ELSE 0 END) as totalDeposits,
        SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as totalWithdrawals
    FROM digitalswap 
    WHERE account_id = ? 
    AND DATE(transaction_date) BETWEEN ? AND ?";

    $stmt = $connect->prepare($summaryQuery);
    $stmt->bind_param("iss", $accountId, $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $summary = $result->fetch_assoc();

    $response['summary']['totalTransactions'] = intval($summary['totalTransactions']);
    $response['summary']['totalDeposits'] = floatval($summary['totalDeposits']);
    $response['summary']['totalWithdrawals'] = floatval($summary['totalWithdrawals']);
    $response['summary']['netChange'] = $response['summary']['totalDeposits'] - $response['summary']['totalWithdrawals'];

    // Get daily transactions
    $dailyQuery = "SELECT 
        DATE(transaction_date) as date,
        SUM(CASE WHEN amount >= 0 THEN amount ELSE 0 END) as deposits,
        SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as withdrawals,
        SUM(amount) as netChange
    FROM digitalswap 
    WHERE account_id = ? 
    AND DATE(transaction_date) BETWEEN ? AND ?
    GROUP BY DATE(transaction_date)
    ORDER BY date ASC";

    $stmt = $connect->prepare($dailyQuery);
    $stmt->bind_param("iss", $accountId, $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();

    $runningBalance = 0;
    while ($row = $result->fetch_assoc()) {
        $runningBalance += $row['netChange'];
        $response['daily'][] = [
            'date' => $row['date'],
            'deposits' => floatval($row['deposits']),
            'withdrawals' => floatval($row['withdrawals']),
            'netChange' => floatval($row['netChange']),
            'runningBalance' => $runningBalance
        ];
    }

    // Get detailed transactions
    $transactionQuery = "SELECT 
        transaction_date as date,
        reference_no as reference,
        transaction_type as type,
        description,
        amount,
        balance
    FROM digitalswap 
    WHERE account_id = ? 
    AND DATE(transaction_date) BETWEEN ? AND ?
    ORDER BY transaction_date ASC";

    $stmt = $connect->prepare($transactionQuery);
    $stmt->bind_param("iss", $accountId, $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $response['transactions'][] = [
            'date' => $row['date'],
            'reference' => $row['reference'],
            'type' => $row['type'],
            'description' => $row['description'],
            'amount' => floatval($row['amount']),
            'balance' => floatval($row['balance'])
        ];
    }

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode(['error' => true, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 