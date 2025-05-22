<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'messages' => ['Session expired. Please log in again.']
    ]);
    exit();
}

require_once 'core.php';

// Set headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$response = array(
    'success' => false,
    'messages' => [],
    'data' => null
);

try {
    // Check database connection
    if (!isset($connect) || $connect->connect_error) {
        throw new Exception("Database connection failed");
    }

    // Get order ID from POST data
    $orderId = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    if ($orderId <= 0) {
        throw new Exception("Invalid order ID");
    }

    // Fetch order details with business cycle information
    $sql = "SELECT o.*, bco.business_cycle_id 
            FROM gps_orders o 
            LEFT JOIN gps_business_cycle_orders bco ON o.id = bco.order_id 
            WHERE o.id = ?";
    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error preparing query: " . $connect->error);
    }
    
    $stmt->bind_param("i", $orderId);
    if (!$stmt->execute()) {
        throw new Exception("Error executing query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception("Error getting result set: " . $stmt->error);
    }

    $order = $result->fetch_assoc();
    if (!$order) {
        throw new Exception("Order not found");
    }

    // Calculate total price
    $total_price = $order['quantity'] * $order['unit_price'];

    $response['data'] = array(
        'order_number' => $order['order_number'],
        'order_date' => date('Y-m-d', strtotime($order['order_date'])),
        'quantity' => $order['quantity'],
        'unit_price' => $order['unit_price'],
        'total_price' => $order['total_price'],
        'has_credit' => floatval($order['credit_amount']) > 0,
        'credit_amount' => $order['credit_amount'],
        'created_at' => date('Y-m-d H:i:s', strtotime($order['created_at']))
    );

    $response['success'] = true;

} catch (Exception $e) {
    error_log("Error in fetchOrderDetails.php: " . $e->getMessage());
    $response['messages'][] = $e->getMessage();
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