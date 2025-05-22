<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for session timeout or invalid session
if (!isset($_SESSION['userId'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode([
        'error' => 'Session expired or invalid. Please refresh the page and login again.',
        'redirect' => '../index.php'
    ]);
    exit();
}

// Set headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once 'core.php';

// Initialize response array
$response = array(
    'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
    'recordsTotal' => 0,
    'recordsFiltered' => 0,
    'data' => array(),
    'error' => null
);

try {
    // Check database connection
    if (!isset($connect) || $connect->connect_error) {
        throw new Exception("Database connection failed: " . ($connect->connect_error ?? "Connection not established"));
    }

    // Get total records count
    $sql = "SELECT COUNT(*) as count FROM gps_business_cycles";
    $result = $connect->query($sql);
    
    if (!$result) {
        throw new Exception("Error counting records: " . $connect->error);
    }
    
    $row = $result->fetch_assoc();
    $response['recordsTotal'] = $row['count'];
    $response['recordsFiltered'] = $row['count'];

    // Main query
    $sql = "SELECT bc.*,
            (SELECT COALESCE(SUM(o.total_price), 0)
             FROM gps_orders o 
             JOIN gps_business_cycle_orders bco ON o.id = bco.order_id 
             WHERE bco.business_cycle_id = bc.id) as total_purchase_etb,
            (SELECT COALESCE(SUM(s.total), 0)
             FROM gps_sales s 
             JOIN gps_business_cycle_sales bcs ON s.id = bcs.sale_id 
             WHERE bcs.business_cycle_id = bc.id) as total_sales_etb,
            (SELECT COALESCE(SUM(o.credit_amount), 0)
             FROM gps_orders o 
             JOIN gps_business_cycle_orders bco ON o.id = bco.order_id 
             WHERE bco.business_cycle_id = bc.id) as total_credit_amount,
            (SELECT COALESCE(SUM(amount_etb), 0)
             FROM gps_business_expenses 
             WHERE business_cycle_id = bc.id) as total_expenses_etb
            FROM gps_business_cycles bc 
            ORDER BY bc.created_at DESC";
    $result = $connect->query($sql);

    if (!$result) {
        throw new Exception("Error fetching records: " . $connect->error);
    }

    while ($row = $result->fetch_assoc()) {
        // Calculate net profit
        $net_profit = $row['total_sales_etb'] - $row['total_purchase_etb'] - $row['total_expenses_etb'];
        
        $response['data'][] = array(
            'id' => $row['id'],
            'cycle_number' => $row['cycle_number'],
            'start_date' => $row['start_date'],
            'end_date' => $row['end_date'],
            'status' => $row['status'],
            'total_purchase_etb' => $row['total_purchase_etb'],
            'total_sales_etb' => $row['total_sales_etb'],
            'total_expenses_etb' => $row['total_expenses_etb'],
            'total_credit_amount' => $row['total_credit_amount'],
            'net_profit_etb' => $net_profit
        );
    }

} catch (Exception $e) {
    error_log("Error in fetchBusinessCycles.php: " . $e->getMessage());
    $response['error'] = $e->getMessage();
    http_response_code(500);
} finally {
    if (isset($connect)) {
        $connect->close();
    }
}

echo json_encode($response);
exit; 