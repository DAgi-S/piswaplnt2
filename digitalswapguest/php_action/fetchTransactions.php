<?php
require_once '../includes/core.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode([
        'error' => true,
        'message' => 'Authentication required'
    ]);
    exit();
}

try {
    // Get filter parameters
    $startDate = isset($_POST['startDate']) ? $_POST['startDate'] : null;
    $endDate = isset($_POST['endDate']) ? $_POST['endDate'] : null;
    $type = isset($_POST['type']) ? $_POST['type'] : null;
    $accountId = $_SESSION['active_guest_account'] ?? null;

    if (!$accountId) {
        throw new Exception("No active account selected");
    }

    // First get the account currency
    $currencySQL = "SELECT Currency FROM accounts WHERE id = ?";
    $stmt = $connect->prepare($currencySQL);
    $stmt->bind_param("i", $accountId);
    $stmt->execute();
    $currencyResult = $stmt->get_result();
    $currencyRow = $currencyResult->fetch_assoc();
    $currency = $currencyRow['Currency'] ?? 'ETB';
    $stmt->close();

    // Build base query for transactions
    $sql = "SELECT DISTINCT d.id, d.transaction_date, d.name, d.type, d.platform, d.amount, d.status, d.comment, d.image, a.Currency 
            FROM digitalswap d
            JOIN accounts a ON d.account_id = a.id
            LEFT JOIN guest_account_links gal ON a.id = gal.account_id
            WHERE gal.guest_id = ? AND d.account_id = ?";
    $params = [$_SESSION['guest_id'], $accountId];
    $types = "ii";

    // Add date filters if provided
    if ($startDate) {
        $sql .= " AND DATE(d.transaction_date) >= ?";
        $params[] = $startDate;
        $types .= "s";
    }
    if ($endDate) {
        $sql .= " AND DATE(d.transaction_date) <= ?";
        $params[] = $endDate;
        $types .= "s";
    }
    if ($type) {
        $sql .= " AND d.type = ?";
        $params[] = $type;
        $types .= "s";
    }

    // Get summary totals first
    $summarySQL = "SELECT 
        COUNT(DISTINCT d.id) as totalTransactions,
        COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END), 0) as totalDeposits,
        COALESCE(SUM(CASE WHEN type = 'withdraw' THEN amount ELSE 0 END), 0) as totalWithdraws,
        COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE -amount END), 0) as netChange
    FROM digitalswap d
    WHERE d.account_id = ?";
    
    // Add the same date and type filters to summary
    $summaryParams = [$accountId];
    $summaryTypes = "i";
    
    if ($startDate) {
        $summarySQL .= " AND DATE(d.transaction_date) >= ?";
        $summaryParams[] = $startDate;
        $summaryTypes .= "s";
    }
    if ($endDate) {
        $summarySQL .= " AND DATE(d.transaction_date) <= ?";
        $summaryParams[] = $endDate;
        $summaryTypes .= "s";
    }
    if ($type) {
        $summarySQL .= " AND d.type = ?";
        $summaryParams[] = $type;
        $summaryTypes .= "s";
    }

    // Get summary data
    $summaryStmt = $connect->prepare($summarySQL);
    $summaryStmt->bind_param($summaryTypes, ...$summaryParams);
    $summaryStmt->execute();
    $summaryResult = $summaryStmt->get_result();
    $summary = $summaryResult->fetch_assoc();
    $summaryStmt->close();

    // Add order by to the main transaction query
    $sql .= " ORDER BY d.transaction_date DESC, d.id DESC";

    // Get transaction data
    $stmt = $connect->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    // Prepare data array
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'id' => $row['id'],
            'transaction_date' => date('M d, Y', strtotime($row['transaction_date'])),
            'name' => $row['name'],
            'type' => ucfirst($row['type']),
            'platform' => $row['platform'],
            'amount' => $row['amount'],
            'status' => $row['status'],
            'comment' => htmlspecialchars($row['comment'] ?? ''),
            'image' => $row['image'],
            'currency' => $row['Currency']
        ];
    }

    // Format summary amounts
    $formattedSummary = [
        'totalTransactions' => (int)$summary['totalTransactions'],
        'totalDeposits' => number_format($summary['totalDeposits'], 2) . ' ' . $currency,
        'totalWithdraws' => number_format($summary['totalWithdraws'], 2) . ' ' . $currency,
        'netChange' => number_format($summary['netChange'], 2) . ' ' . $currency
    ];

    echo json_encode([
        'data' => $data,
        'summary' => $formattedSummary
    ]);

} catch (Exception $e) {
    error_log("Error in fetchTransactions.php: " . $e->getMessage());
    echo json_encode([
        'error' => true,
        'message' => DISPLAY_ERRORS ? $e->getMessage() : 'An error occurred while fetching transactions.',
        'data' => [],
        'summary' => [
            'totalTransactions' => 0,
            'totalDeposits' => '0.00 ' . ($currency ?? 'ETB'),
            'totalWithdraws' => '0.00 ' . ($currency ?? 'ETB'),
            'netChange' => '0.00 ' . ($currency ?? 'ETB')
        ]
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($connect)) {
        $connect->close();
    }
} 