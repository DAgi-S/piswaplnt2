<?php
require_once 'db_connect.php';
header('Content-Type: application/json');

try {
    $conditions = [];
    $params = [];
    $types = '';

    if (!empty($_GET['start_date'])) {
        $conditions[] = "transaction_date >= ?";
        $params[] = $_GET['start_date'];
        $types .= 's';
    }
    if (!empty($_GET['end_date'])) {
        $conditions[] = "transaction_date <= ?";
        $params[] = $_GET['end_date'];
        $types .= 's';
    }
    if (!empty($_GET['account_id'])) {
        $conditions[] = "account_id = ?";
        $params[] = $_GET['account_id'];
        $types .= 'i';
    }
    if (!empty($_GET['platform'])) {
        $conditions[] = "platform = ?";
        $params[] = $_GET['platform'];
        $types .= 's';
    }

    $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
    $period = $_GET['period'] ?? 'daily';

    // Transaction Volume Data
    $groupBy = match($period) {
        'weekly' => "DATE_FORMAT(transaction_date, '%Y-%u')",
        'monthly' => "DATE_FORMAT(transaction_date, '%Y-%m')",
        default => "DATE(transaction_date)" // daily
    };

    $query = "SELECT 
        $groupBy as date,
        SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END) as deposits,
        SUM(CASE WHEN type = 'withdraw' THEN ABS(amount) ELSE 0 END) as withdrawals
        FROM digitalswap
        $whereClause
        GROUP BY date
        ORDER BY date";

    $stmt = $connect->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $transactionData = [];
    while ($row = $result->fetch_assoc()) {
        $transactionData[] = [
            'date' => $row['date'],
            'deposits' => (float)$row['deposits'],
            'withdrawals' => (float)$row['withdrawals']
        ];
    }

    // Platform Distribution Data
    $platformQuery = "SELECT 
        platform,
        COUNT(*) as total
        FROM digitalswap
        $whereClause
        GROUP BY platform
        ORDER BY total DESC";

    $stmt = $connect->prepare($platformQuery);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $platformData = [];
    while ($row = $result->fetch_assoc()) {
        $platformData[] = [
            'platform' => $row['platform'],
            'total' => (int)$row['total']
        ];
    }

    echo json_encode([
        'success' => true,
        'transactionData' => $transactionData,
        'platformData' => $platformData
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 