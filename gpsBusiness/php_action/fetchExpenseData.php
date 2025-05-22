<?php
session_start();

// Include database connection
require_once '../../php_action/db_connect.php';
require_once '../../php_action/core.php';

// Set headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Initialize response array
$response = array(
    'success' => false,
    'messages' => array(),
    'data' => array(
        'summary' => array(),
        'charts' => array(
            'trend' => array(
                'labels' => array(),
                'expenses' => array()
            ),
            'distribution' => array(
                'labels' => array(),
                'values' => array()
            )
        ),
        'table' => array()
    )
);

try {
    // Check if user is logged in
    if (!isset($_SESSION['userId'])) {
        http_response_code(401);
        throw new Exception("Unauthorized access");
    }

    // Validate input parameters
    $startDate = isset($_POST['start_date']) ? $_POST['start_date'] : null;
    $endDate = isset($_POST['end_date']) ? $_POST['end_date'] : null;
    $cycleId = isset($_POST['cycle_id']) ? intval($_POST['cycle_id']) : null;
    $expenseType = isset($_POST['expense_type']) ? $_POST['expense_type'] : null;

    if (!$startDate || !$endDate) {
        throw new Exception("Start date and end date are required");
    }

    // Build the WHERE clause
    $where = array();
    $params = array();
    $types = '';

    $where[] = "e.expense_date >= ?";
    $params[] = $startDate;
    $types .= 's';

    $where[] = "e.expense_date <= ?";
    $params[] = $endDate;
    $types .= 's';

    if ($cycleId) {
        $where[] = "e.business_cycle_id = ?";
        $params[] = $cycleId;
        $types .= 'i';
    }

    if ($expenseType) {
        $where[] = "e.expense_type = ?";
        $params[] = $expenseType;
        $types .= 's';
    }

    $whereClause = implode(" AND ", $where);

    // Fetch summary data
    $sql = "SELECT 
            COUNT(DISTINCT e.id) as total_expenses,
            SUM(e.amount_etb) as total_amount,
            AVG(e.amount_etb) as average_expense,
            MAX(e.amount_etb) as highest_expense
            FROM gps_business_expenses e
            WHERE $whereClause";

    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error preparing summary query: " . $connect->error);
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $summaryResult = $stmt->get_result();
    $summary = $summaryResult->fetch_assoc();

    $response['data']['summary'] = array(
        'total_expenses' => $summary['total_expenses'] ?: 0,
        'total_amount' => $summary['total_amount'] ?: 0,
        'average_expense' => $summary['average_expense'] ?: 0,
        'highest_expense' => $summary['highest_expense'] ?: 0
    );

    // Fetch trend data (monthly breakdown)
    $sql = "SELECT 
            DATE_FORMAT(e.expense_date, '%Y-%m') as month,
            SUM(e.amount_etb) as expenses
            FROM gps_business_expenses e
            WHERE $whereClause
            GROUP BY DATE_FORMAT(e.expense_date, '%Y-%m')
            ORDER BY month ASC";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $trendResult = $stmt->get_result();

    while ($row = $trendResult->fetch_assoc()) {
        $response['data']['charts']['trend']['labels'][] = $row['month'];
        $response['data']['charts']['trend']['expenses'][] = $row['expenses'];
    }

    // Fetch expense distribution by type
    $sql = "SELECT 
            e.expense_type,
            SUM(e.amount_etb) as total_amount
            FROM gps_business_expenses e
            WHERE $whereClause
            GROUP BY e.expense_type
            ORDER BY total_amount DESC";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $distributionResult = $stmt->get_result();

    while ($row = $distributionResult->fetch_assoc()) {
        $response['data']['charts']['distribution']['labels'][] = $row['expense_type'] ?: 'Other';
        $response['data']['charts']['distribution']['values'][] = $row['total_amount'];
    }

    // Fetch table data
    $sql = "SELECT 
            e.expense_date,
            bc.cycle_number,
            e.description,
            e.expense_type,
            e.amount_etb,
            e.payment_method,
            e.reference_number
            FROM gps_business_expenses e
            JOIN gps_business_cycles bc ON e.business_cycle_id = bc.id
            WHERE $whereClause
            ORDER BY e.expense_date DESC";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $tableResult = $stmt->get_result();

    while ($row = $tableResult->fetch_assoc()) {
        $response['data']['table'][] = $row;
    }

    $response['success'] = true;

} catch (Exception $e) {
    $response['messages'][] = $e->getMessage();
    if (!isset($response['error'])) {
        $response['error'] = "Error generating expense report: " . $e->getMessage();
    }

} finally {
    // Close statement if it exists
    if (isset($stmt)) {
        $stmt->close();
    }

    // Close connection
    if (isset($connect) && $connect->ping()) {
        $connect->close();
    }
}

echo json_encode($response);
exit; 