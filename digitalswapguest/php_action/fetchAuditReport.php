<?php
require_once '../includes/core.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['guest_id']) || !isset($_SESSION['active_guest_account']) || !isset($_POST['year'])) {
    error_log("Missing required session parameters in fetchAuditReport.php");
    error_log("SESSION: " . print_r($_SESSION, true));
    error_log("POST: " . print_r($_POST, true));
    echo json_encode(['error' => true, 'message' => 'Invalid request parameters']);
    exit();
}

try {
    // Get guest user's active account data
    $sql = "SELECT gu.*, a.account_owner, a.account_platform, a.Currency 
            FROM guest_users gu 
            JOIN guest_account_links gal ON gu.id = gal.guest_id
            JOIN accounts a ON gal.account_id = a.id 
            WHERE gu.id = ? AND gal.account_id = ?";
    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Failed to prepare account query: " . $connect->error);
    }
    
    $stmt->bind_param("ii", $_SESSION['guest_id'], $_SESSION['active_guest_account']);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute account query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $guestData = $result->fetch_assoc();

    if (!$guestData) {
        throw new Exception("No account data found");
    }

    $year = $_POST['year'];
    $monthly = [];
    $annualSummary = [
        'totalDeposits' => 0.00,
        'totalWithdrawals' => 0.00,
        'netChange' => 0.00
    ];

    // Get annual totals first
    $sql = "SELECT 
        COALESCE(SUM(CASE WHEN LOWER(type) = 'deposit' THEN amount ELSE 0 END), 0) as total_deposits,
        COALESCE(SUM(CASE WHEN LOWER(type) = 'withdraw' THEN amount ELSE 0 END), 0) as total_withdrawals
    FROM digitalswap 
    WHERE account_id = ? AND YEAR(transaction_date) = ?";
    
    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Failed to prepare annual totals query: " . $connect->error);
    }
    
    $stmt->bind_param("ii", $_SESSION['active_guest_account'], $year);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute annual totals query: " . $stmt->error);
    }
    
    $annualResult = $stmt->get_result();
    $annualData = $annualResult->fetch_assoc();
    
    $annualSummary['totalDeposits'] = floatval($annualData['total_deposits']);
    $annualSummary['totalWithdrawals'] = floatval($annualData['total_withdrawals']);
    $annualSummary['netChange'] = $annualSummary['totalDeposits'] - $annualSummary['totalWithdrawals'];

    // Process each month
    for ($month = 1; $month <= 12; $month++) {
        $monthData = [
            'month' => $month,
            'transactions' => [],
            'summary' => [
                'deposits' => 0.00,
                'withdrawals' => 0.00,
                'netChange' => 0.00
            ]
        ];

        // Get transactions for this month
        $sql = "SELECT 
            d.id,
            DATE_FORMAT(d.transaction_date, '%Y-%m-%d') as date,
            LOWER(d.type) as type,
            d.name,
            d.platform,
            COALESCE(d.amount, 0) as amount,
            d.status,
            d.comment,
            tt.name as reference,
            COALESCE(d.name, '') as description
        FROM digitalswap d
        LEFT JOIN transaction_types tt ON d.type_id = tt.id
        WHERE d.account_id = ? 
        AND YEAR(d.transaction_date) = ?
        AND MONTH(d.transaction_date) = ?
        AND d.status = 1
        ORDER BY d.transaction_date ASC";

        $stmt = $connect->prepare($sql);
        if (!$stmt) {
            throw new Exception("Failed to prepare monthly transactions query: " . $connect->error);
        }
        
        $stmt->bind_param("iii", $_SESSION['active_guest_account'], $year, $month);
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute monthly transactions query: " . $stmt->error);
        }
        
        $transactions = $stmt->get_result();

        $runningBalance = 0;
        while ($trans = $transactions->fetch_assoc()) {
            $amount = floatval($trans['amount']);
            
            // Calculate running balance
            if ($trans['type'] === 'deposit') {
                $runningBalance += $amount;
                $monthData['summary']['deposits'] += $amount;
            } else {
                $runningBalance -= $amount;
                $monthData['summary']['withdrawals'] += $amount;
            }

            // Build description
            $description = $trans['name'];
            if (!empty($trans['platform'])) {
                $description .= ' via ' . $trans['platform'];
            }
            if (!empty($trans['comment'])) {
                $description .= '<br><small class="text-muted">' . nl2br(htmlspecialchars($trans['comment'])) . '</small>';
            }

            $monthData['transactions'][] = [
                'date' => $trans['date'],
                'reference' => $trans['reference'] ?? $trans['type'],
                'type' => ucfirst($trans['type']),
                'description' => $description,
                'amount' => $guestData['Currency'] . ' ' . number_format($amount, 2),
                'balance' => $guestData['Currency'] . ' ' . number_format($runningBalance, 2)
            ];
        }

        $monthData['summary']['netChange'] = $monthData['summary']['deposits'] - $monthData['summary']['withdrawals'];
        
        // Format summary values
        $monthData['summary']['deposits'] = number_format($monthData['summary']['deposits'], 2, '.', '');
        $monthData['summary']['withdrawals'] = number_format($monthData['summary']['withdrawals'], 2, '.', '');
        $monthData['summary']['netChange'] = number_format($monthData['summary']['netChange'], 2, '.', '');
        
        $monthly[] = $monthData;
    }

    // Format annual summary values
    $annualSummary['totalDeposits'] = number_format($annualSummary['totalDeposits'], 2, '.', '');
    $annualSummary['totalWithdrawals'] = number_format($annualSummary['totalWithdrawals'], 2, '.', '');
    $annualSummary['netChange'] = number_format($annualSummary['netChange'], 2, '.', '');

    echo json_encode([
        'error' => false,
        'monthly' => $monthly,
        'annualSummary' => $annualSummary,
        'currency' => $guestData['Currency']
    ]);

} catch (Exception $e) {
    error_log("Error in fetchAuditReport.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'error' => true,
        'message' => 'An error occurred while generating the report: ' . $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($connect)) {
        $connect->close();
    }
}
?> 