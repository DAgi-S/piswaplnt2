<?php
require_once '../includes/core.php';

header('Content-Type: application/json');

// Function to format currency
function formatGuestCurrency($amount, $currency = 'ETB') {
    return number_format((float)$amount, 2, '.', ',');
}

// Check if user is logged in and has access
if (!isLoggedIn()) {
    echo json_encode([
        'error' => true,
        'message' => 'Authentication required'
    ]);
    exit();
}

try {
    // Get guest user data first
    $sql = "SELECT * FROM guest_users WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $_SESSION['guest_id']);
    $stmt->execute();
    $guestData = $stmt->get_result()->fetch_assoc();

    if (!$guestData) {
        throw new Exception("User data not found.");
    }

    // Get linked accounts
    $accountsSQL = "SELECT DISTINCT a.* 
                   FROM accounts a 
                   JOIN guest_account_links gal ON a.id = gal.account_id 
                   WHERE gal.guest_id = ? AND a.status = 1";
    $stmt = $connect->prepare($accountsSQL);
    $stmt->bind_param("i", $_SESSION['guest_id']);
    $stmt->execute();
    $accountsResult = $stmt->get_result();
    
    if ($accountsResult->num_rows === 0) {
        echo json_encode([
            'success' => true,
            'has_accounts' => false,
            'message' => 'No accounts linked to this profile'
        ]);
        exit();
    }

    // If no active account is set, use the first one
    if (!isset($_SESSION['active_guest_account'])) {
        $firstAccount = $accountsResult->fetch_assoc();
        $_SESSION['active_guest_account'] = $firstAccount['id'];
        $accountsResult->data_seek(0); // Reset result pointer
    }

    $accountId = $_SESSION['active_guest_account'];
    $currency = null;
    $accountBalances = [];

    // Process all accounts and their balances
    while ($account = $accountsResult->fetch_assoc()) {
        if ($account['id'] == $accountId) {
            $currency = $account['Currency'];
        }

        // Get balance for this account
        $balanceSQL = "SELECT 
            COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount 
                             WHEN type = 'withdraw' THEN -amount 
                             ELSE 0 END), 0) as balance
            FROM digitalswap 
            WHERE account_id = ?";
        
        $balanceStmt = $connect->prepare($balanceSQL);
        $balanceStmt->bind_param("i", $account['id']);
        $balanceStmt->execute();
        $balanceResult = $balanceStmt->get_result()->fetch_assoc();

        $accountBalances[] = [
            'account_owner' => $account['account_owner'],
            'account_platform' => $account['account_platform'],
            'currency' => $account['Currency'],
            'balance' => (float)$balanceResult['balance'],
            'formatted_balance' => formatGuestCurrency($balanceResult['balance'], $account['Currency'])
        ];
    }

    // Get date range from parameters or use defaults
    $endDate = isset($_GET['endDate']) ? $_GET['endDate'] : date('Y-m-d');
    $startDate = isset($_GET['startDate']) ? $_GET['startDate'] : date('Y-m-d', strtotime('-30 days'));

    // Get recent activity summary (last 30 days)
    $recentActivitySQL = "SELECT 
        COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END), 0) as recent_deposits,
        COALESCE(SUM(CASE WHEN type = 'withdraw' THEN amount ELSE 0 END), 0) as recent_withdrawals,
        COUNT(*) as total_transactions
    FROM digitalswap 
    WHERE account_id = ? 
    AND transaction_date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)";

    $stmt = $connect->prepare($recentActivitySQL);
    $stmt->bind_param("i", $accountId);
    $stmt->execute();
    $recentActivity = $stmt->get_result()->fetch_assoc();

    // Get current balance (all time)
    $balanceSQL = "SELECT 
        COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount 
                         WHEN type = 'withdraw' THEN -amount 
                         ELSE 0 END), 0) as current_balance
    FROM digitalswap 
    WHERE account_id = ?";

    $stmt = $connect->prepare($balanceSQL);
    $stmt->bind_param("i", $accountId);
    $stmt->execute();
    $balanceData = $stmt->get_result()->fetch_assoc();

    // Get monthly transaction totals for chart
    $monthlySQL = "SELECT 
        DATE_FORMAT(transaction_date, '%Y-%m') as month,
        COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END), 0) as monthly_deposits,
        COALESCE(SUM(CASE WHEN type = 'withdraw' THEN amount ELSE 0 END), 0) as monthly_withdrawals,
        COUNT(*) as transaction_count
    FROM digitalswap 
    WHERE account_id = ? 
    AND transaction_date BETWEEN ? AND ?
    GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
    ORDER BY month ASC";

    $stmt = $connect->prepare($monthlySQL);
    $stmt->bind_param("iss", $accountId, $startDate, $endDate);
    $stmt->execute();
    $monthlyResult = $stmt->get_result();

    $monthlyData = [];
    while ($row = $monthlyResult->fetch_assoc()) {
        $monthlyData[] = [
            'month' => date('M Y', strtotime($row['month'] . '-01')),
            'deposits' => (float)$row['monthly_deposits'],
            'withdrawals' => (float)$row['monthly_withdrawals'],
            'transaction_count' => (int)$row['transaction_count'],
            'net_change' => (float)$row['monthly_deposits'] - (float)$row['monthly_withdrawals']
        ];
    }

    // Get recent transactions (last 5)
    $recentTransactionsSQL = "SELECT 
        transaction_date,
        type,
        amount,
        name,
        platform,
        comment
    FROM digitalswap 
    WHERE account_id = ?
    ORDER BY transaction_date DESC, id DESC
    LIMIT 5";

    $stmt = $connect->prepare($recentTransactionsSQL);
    $stmt->bind_param("i", $accountId);
    $stmt->execute();
    $recentTransactionsResult = $stmt->get_result();

    $recentTransactions = [];
    while ($row = $recentTransactionsResult->fetch_assoc()) {
        $recentTransactions[] = [
            'date' => date('Y-m-d', strtotime($row['transaction_date'])),
            'type' => ucfirst($row['type']),
            'amount' => (float)$row['amount'],
            'name' => $row['name'],
            'platform' => $row['platform'],
            'comment' => $row['comment']
        ];
    }

    // Prepare response
    $response = [
        'success' => true,
        'has_accounts' => true,
        'summary' => [
            'current_balance' => formatGuestCurrency($balanceData['current_balance'], $currency),
            'recent_deposits' => formatGuestCurrency($recentActivity['recent_deposits'], $currency),
            'recent_withdrawals' => formatGuestCurrency($recentActivity['recent_withdrawals'], $currency),
            'total_transactions' => (int)$recentActivity['total_transactions']
        ],
        'account_balances' => $accountBalances,
        'monthly_data' => $monthlyData,
        'recent_transactions' => $recentTransactions,
        'currency' => $currency
    ];

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error in fetchDashboardAnalytics.php: " . $e->getMessage());
    echo json_encode([
        'error' => true,
        'message' => DISPLAY_ERRORS ? $e->getMessage() : 'An error occurred while fetching dashboard data.'
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($connect)) {
        $connect->close();
    }
} 