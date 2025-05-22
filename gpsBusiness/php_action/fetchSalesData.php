<?php
session_start();

// Include database connection
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
                'sales' => array(),
                'quantity' => array()
            ),
            'customers' => array(
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
    $currency = isset($_POST['currency']) ? $_POST['currency'] : 'ETB';

    if (!$startDate || !$endDate) {
        throw new Exception("Start date and end date are required");
    }

    // Build the WHERE clause
    $where = array();
    $params = array();
    $types = '';

    $where[] = "s.sale_date >= ?";
    $params[] = $startDate;
    $types .= 's';

    $where[] = "s.sale_date <= ?";
    $params[] = $endDate;
    $types .= 's';

    if ($cycleId) {
        $where[] = "bcs.business_cycle_id = ?";
        $params[] = $cycleId;
        $types .= 'i';
    }

    $whereClause = implode(" AND ", $where);

    // Fetch summary data
    $sql = "SELECT 
            COUNT(DISTINCT s.id) as total_sales,
            COALESCE(SUM(s.quantity), 0) as total_quantity,
            COALESCE(SUM(s.total), 0) as total_amount,
            COUNT(DISTINCT s.buyer_name) as total_customers,
            COALESCE(AVG(s.total), 0) as average_sale
            FROM gps_sales s
            LEFT JOIN gps_business_cycle_sales bcs ON s.id = bcs.sale_id
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

        $summary['total_amount'] = $summary['total_amount'] / $rate;
        $summary['average_sale'] = $summary['average_sale'] / $rate;
    }

    $response['data']['summary'] = array(
        'total_amount' => $summary['total_amount'] ?: 0,
        'total_quantity' => $summary['total_quantity'] ?: 0,
        'average_sale' => $summary['average_sale'] ?: 0,
        'total_customers' => $summary['total_customers'] ?: 0,
        'currency' => $currency
    );

    // Fetch trend data (monthly breakdown)
    $sql = "SELECT 
            DATE_FORMAT(s.sale_date, '%Y-%m') as month,
            COALESCE(SUM(s.total), 0) as sales,
            COALESCE(SUM(s.quantity), 0) as quantity
            FROM gps_sales s
            LEFT JOIN gps_business_cycle_sales bcs ON s.id = bcs.sale_id
            WHERE $whereClause
            GROUP BY DATE_FORMAT(s.sale_date, '%Y-%m')
            ORDER BY month ASC";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $trendResult = $stmt->get_result();

    while ($row = $trendResult->fetch_assoc()) {
        $response['data']['charts']['trend']['labels'][] = $row['month'];
        if ($currency === 'USD') {
            $response['data']['charts']['trend']['sales'][] = $row['sales'] / $rate;
        } else {
            $response['data']['charts']['trend']['sales'][] = $row['sales'];
        }
        $response['data']['charts']['trend']['quantity'][] = $row['quantity'];
    }

    // Fetch top customers data
    $sql = "SELECT 
            s.buyer_name,
            COALESCE(SUM(s.total), 0) as total_amount
            FROM gps_sales s
            LEFT JOIN gps_business_cycle_sales bcs ON s.id = bcs.sale_id
            WHERE $whereClause
            GROUP BY s.buyer_name
            ORDER BY total_amount DESC
            LIMIT 5";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $customersResult = $stmt->get_result();

    while ($row = $customersResult->fetch_assoc()) {
        $response['data']['charts']['customers']['labels'][] = $row['buyer_name'];
        if ($currency === 'USD') {
            $response['data']['charts']['customers']['values'][] = $row['total_amount'] / $rate;
        } else {
            $response['data']['charts']['customers']['values'][] = $row['total_amount'];
        }
    }

    // Fetch table data
    $sql = "SELECT 
            s.sale_date,
            COALESCE(bc.cycle_number, 'N/A') as cycle_number,
            s.buyer_name,
            s.quantity,
            s.unit_price,
            s.total,
            s.status
            FROM gps_sales s
            LEFT JOIN gps_business_cycle_sales bcs ON s.id = bcs.sale_id
            LEFT JOIN gps_business_cycles bc ON bcs.business_cycle_id = bc.id
            WHERE $whereClause
            ORDER BY s.sale_date DESC";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $tableResult = $stmt->get_result();

    $response['data']['table'] = array();
    while ($row = $tableResult->fetch_assoc()) {
        if ($currency === 'USD') {
            $row['unit_price'] = $row['unit_price'] / $rate;
            $row['total'] = $row['total'] / $rate;
        }
        $response['data']['table'][] = $row;
    }

    $response['success'] = true;

} catch (Exception $e) {
    $response['messages'][] = $e->getMessage();
    if (!isset($response['error'])) {
        $response['error'] = "Error generating sales report: " . $e->getMessage();
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