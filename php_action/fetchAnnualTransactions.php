<?php
require_once 'core.php';
require_once 'db_connect.php';

// Set header to JSON
header('Content-Type: application/json');

// Get parameters
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$usdAccounts = isset($_GET['usd_accounts']) ? $_GET['usd_accounts'] : [];
$etbAccounts = isset($_GET['etb_accounts']) ? $_GET['etb_accounts'] : [];

// Initialize response
$response = array(
    'success' => false,
    'data' => array(
        'usd' => array(),
        'etb' => array()
    )
);

try {
    // Prepare account IDs for SQL
    $usdAccountIds = !empty($usdAccounts) ? implode(',', array_map('intval', $usdAccounts)) : '0';
    $etbAccountIds = !empty($etbAccounts) ? implode(',', array_map('intval', $etbAccounts)) : '0';

    // Helper function to initialize account data
    function initializeAccountData($accountId, $accountOwner, $accountPlatform) {
        return array(
            'account_owner' => $accountOwner,
            'account_platform' => $accountPlatform,
            'months' => array_fill(1, 12, array(
                'deposits' => 0,
                'withdrawals' => 0,
                'balance' => 0
            ))
        );
    }

    // Fetch USD transactions
    if (!empty($usdAccounts)) {
        // First get account details
        $accountQuery = "SELECT id, account_owner, account_platform FROM accounts WHERE id IN ($usdAccountIds)";
        $accountResult = $connect->query($accountQuery);
        while ($account = $accountResult->fetch_assoc()) {
            $response['data']['usd'][$account['id']] = initializeAccountData(
                $account['id'],
                $account['account_owner'],
                $account['account_platform']
            );
        }

        // Then get monthly transactions
        $usdQuery = "SELECT 
            d.account_id,
            MONTH(d.transaction_date) as month,
            SUM(CASE WHEN d.type = 'deposit' THEN d.amount ELSE 0 END) as deposits,
            SUM(CASE WHEN d.type = 'withdraw' THEN -d.amount ELSE 0 END) as withdrawals
        FROM digitalswap d
        WHERE d.account_id IN ($usdAccountIds)
        AND YEAR(d.transaction_date) = ?
        AND d.status = 1
        GROUP BY d.account_id, MONTH(d.transaction_date)
        ORDER BY d.account_id, MONTH(d.transaction_date)";

        $stmt = $connect->prepare($usdQuery);
        $stmt->bind_param("i", $year);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $month = (int)$row['month'];
            $accountId = $row['account_id'];
            
            $response['data']['usd'][$accountId]['months'][$month] = array(
                'deposits' => floatval($row['deposits']),
                'withdrawals' => floatval($row['withdrawals']),
                'balance' => floatval($row['deposits'] + $row['withdrawals'])
            );
        }

        // Calculate running balances for each account
        foreach ($response['data']['usd'] as $accountId => &$accountData) {
            $runningBalance = 0;
            for ($month = 1; $month <= 12; $month++) {
                $runningBalance += $accountData['months'][$month]['deposits'] + $accountData['months'][$month]['withdrawals'];
                $accountData['months'][$month]['balance'] = $runningBalance;
            }
        }
    }

    // Fetch ETB transactions
    if (!empty($etbAccounts)) {
        // First get account details
        $accountQuery = "SELECT id, account_owner, account_platform FROM accounts WHERE id IN ($etbAccountIds)";
        $accountResult = $connect->query($accountQuery);
        while ($account = $accountResult->fetch_assoc()) {
            $response['data']['etb'][$account['id']] = initializeAccountData(
                $account['id'],
                $account['account_owner'],
                $account['account_platform']
            );
        }

        // Then get monthly transactions
        $etbQuery = "SELECT 
            d.account_id,
            MONTH(d.transaction_date) as month,
            SUM(CASE WHEN d.type = 'deposit' THEN d.amount ELSE 0 END) as deposits,
            SUM(CASE WHEN d.type = 'withdraw' THEN -d.amount ELSE 0 END) as withdrawals
        FROM digitalswap d
        WHERE d.account_id IN ($etbAccountIds)
        AND YEAR(d.transaction_date) = ?
        AND d.status = 1
        GROUP BY d.account_id, MONTH(d.transaction_date)
        ORDER BY d.account_id, MONTH(d.transaction_date)";

        $stmt = $connect->prepare($etbQuery);
        $stmt->bind_param("i", $year);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $month = (int)$row['month'];
            $accountId = $row['account_id'];
            
            $response['data']['etb'][$accountId]['months'][$month] = array(
                'deposits' => floatval($row['deposits']),
                'withdrawals' => floatval($row['withdrawals']),
                'balance' => floatval($row['deposits'] + $row['withdrawals'])
            );
        }

        // Calculate running balances for each account
        foreach ($response['data']['etb'] as $accountId => &$accountData) {
            $runningBalance = 0;
            for ($month = 1; $month <= 12; $month++) {
                $runningBalance += $accountData['months'][$month]['deposits'] + $accountData['months'][$month]['withdrawals'];
                $accountData['months'][$month]['balance'] = $runningBalance;
            }
        }
    }

    $response['success'] = true;

} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
}

// Return the response
echo json_encode($response); 