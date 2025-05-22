<?php
require_once 'core.php';
require_once 'db_connect.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Clear any previous output
while (ob_get_level()) {
    ob_end_clean();
}

// Set header for JSON response
header('Content-Type: application/json');

$response = array(
    'success' => false,
    'message' => '',
    'balance' => 0,
    'deposits' => 0,
    'withdrawals' => 0,
    'today' => array(
        'deposits' => 0,
        'withdrawals' => 0,
        'transactions' => 0
    ),
    'this_month' => array(
        'deposits' => 0,
        'withdrawals' => 0,
        'transactions' => 0
    ),
    'metrics' => array(
        'avg_transaction' => 0,
        'largest_transaction' => 0,
        'most_active_platform' => '',
        'most_active_owner' => ''
    )
);

try {
    // Overall summary
    $sql = "SELECT 
                COALESCE(SUM(CASE WHEN ds.type = 'deposit' THEN ds.amount ELSE -ds.amount END), 0) as balance,
                COALESCE(SUM(CASE WHEN ds.type = 'deposit' THEN ds.amount ELSE 0 END), 0) as deposits,
                COALESCE(SUM(CASE WHEN ds.type = 'withdraw' THEN ds.amount ELSE 0 END), 0) as withdrawals,
                COUNT(*) as total_transactions,
                AVG(ds.amount) as avg_transaction,
                MAX(ds.amount) as largest_transaction
            FROM digitalswap ds
            INNER JOIN accounts a ON ds.account_id = a.id
            WHERE a.Currency = 'ETB'";

    $result = $connect->query($sql);
    if (!$result) throw new Exception("Main query failed: " . $connect->error);
    $data = $result->fetch_assoc();
    
    // Today's summary
    $sql_today = "SELECT 
                    COALESCE(SUM(CASE WHEN ds.type = 'deposit' THEN ds.amount ELSE 0 END), 0) as today_deposits,
                    COALESCE(SUM(CASE WHEN ds.type = 'withdraw' THEN ds.amount ELSE 0 END), 0) as today_withdrawals,
                    COUNT(*) as today_transactions
                FROM digitalswap ds
                INNER JOIN accounts a ON ds.account_id = a.id
                WHERE a.Currency = 'ETB' 
                AND DATE(ds.transaction_date) = CURDATE()";

    $result_today = $connect->query($sql_today);
    if (!$result_today) throw new Exception("Today query failed: " . $connect->error);
    $data_today = $result_today->fetch_assoc();

    // This month's summary
    $sql_month = "SELECT 
                    COALESCE(SUM(CASE WHEN ds.type = 'deposit' THEN ds.amount ELSE 0 END), 0) as month_deposits,
                    COALESCE(SUM(CASE WHEN ds.type = 'withdraw' THEN ds.amount ELSE 0 END), 0) as month_withdrawals,
                    COUNT(*) as month_transactions
                FROM digitalswap ds
                INNER JOIN accounts a ON ds.account_id = a.id
                WHERE a.Currency = 'ETB' 
                AND MONTH(ds.transaction_date) = MONTH(CURRENT_DATE())
                AND YEAR(ds.transaction_date) = YEAR(CURRENT_DATE())";

    $result_month = $connect->query($sql_month);
    if (!$result_month) throw new Exception("Month query failed: " . $connect->error);
    $data_month = $result_month->fetch_assoc();

    // Most active platform and owner
    $sql_activity = "SELECT 
                        a.account_platform,
                        a.account_owner,
                        COUNT(*) as transaction_count
                    FROM digitalswap ds
                    INNER JOIN accounts a ON ds.account_id = a.id
                    WHERE a.Currency = 'ETB'
                    GROUP BY a.account_platform, a.account_owner
                    ORDER BY transaction_count DESC
                    LIMIT 1";

    $result_activity = $connect->query($sql_activity);
    if (!$result_activity) throw new Exception("Activity query failed: " . $connect->error);
    $data_activity = $result_activity->fetch_assoc();
    
    // Populate response
    $response['success'] = true;
    $response['balance'] = floatval($data['balance'] ?? 0);
    $response['deposits'] = floatval($data['deposits'] ?? 0);
    $response['withdrawals'] = floatval($data['withdrawals'] ?? 0);
    
    // Today's data
    $response['today']['deposits'] = floatval($data_today['today_deposits'] ?? 0);
    $response['today']['withdrawals'] = floatval($data_today['today_withdrawals'] ?? 0);
    $response['today']['transactions'] = intval($data_today['today_transactions'] ?? 0);
    
    // This month's data
    $response['this_month']['deposits'] = floatval($data_month['month_deposits'] ?? 0);
    $response['this_month']['withdrawals'] = floatval($data_month['month_withdrawals'] ?? 0);
    $response['this_month']['transactions'] = intval($data_month['month_transactions'] ?? 0);
    
    // Metrics
    $response['metrics']['avg_transaction'] = floatval($data['avg_transaction'] ?? 0);
    $response['metrics']['largest_transaction'] = floatval($data['largest_transaction'] ?? 0);
    $response['metrics']['most_active_platform'] = $data_activity['account_platform'] ?? '';
    $response['metrics']['most_active_owner'] = $data_activity['account_owner'] ?? '';

    // Log the response for debugging
    error_log("ETB Summary Response: " . print_r($response, true));

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log("ETB Summary Error: " . $e->getMessage());
}

// Close the database connection
$connect->close();

// Send JSON response
echo json_encode($response); 