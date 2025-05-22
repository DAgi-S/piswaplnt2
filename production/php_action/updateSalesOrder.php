<?php
require_once 'core.php';
require_once 'db_connect.php';

// Set Content Type
header('Content-Type: application/json');

// Default response
$response = array(
    'success' => false,
    'messages' => array()
);

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['messages'][] = 'Invalid request method';
    echo json_encode($response);
    exit();
}

try {
    // Get and validate input
    $orderId = isset($_POST['status_order_id']) ? intval($_POST['status_order_id']) : 0;
    $newStatus = isset($_POST['order_status']) ? $_POST['order_status'] : '';
    $notes = isset($_POST['status_notes']) ? $_POST['status_notes'] : '';

    // Validate inputs
    if ($orderId <= 0) {
        throw new Exception('Invalid order ID');
    }
    if (empty($newStatus)) {
        throw new Exception('Status is required');
    }

    // Validate status value
    $validStatuses = array('pending', 'processing', 'completed', 'cancelled');
    if (!in_array(strtolower($newStatus), $validStatuses)) {
        throw new Exception('Invalid status value');
    }

    // Get current order status
    $orderSql = "SELECT order_status, payment_status FROM sales_orders WHERE id = ?";
    $orderStmt = $connect->prepare($orderSql);
    $orderStmt->bind_param('i', $orderId);
    $orderStmt->execute();
    $orderResult = $orderStmt->get_result();
    $order = $orderResult->fetch_assoc();

    if (!$order) {
        throw new Exception('Order not found');
    }

    // Prevent status update if order is cancelled
    if ($order['order_status'] === 'cancelled' && $newStatus !== 'cancelled') {
        throw new Exception('Cannot update status of cancelled order');
    }

    // Start transaction
    $connect->begin_transaction();

    // Update order status
    $updateSql = "UPDATE sales_orders SET order_status = ? WHERE id = ?";
    $updateStmt = $connect->prepare($updateSql);
    $updateStmt->bind_param('si', $newStatus, $orderId);

    if (!$updateStmt->execute()) {
        throw new Exception('Error updating order status: ' . $updateStmt->error);
    }

    // Record status change in history
    $historySql = "INSERT INTO sales_status_history (
        sales_order_id, status, notes, created_by, created_at
    ) VALUES (?, ?, ?, ?, NOW())";

    $historyStmt = $connect->prepare($historySql);
    $historyStmt->bind_param('issi', 
        $orderId,
        $newStatus,
        $notes,
        $_SESSION['userId']
    );

    if (!$historyStmt->execute()) {
        throw new Exception('Error recording status history: ' . $historyStmt->error);
    }

    // Commit transaction
    $connect->commit();

    $response['success'] = true;
    $response['messages'][] = 'Order status updated successfully';

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($connect) && $connect->ping()) {
        $connect->rollback();
    }
    $response['messages'][] = $e->getMessage();
}

echo json_encode($response); 