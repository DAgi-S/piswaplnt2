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
                'distributions' => array()
            ),
            'investors' => array(
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
    $investorId = isset($_POST['investor_id']) ? intval($_POST['investor_id']) : null;
    $status = isset($_POST['status']) ? $_POST['status'] : null;

    if (!$startDate || !$endDate) {
        throw new Exception("Start date and end date are required");
    }

    // Build the WHERE clause
    $where = array();
    $params = array();
    $types = '';

    $where[] = "pd.distribution_date >= ?";
    $params[] = $startDate;
    $types .= 's';

    $where[] = "pd.distribution_date <= ?";
    $params[] = $endDate;
    $types .= 's';

    if ($investorId) {
        $where[] = "pd.investor_id = ?";
        $params[] = $investorId;
        $types .= 'i';
    }

    if ($status) {
        $where[] = "pd.status = ?";
        $params[] = $status;
        $types .= 's';
    }

    $whereClause = implode(" AND ", $where);

    // Fetch summary data
    $sql = "SELECT 
            SUM(pd.amount_etb) as total_distributed,
            AVG(pd.amount_etb) as average_distribution,
            COUNT(CASE WHEN pd.status = 'completed' THEN 1 END) as completed_count,
            COUNT(CASE WHEN pd.status = 'pending' THEN 1 END) as pending_count
            FROM gps_profit_distributions pd
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
        'total_distributed' => $summary['total_distributed'] ?: 0,
        'average_distribution' => $summary['average_distribution'] ?: 0,
        'completed_count' => $summary['completed_count'] ?: 0,
        'pending_count' => $summary['pending_count'] ?: 0
    );

    // Fetch trend data (monthly breakdown)
    $sql = "SELECT 
            DATE_FORMAT(pd.distribution_date, '%Y-%m') as month,
            SUM(pd.amount_etb) as distributions
            FROM gps_profit_distributions pd
            WHERE $whereClause
            GROUP BY DATE_FORMAT(pd.distribution_date, '%Y-%m')
            ORDER BY month ASC";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $trendResult = $stmt->get_result();

    while ($row = $trendResult->fetch_assoc()) {
        $response['data']['charts']['trend']['labels'][] = $row['month'];
        $response['data']['charts']['trend']['distributions'][] = $row['distributions'];
    }

    // Fetch distribution by investor
    $sql = "SELECT 
            i.name as investor_name,
            SUM(pd.amount_etb) as total_amount
            FROM gps_profit_distributions pd
            JOIN gps_investors i ON pd.investor_id = i.id
            WHERE $whereClause
            GROUP BY pd.investor_id, i.name
            ORDER BY total_amount DESC";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $investorsResult = $stmt->get_result();

    while ($row = $investorsResult->fetch_assoc()) {
        $response['data']['charts']['investors']['labels'][] = $row['investor_name'];
        $response['data']['charts']['investors']['values'][] = $row['total_amount'];
    }

    // Fetch table data
    $sql = "SELECT 
            pd.distribution_date,
            bc.cycle_number,
            i.name as investor_name,
            i.share_percentage,
            pd.amount_etb,
            pd.status,
            pd.reinvested
            FROM gps_profit_distributions pd
            JOIN gps_business_cycles bc ON pd.business_cycle_id = bc.id
            JOIN gps_investors i ON pd.investor_id = i.id
            WHERE $whereClause
            ORDER BY pd.distribution_date DESC";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $tableResult = $stmt->get_result();

    while ($row = $tableResult->fetch_assoc()) {
        $row['reinvested'] = $row['reinvested'] ? 'Yes' : 'No';
        $response['data']['table'][] = $row;
    }

    $response['success'] = true;

} catch (Exception $e) {
    $response['messages'][] = $e->getMessage();
    if (!isset($response['error'])) {
        $response['error'] = "Error generating investor report: " . $e->getMessage();
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