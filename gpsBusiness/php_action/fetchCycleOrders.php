<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for session timeout or invalid session
if (!isset($_SESSION['userId'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode([
        'success' => false,
        'messages' => ['Session expired or invalid. Please refresh the page and login again.'],
        'redirect' => '../index.php'
    ]);
    exit();
}

// Set headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once 'core.php';

$response = array(
    'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 1,
    'recordsTotal' => 0,
    'recordsFiltered' => 0,
    'data' => []
);

try {
    // Check database connection
    if (!isset($connect) || $connect->connect_error) {
        throw new Exception("Database connection failed");
    }

    // Get cycle ID from POST data
    $cycleId = isset($_POST['cycle_id']) ? intval($_POST['cycle_id']) : 0;
    if ($cycleId <= 0) {
        throw new Exception("Invalid cycle ID");
    }

    // Get total records count
    $sql = "SELECT COUNT(*) as count 
            FROM gps_business_cycle_orders bco 
            WHERE bco.business_cycle_id = ?";
    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error preparing count query: " . $connect->error);
    }
    
    $stmt->bind_param("i", $cycleId);
    if (!$stmt->execute()) {
        throw new Exception("Error executing count query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $response['recordsTotal'] = $response['recordsFiltered'] = $row['count'];

    // Fetch orders for this cycle
    $sql = "SELECT o.id, o.order_number, o.order_date, o.total_price as amount, 
            'ETB' as currency, o.credit_amount, 
            CASE 
                WHEN o.credit_amount > 0 THEN 'Credit'
                ELSE 'Completed'
            END as status
            FROM gps_orders o 
            JOIN gps_business_cycle_orders bco ON o.id = bco.order_id 
            WHERE bco.business_cycle_id = ?
            ORDER BY o.order_date DESC";
    
    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error preparing query: " . $connect->error);
    }
    
    $stmt->bind_param("i", $cycleId);
    if (!$stmt->execute()) {
        throw new Exception("Error executing query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $data = array();

    while ($row = $result->fetch_assoc()) {
        $data[] = array(
            'id' => $row['id'],
            'order_number' => $row['order_number'],
            'order_date' => date('Y-m-d', strtotime($row['order_date'])),
            'amount' => number_format($row['amount'], 2),
            'currency' => $row['currency'],
            'credit_amount' => number_format($row['credit_amount'], 2),
            'status' => $row['status']
        );
    }

    $response['data'] = $data;

} catch (Exception $e) {
    error_log("Error in fetchCycleOrders.php: " . $e->getMessage());
    $response['error'] = $e->getMessage();
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($connect)) {
        $connect->close();
    }
}

echo json_encode($response);
exit; 