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
    'data' => array()
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

    // Fetch profit and loss data
    $sql = "SELECT 
            bc.id,
            bc.cycle_number,
            bc.start_date as date,
            bc.total_sales_etb as revenue,
            bc.total_purchase_etb as cost_of_sales,
            (bc.total_sales_etb - bc.total_purchase_etb) as gross_profit,
            bc.total_expenses_etb as expenses,
            bc.net_profit_etb as net_profit
            FROM gps_business_cycles bc
            WHERE $whereClause
            ORDER BY bc.start_date DESC";

    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error preparing query: " . $connect->error);
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    // Convert to USD if needed
    if ($currency === 'USD') {
        // Fetch latest exchange rate
        $rateStmt = $connect->prepare("SELECT usd_to_etb_rate FROM gps_currency_rates ORDER BY date DESC LIMIT 1");
        $rateStmt->execute();
        $rateResult = $rateStmt->get_result();
        $rate = $rateResult->fetch_assoc()['usd_to_etb_rate'];
    }

    while ($row = $result->fetch_assoc()) {
        if ($currency === 'USD') {
            $row['revenue'] = $row['revenue'] / $rate;
            $row['cost_of_sales'] = $row['cost_of_sales'] / $rate;
            $row['gross_profit'] = $row['gross_profit'] / $rate;
            $row['expenses'] = $row['expenses'] / $rate;
            $row['net_profit'] = $row['net_profit'] / $rate;
        }

        $response['data'][] = array(
            'id' => $row['id'],
            'date' => $row['date'],
            'cycle_number' => $row['cycle_number'],
            'revenue' => $row['revenue'],
            'cost_of_sales' => $row['cost_of_sales'],
            'gross_profit' => $row['gross_profit'],
            'expenses' => $row['expenses'],
            'net_profit' => $row['net_profit'],
            'currency' => $currency
        );
    }

} catch (Exception $e) {
    $response['error'] = "Error fetching profit and loss data: " . $e->getMessage();

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