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
    'success' => false,
    'messages' => []
);

try {
    // Check database connection
    if (!isset($connect) || $connect->connect_error) {
        throw new Exception("Database connection failed: " . ($connect->connect_error ?? "Connection not established"));
    }

    // Get POST data
    $cycleId = isset($_POST['cycle_id']) ? intval($_POST['cycle_id']) : 0;
    $orderId = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;

    // Validate inputs
    if ($cycleId <= 0 || $orderId <= 0) {
        throw new Exception("Invalid cycle ID or order ID");
    }

    // Check if cycle exists and is active
    $sql = "SELECT status FROM gps_business_cycles WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $cycleId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result || $result->num_rows === 0) {
        throw new Exception("Business cycle not found");
    }
    
    $cycle = $result->fetch_assoc();
    if ($cycle['status'] !== 'active') {
        throw new Exception("Cannot add order to a non-active business cycle");
    }

    // Check if order exists and is not already in a cycle
    $sql = "SELECT o.* FROM gps_orders o 
            LEFT JOIN gps_business_cycle_orders bco ON o.id = bco.order_id 
            WHERE o.id = ? AND bco.id IS NULL";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result || $result->num_rows === 0) {
        throw new Exception("Order not found or already assigned to a cycle");
    }

    // Begin transaction
    $connect->begin_transaction();

    try {
        // Add order to cycle
        $sql = "INSERT INTO gps_business_cycle_orders (business_cycle_id, order_id, created_at) VALUES (?, ?, NOW())";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("ii", $cycleId, $orderId);
        
        if (!$stmt->execute()) {
            throw new Exception("Error adding order to cycle: " . $stmt->error);
        }

        // Update cycle's total purchase amount
        $sql = "UPDATE gps_business_cycles bc 
                SET bc.total_purchase_etb = (
                    SELECT COALESCE(SUM(
                        CASE 
                            WHEN o.currency = 'ETB' THEN o.ordered_amount 
                            ELSE o.ordered_amount * o.rate
                        END
                    ), 0)
                    FROM gps_business_cycle_orders bco
                    JOIN gps_orders o ON bco.order_id = o.id
                    WHERE bco.business_cycle_id = ?
                )
                WHERE bc.id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("ii", $cycleId, $cycleId);
        
        if (!$stmt->execute()) {
            throw new Exception("Error updating cycle total: " . $stmt->error);
        }

        $connect->commit();
        $response['success'] = true;
        $response['messages'][] = "Order successfully added to cycle";

    } catch (Exception $e) {
        $connect->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error in addOrderToCycle.php: " . $e->getMessage());
    $response['messages'][] = $e->getMessage();
} finally {
    if (isset($connect)) {
        $connect->close();
    }
}

echo json_encode($response);
exit; 