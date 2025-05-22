<?php
require_once 'core.php';
require_once 'db_connect.php';

// Set header type to JSON
header('Content-Type: application/json');

$response = array();

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['success'] = false;
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit();
}

// Check if required parameters are present
if (!isset($_POST['order_id'])) {
    $response['success'] = false;
    $response['message'] = 'Missing required parameters';
    echo json_encode($response);
    exit();
}

$orderId = intval($_POST['order_id']);

try {
    // Start transaction
    $connect->begin_transaction();

    // Get order details first
    $orderQuery = "SELECT total_amount FROM sales_orders WHERE id = ?";
    $stmt = $connect->prepare($orderQuery);
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Order not found');
    }
    
    $orderData = $result->fetch_assoc();
    $totalAmount = $orderData['total_amount'];

    // Update order payment status and paid amount
    $updateQuery = "UPDATE sales_orders 
                   SET payment_status = 'paid',
                       paid_amount = ?,
                       balance = 0,
                       updated_at = CURRENT_TIMESTAMP 
                   WHERE id = ? AND payment_status != 'paid'";
    
    $stmt = $connect->prepare($updateQuery);
    $stmt->bind_param('di', $totalAmount, $orderId);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception('Order is already marked as paid or not found');
    }

    // Add a payment record for the remaining balance
    $insertPaymentQuery = "INSERT INTO sales_payments (
        sales_order_id,
        payment_date,
        amount,
        payment_method,
        reference_number,
        notes,
        created_by
    ) VALUES (?, CURRENT_TIMESTAMP, ?, 'System', 'AUTO-PAID', 'Automatically marked as paid', ?)";

    $stmt = $connect->prepare($insertPaymentQuery);
    $stmt->bind_param('idi', $orderId, $totalAmount, $_SESSION['userId']);
    $stmt->execute();

    // Commit transaction
    $connect->commit();

    $response['success'] = true;
    $response['message'] = 'Order payment status successfully updated to Paid';

} catch (Exception $e) {
    // Rollback transaction on error
    $connect->rollback();
    
    $response['success'] = false;
    $response['message'] = 'Error: ' . $e->getMessage();
}

// Close statement if it exists
if (isset($stmt)) {
    $stmt->close();
}

echo json_encode($response); 