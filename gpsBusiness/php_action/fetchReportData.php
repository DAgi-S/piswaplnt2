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
                'revenue' => array(),
                'gross_profit' => array(),
                'net_profit' => array()
            ),
            'distribution' => array(
                'labels' => array(),
                'values' => array()
            )
        )
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
    $currency = isset($_POST['currency']) ? $_POST['currency'] : 'ETB';

    if (!$startDate || !$endDate) {
        throw new Exception("Start date and end date are required");
    }

    // Build the WHERE clause
    $where = array();
    $params = array();
    $types = '';

    $where[] = "bc.start_date >= ?";
    $params[] = $startDate;
    $types .= 's';

    $where[] = "bc.start_date <= ?";
    $params[] = $endDate;
    $types .= 's';

    if ($cycleId) {
        $where[] = "bc.id = ?";
        $params[] = $cycleId;
        $types .= 'i';
    }

    $whereClause = implode(" AND ", $where);

    // Fetch summary data
    $sql = "SELECT 
            SUM(bc.total_sales_etb) as total_revenue,
            SUM(bc.total_purchase_etb) as total_cost,
            SUM(bc.total_expenses_etb) as total_expenses,
            SUM(bc.total_sales_etb - bc.total_purchase_etb) as gross_profit,
            SUM(bc.net_profit_etb) as net_profit
            FROM gps_business_cycles bc
            WHERE $whereClause";

    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error preparing summary query: " . $connect->error);
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $summaryResult = $stmt->get_result();
    $summary = $summaryResult->fetch_assoc();

    // Convert to USD if needed
    if ($currency === 'USD') {
        // Fetch latest exchange rate
        $rateStmt = $connect->prepare("SELECT usd_to_etb_rate FROM gps_currency_rates ORDER BY date DESC LIMIT 1");
        $rateStmt->execute();
        $rateResult = $rateStmt->get_result();
        $rate = $rateResult->fetch_assoc()['usd_to_etb_rate'];

        foreach ($summary as &$value) {
            $value = $value / $rate;
        }
    }

    $response['data']['summary'] = array(
        'total_revenue' => $summary['total_revenue'] ?: 0,
        'total_cost' => $summary['total_cost'] ?: 0,
        'gross_profit' => $summary['gross_profit'] ?: 0,
        'total_expenses' => $summary['total_expenses'] ?: 0,
        'net_profit' => $summary['net_profit'] ?: 0,
        'currency' => $currency
    );

    // Fetch trend data (monthly breakdown)
    $sql = "SELECT 
            DATE_FORMAT(bc.start_date, '%Y-%m') as month,
            SUM(bc.total_sales_etb) as revenue,
            SUM(bc.total_sales_etb - bc.total_purchase_etb) as gross_profit,
            SUM(bc.net_profit_etb) as net_profit
            FROM gps_business_cycles bc
            WHERE $whereClause
            GROUP BY DATE_FORMAT(bc.start_date, '%Y-%m')
            ORDER BY month ASC";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $trendResult = $stmt->get_result();

    while ($row = $trendResult->fetch_assoc()) {
        $response['data']['charts']['trend']['labels'][] = $row['month'];
        if ($currency === 'USD') {
            $response['data']['charts']['trend']['revenue'][] = $row['revenue'] / $rate;
            $response['data']['charts']['trend']['gross_profit'][] = $row['gross_profit'] / $rate;
            $response['data']['charts']['trend']['net_profit'][] = $row['net_profit'] / $rate;
        } else {
            $response['data']['charts']['trend']['revenue'][] = $row['revenue'];
            $response['data']['charts']['trend']['gross_profit'][] = $row['gross_profit'];
            $response['data']['charts']['trend']['net_profit'][] = $row['net_profit'];
        }
    }

    // Fetch distribution data (revenue by cycle)
    $sql = "SELECT 
            bc.cycle_number,
            bc.total_sales_etb as revenue
            FROM gps_business_cycles bc
            WHERE $whereClause
            ORDER BY bc.total_sales_etb DESC
            LIMIT 5";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $distributionResult = $stmt->get_result();

    while ($row = $distributionResult->fetch_assoc()) {
        $response['data']['charts']['distribution']['labels'][] = $row['cycle_number'];
        $response['data']['charts']['distribution']['values'][] = $currency === 'USD' ? 
            $row['revenue'] / $rate : $row['revenue'];
    }

    $response['success'] = true;

} catch (Exception $e) {
    $response['messages'][] = $e->getMessage();
    if (!isset($response['error'])) {
        $response['error'] = "Error generating report: " . $e->getMessage();
    }

} finally {
    // Close statement if it exists
    if (isset($stmt)) {
        $stmt->close();
    }

    // Close rate statement if it exists
    if (isset($rateStmt)) {
        $rateStmt->close();
    }

    // Close connection
    if (isset($connect) && $connect->ping()) {
        $connect->close();
    }
}

echo json_encode($response);
exit; 