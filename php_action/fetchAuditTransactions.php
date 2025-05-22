<?php
require_once 'core.php';
require_once 'db_connect.php';

// Set header to JSON
header('Content-Type: application/json');

// Get parameters
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$usdAccounts = isset($_GET['usd_accounts']) ? $_GET['usd_accounts'] : [];
$etbAccounts = isset($_GET['etb_accounts']) ? $_GET['etb_accounts'] : [];

$response = array();

try {
    // Fetch USD transactions for the month
    $usdTransactions = [];
    if (!empty($usdAccounts)) {
        $placeholders = str_repeat('?,', count($usdAccounts) - 1) . '?';
        $usdQuery = "SELECT 
                        d.transaction_date,
                        d.name,
                        d.type,
                        d.platform,
                        d.amount,
                        d.comment
                    FROM digitalswap d
                    JOIN accounts a ON d.account_id = a.id
                    WHERE MONTH(d.transaction_date) = ? 
                    AND YEAR(d.transaction_date) = ?
                    AND d.account_id IN ($placeholders)
                    AND d.status = 1
                    ORDER BY d.transaction_date ASC";

        $stmt = $connect->prepare($usdQuery);
        $params = array_merge([$month, $year], $usdAccounts);
        $types = str_repeat('i', count($params));
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $usdResult = $stmt->get_result();
        $usdTransactions = $usdResult->fetch_all(MYSQLI_ASSOC);
    }

    // Fetch ETB transactions for the month
    $etbTransactions = [];
    if (!empty($etbAccounts)) {
        $placeholders = str_repeat('?,', count($etbAccounts) - 1) . '?';
        $etbQuery = "SELECT 
                        d.transaction_date,
                        d.name,
                        d.type,
                        d.platform,
                        d.amount,
                        d.comment
                    FROM digitalswap d
                    JOIN accounts a ON d.account_id = a.id
                    WHERE MONTH(d.transaction_date) = ? 
                    AND YEAR(d.transaction_date) = ?
                    AND d.account_id IN ($placeholders)
                    AND d.status = 1
                    ORDER BY d.transaction_date ASC";

        $stmt = $connect->prepare($etbQuery);
        $params = array_merge([$month, $year], $etbAccounts);
        $types = str_repeat('i', count($params));
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $etbResult = $stmt->get_result();
        $etbTransactions = $etbResult->fetch_all(MYSQLI_ASSOC);
    }

    // Calculate monthly totals for USD
    $usdTotals = [
        'total_deposit' => 0,
        'total_withdraw' => 0,
        'monthly_closing' => 0
    ];
    
    if (!empty($usdAccounts)) {
        $placeholders = str_repeat('?,', count($usdAccounts) - 1) . '?';
        $usdTotalsQuery = "SELECT 
                            SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END) as total_deposit,
                            SUM(CASE WHEN type = 'withdraw' THEN amount ELSE 0 END) as total_withdraw,
                            SUM(CASE WHEN type = 'deposit' THEN amount ELSE -amount END) as monthly_closing
                        FROM digitalswap d
                        JOIN accounts a ON d.account_id = a.id
                        WHERE MONTH(d.transaction_date) = ? 
                        AND YEAR(d.transaction_date) = ?
                        AND d.account_id IN ($placeholders)
                        AND d.status = 1";

        $stmt = $connect->prepare($usdTotalsQuery);
        $params = array_merge([$month, $year], $usdAccounts);
        $types = str_repeat('i', count($params));
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $usdTotals = $stmt->get_result()->fetch_assoc();
    }

    // Calculate monthly totals for ETB
    $etbTotals = [
        'total_deposit' => 0,
        'total_withdraw' => 0,
        'monthly_closing' => 0
    ];
    
    if (!empty($etbAccounts)) {
        $placeholders = str_repeat('?,', count($etbAccounts) - 1) . '?';
        $etbTotalsQuery = "SELECT 
                            SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END) as total_deposit,
                            SUM(CASE WHEN type = 'withdraw' THEN amount ELSE 0 END) as total_withdraw,
                            SUM(CASE WHEN type = 'deposit' THEN amount ELSE -amount END) as monthly_closing
                        FROM digitalswap d
                        JOIN accounts a ON d.account_id = a.id
                        WHERE MONTH(d.transaction_date) = ? 
                        AND YEAR(d.transaction_date) = ?
                        AND d.account_id IN ($placeholders)
                        AND d.status = 1";

        $stmt = $connect->prepare($etbTotalsQuery);
        $params = array_merge([$month, $year], $etbAccounts);
        $types = str_repeat('i', count($params));
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $etbTotals = $stmt->get_result()->fetch_assoc();
    }

    // Fetch monthly summary for the year
    $monthlySummary = [
        'usd' => array_fill(0, 12, ['deposit' => 0, 'withdraw' => 0]),
        'etb' => array_fill(0, 12, ['deposit' => 0, 'withdraw' => 0])
    ];

    if (!empty($usdAccounts)) {
        $placeholders = str_repeat('?,', count($usdAccounts) - 1) . '?';
        $usdSummaryQuery = "SELECT 
                            MONTH(d.transaction_date) as month,
                            SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END) as deposit,
                            SUM(CASE WHEN type = 'withdraw' THEN amount ELSE 0 END) as withdraw
                        FROM digitalswap d
                        JOIN accounts a ON d.account_id = a.id
                        WHERE YEAR(d.transaction_date) = ?
                        AND d.account_id IN ($placeholders)
                        AND d.status = 1
                        GROUP BY MONTH(d.transaction_date)
                        ORDER BY MONTH(d.transaction_date)";

        $stmt = $connect->prepare($usdSummaryQuery);
        $params = array_merge([$year], $usdAccounts);
        $types = str_repeat('i', count($params));
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $summaryResult = $stmt->get_result();
        
        while ($row = $summaryResult->fetch_assoc()) {
            $monthIndex = intval($row['month']) - 1;
            $monthlySummary['usd'][$monthIndex] = [
                'deposit' => $row['deposit'],
                'withdraw' => $row['withdraw']
            ];
        }
    }

    if (!empty($etbAccounts)) {
        $placeholders = str_repeat('?,', count($etbAccounts) - 1) . '?';
        $etbSummaryQuery = "SELECT 
                            MONTH(d.transaction_date) as month,
                            SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END) as deposit,
                            SUM(CASE WHEN type = 'withdraw' THEN amount ELSE 0 END) as withdraw
                        FROM digitalswap d
                        JOIN accounts a ON d.account_id = a.id
                        WHERE YEAR(d.transaction_date) = ?
                        AND d.account_id IN ($placeholders)
                        AND d.status = 1
                        GROUP BY MONTH(d.transaction_date)
                        ORDER BY MONTH(d.transaction_date)";

        $stmt = $connect->prepare($etbSummaryQuery);
        $params = array_merge([$year], $etbAccounts);
        $types = str_repeat('i', count($params));
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $summaryResult = $stmt->get_result();
        
        while ($row = $summaryResult->fetch_assoc()) {
            $monthIndex = intval($row['month']) - 1;
            $monthlySummary['etb'][$monthIndex] = [
                'deposit' => $row['deposit'],
                'withdraw' => $row['withdraw']
            ];
        }
    }

    // Calculate yearly totals
    $yearTotals = [
        'usd_year_totals' => [
            'total_deposit' => 0,
            'total_withdraw' => 0,
            'yearly_closing' => 0
        ],
        'etb_year_totals' => [
            'total_deposit' => 0,
            'total_withdraw' => 0,
            'yearly_closing' => 0
        ]
    ];

    if (!empty($usdAccounts)) {
        $placeholders = str_repeat('?,', count($usdAccounts) - 1) . '?';
        $usdYearlyQuery = "SELECT 
                            SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END) as total_deposit,
                            SUM(CASE WHEN type = 'withdraw' THEN amount ELSE 0 END) as total_withdraw,
                            SUM(CASE WHEN type = 'deposit' THEN amount ELSE -amount END) as yearly_closing
                        FROM digitalswap d
                        JOIN accounts a ON d.account_id = a.id
                        WHERE YEAR(d.transaction_date) = ?
                        AND d.account_id IN ($placeholders)
                        AND d.status = 1";

        $stmt = $connect->prepare($usdYearlyQuery);
        $params = array_merge([$year], $usdAccounts);
        $types = str_repeat('i', count($params));
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $yearTotals['usd_year_totals'] = $stmt->get_result()->fetch_assoc();
    }

    if (!empty($etbAccounts)) {
        $placeholders = str_repeat('?,', count($etbAccounts) - 1) . '?';
        $etbYearlyQuery = "SELECT 
                            SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END) as total_deposit,
                            SUM(CASE WHEN type = 'withdraw' THEN amount ELSE 0 END) as total_withdraw,
                            SUM(CASE WHEN type = 'deposit' THEN amount ELSE -amount END) as yearly_closing
                        FROM digitalswap d
                        JOIN accounts a ON d.account_id = a.id
                        WHERE YEAR(d.transaction_date) = ?
                        AND d.account_id IN ($placeholders)
                        AND d.status = 1";

        $stmt = $connect->prepare($etbYearlyQuery);
        $params = array_merge([$year], $etbAccounts);
        $types = str_repeat('i', count($params));
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $yearTotals['etb_year_totals'] = $stmt->get_result()->fetch_assoc();
    }

    // Prepare response
    $response['success'] = true;
    $response['data'] = array(
        'usd' => $usdTransactions,
        'etb' => $etbTransactions,
        'usd_totals' => $usdTotals,
        'etb_totals' => $etbTotals,
        'monthly_summary' => array_merge($monthlySummary, $yearTotals)
    );

} catch (Exception $e) {
    $response['success'] = false;
    $response['messages'] = $e->getMessage();
}

echo json_encode($response);
$connect->close(); 