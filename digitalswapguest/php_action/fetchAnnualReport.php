<?php
require_once '../includes/core.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode([
        'error' => true,
        'message' => 'Authentication required'
    ]);
    exit();
}

// Get current user data and active account
$currentUser = getCurrentUser();
if (!$currentUser) {
    echo json_encode([
        'error' => true,
        'message' => 'User data not found'
    ]);
    exit();
}

// Verify active account access
if (!isset($_SESSION['active_guest_account'])) {
    echo json_encode([
        'error' => true,
        'message' => 'No active account selected'
    ]);
    exit();
}

try {
    // Get parameters
    $fromYear = isset($_POST['fromYear']) ? intval($_POST['fromYear']) : date('Y');
    $toYear = isset($_POST['toYear']) ? intval($_POST['toYear']) : date('Y');

    // Validate years
    if ($fromYear > $toYear) {
        echo json_encode([
            'error' => true,
            'message' => 'From Year cannot be greater than To Year'
        ]);
        exit();
    }

    // Get account currency
    $currencyQuery = "SELECT Currency FROM accounts WHERE id = ?";
    $stmt = $connect->prepare($currencyQuery);
    $stmt->bind_param("i", $_SESSION['active_guest_account']);
    $stmt->execute();
    $currencyResult = $stmt->get_result();
    $currencyRow = $currencyResult->fetch_assoc();
    $currency = $currencyRow['Currency'] ?? 'ETB';

    $yearlyData = [];
    $grandTotal = [
        'totalDeposits' => 0,
        'totalWithdraws' => 0,
        'netChange' => 0
    ];

    // Process each year
    for ($year = $fromYear; $year <= $toYear; $year++) {
        $yearData = [
            'year' => $year,
            'months' => [],
            'summary' => [
                'totalDeposits' => 0,
                'totalWithdrawals' => 0,
                'netChange' => 0
            ]
        ];

        // Get monthly data
        $monthlyQuery = "SELECT 
            MONTH(transaction_date) as month,
            SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END) as deposits,
            SUM(CASE WHEN type = 'withdraw' THEN amount ELSE 0 END) as withdrawals
        FROM digitalswap 
        WHERE YEAR(transaction_date) = ? 
        AND account_id = ?
        GROUP BY MONTH(transaction_date)
        ORDER BY MONTH(transaction_date)";

        $stmt = $connect->prepare($monthlyQuery);
        $stmt->bind_param("ii", $year, $_SESSION['active_guest_account']);
        $stmt->execute();
        $monthlyResult = $stmt->get_result();

        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];

        // Initialize all months with zero values
        foreach ($months as $monthNum => $monthName) {
            $yearData['months'][] = [
                'name' => $monthName,
                'deposits' => '0.00',
                'withdrawals' => '0.00',
                'netChange' => '0.00'
            ];
        }

        // Fill in actual data
        while ($row = $monthlyResult->fetch_assoc()) {
            $monthIndex = $row['month'] - 1;
            $deposits = floatval($row['deposits']);
            $withdrawals = floatval($row['withdrawals']);
            $netChange = $deposits - $withdrawals;

            $yearData['months'][$monthIndex] = [
                'name' => $months[$row['month']],
                'deposits' => number_format($deposits, 2),
                'withdrawals' => number_format($withdrawals, 2),
                'netChange' => number_format($netChange, 2)
            ];

            // Update year summary
            $yearData['summary']['totalDeposits'] += $deposits;
            $yearData['summary']['totalWithdrawals'] += $withdrawals;
            $yearData['summary']['netChange'] += $netChange;
        }

        // Format year summary
        $yearData['summary']['totalDeposits'] = number_format($yearData['summary']['totalDeposits'], 2);
        $yearData['summary']['totalWithdrawals'] = number_format($yearData['summary']['totalWithdrawals'], 2);
        $yearData['summary']['netChange'] = number_format($yearData['summary']['netChange'], 2);

        // Update grand totals
        $grandTotal['totalDeposits'] += floatval(str_replace(',', '', $yearData['summary']['totalDeposits']));
        $grandTotal['totalWithdraws'] += floatval(str_replace(',', '', $yearData['summary']['totalWithdrawals']));
        $grandTotal['netChange'] += floatval(str_replace(',', '', $yearData['summary']['netChange']));

        $yearlyData[] = $yearData;
    }

    // Format grand totals
    $grandTotal['totalDeposits'] = number_format($grandTotal['totalDeposits'], 2);
    $grandTotal['totalWithdraws'] = number_format($grandTotal['totalWithdraws'], 2);
    $grandTotal['netChange'] = number_format($grandTotal['netChange'], 2);

    echo json_encode([
        'error' => false,
        'yearly' => $yearlyData,
        'grandTotal' => $grandTotal,
        'currency' => $currency
    ]);

} catch (Exception $e) {
    error_log("Error in fetchAnnualReport.php: " . $e->getMessage());
    echo json_encode([
        'error' => true,
        'message' => 'An error occurred while generating the report.'
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($connect)) {
        $connect->close();
    }
} 