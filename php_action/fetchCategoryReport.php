<?php
require_once 'core.php';
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['userId']) || !isset($_SESSION['active_account'])) {
    echo json_encode([
        'error' => true,
        'message' => 'Unauthorized access'
    ]);
    exit();
}

$response = array(
    'error' => false,
    'message' => '',
    'annual_summary' => array(),
    'monthly' => array()
);

if(!isset($_POST['startYear']) || !isset($_POST['endYear'])) {
    $response['error'] = true;
    $response['message'] = 'Year range is required';
    echo json_encode($response);
    exit();
}

try {
    $startYear = $_POST['startYear'];
    $endYear = $_POST['endYear'];
    $categoryId = isset($_POST['categoryId']) ? $_POST['categoryId'] : '';
    $accountId = $_SESSION['active_account'];

    // Build category filter
    $categoryFilter = '';
    if($categoryId !== '') {
        $categoryFilter = " AND dtc.category_id = " . $connect->real_escape_string($categoryId);
    }

    // Get annual summary
    $sql = "SELECT 
                COALESCE(c.category_name, 'Uncategorized') as category_name,
                COUNT(*) as transaction_count,
                SUM(ds.amount) as total_amount
            FROM digitalswap ds
            LEFT JOIN digital_transaction_categories dtc ON ds.id = dtc.transaction_id
            LEFT JOIN digital_categories c ON dtc.category_id = c.category_id
            WHERE ds.account_id = ? 
            AND YEAR(ds.transaction_date) BETWEEN ? AND ?
            " . $categoryFilter . "
            GROUP BY COALESCE(c.category_name, 'Uncategorized')
            ORDER BY category_name ASC";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param("iii", $accountId, $startYear, $endYear);
    $stmt->execute();
    $result = $stmt->get_result();

    while($row = $result->fetch_assoc()) {
        $response['annual_summary'][] = array(
            'category_name' => $row['category_name'],
            'transaction_count' => $row['transaction_count'],
            'total_amount' => $row['total_amount']
        );
    }

    // Get monthly data
    for($month = 1; $month <= 12; $month++) {
        $sql = "SELECT 
                    DATE_FORMAT(ds.transaction_date, '%Y-%m-%d') as date,
                    COALESCE(tt.name, ds.type) as type,
                    ds.name,
                    ds.platform,
                    COALESCE(c.category_name, 'Uncategorized') as category,
                    ds.amount,
                    ds.comment
                FROM digitalswap ds
                LEFT JOIN digital_transaction_categories dtc ON ds.id = dtc.transaction_id
                LEFT JOIN digital_categories c ON dtc.category_id = c.category_id
                LEFT JOIN transaction_types tt ON ds.type_id = tt.id
                WHERE ds.account_id = ?
                AND YEAR(ds.transaction_date) BETWEEN ? AND ?
                AND MONTH(ds.transaction_date) = ?
                " . $categoryFilter . "
                ORDER BY ds.transaction_date ASC";

        $stmt = $connect->prepare($sql);
        $stmt->bind_param("iiii", $accountId, $startYear, $endYear, $month);
        $stmt->execute();
        $result = $stmt->get_result();

        $transactions = array();
        while($row = $result->fetch_assoc()) {
            $transactions[] = array(
                'date' => $row['date'],
                'type' => $row['type'],
                'name' => htmlspecialchars($row['name']),
                'platform' => htmlspecialchars($row['platform']),
                'category' => $row['category'],
                'comment' => htmlspecialchars($row['comment']),
                'amount' => $row['amount']
            );
        }

        $response['monthly'][] = array(
            'month' => $month,
            'transactions' => $transactions
        );
    }

} catch (Exception $e) {
    $response['error'] = true;
    $response['message'] = 'An error occurred: ' . $e->getMessage();
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($connect)) {
        $connect->close();
    }
}

echo json_encode($response); 