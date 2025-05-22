<?php
// Disable error reporting for production
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering
ob_start();

require_once 'core.php';
require_once 'db_connect.php';

// Clear any previous output
ob_clean();

// Set proper headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

$response = array(
    'success' => false,
    'messages' => array()
);

try {
    // Check if payment_id is provided
    if (!isset($_POST['payment_id']) || empty($_POST['payment_id'])) {
        throw new Exception('Payment ID is required');
    }

    $paymentId = intval($_POST['payment_id']);
    $userId = isset($_SESSION['userId']) ? $_SESSION['userId'] : 0;

    if (!$userId) {
        throw new Exception('User session expired');
    }

    // Start transaction
    mysqli_autocommit($connect, FALSE);

    // Get payment details first
    $paymentQuery = "SELECT sp.*, so.total_amount, so.paid_amount, so.balance 
                    FROM sales_payments sp
                    JOIN sales_orders so ON sp.sales_order_id = so.id
                    WHERE sp.id = ? AND sp.status = 'pending'
                    LIMIT 1";
    
    $stmt = $connect->prepare($paymentQuery);
    if (!$stmt) {
        throw new Exception("Failed to prepare payment query: " . $connect->error);
    }

    $stmt->bind_param('i', $paymentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Payment not found or already confirmed');
    }

    $payment = $result->fetch_assoc();
    $orderId = $payment['sales_order_id'];

    // Update payment status
    $updatePaymentSql = "UPDATE sales_payments 
                        SET status = 'confirmed',
                            updated_by = ?,
                            updated_at = NOW() 
                        WHERE id = ?";
    
    $stmt = $connect->prepare($updatePaymentSql);
    if (!$stmt) {
        throw new Exception("Failed to prepare payment update: " . $connect->error);
    }

    $stmt->bind_param('ii', $userId, $paymentId);
    if (!$stmt->execute()) {
        throw new Exception("Failed to update payment: " . $stmt->error);
    }

    // Update order payment status and balance
    $newPaidAmount = floatval($payment['paid_amount']) + floatval($payment['amount']);
    $newBalance = floatval($payment['total_amount']) - $newPaidAmount;
    $newPaymentStatus = $newBalance <= 0 ? 'paid' : ($newPaidAmount > 0 ? 'partial' : 'unpaid');

    $updateOrderSql = "UPDATE sales_orders 
                      SET payment_status = ?,
                          paid_amount = ?,
                          balance = ?,
                          updated_by = ?,
                          updated_at = NOW() 
                      WHERE id = ?";
    
    $stmt = $connect->prepare($updateOrderSql);
    if (!$stmt) {
        throw new Exception("Failed to prepare order update: " . $connect->error);
    }

    $stmt->bind_param('sddii', 
        $newPaymentStatus,
        $newPaidAmount,
        $newBalance,
        $userId,
        $orderId
    );
    if (!$stmt->execute()) {
        throw new Exception("Failed to update order: " . $stmt->error);
    }

    // Add to changelog
    $logSql = "INSERT INTO changelog (user_id, action, action_data, created_at) VALUES (?, ?, ?, NOW())";
    $logData = json_encode([
        'payment_id' => $paymentId,
        'order_id' => $orderId,
        'amount' => $payment['amount'],
        'new_status' => 'confirmed'
    ]);
    $logAction = "Confirmed payment";
    
    $stmt = $connect->prepare($logSql);
    if (!$stmt) {
        throw new Exception("Failed to prepare changelog entry: " . $connect->error);
    }

    $stmt->bind_param('iss', $userId, $logAction, $logData);
    if (!$stmt->execute()) {
        throw new Exception("Failed to log action: " . $stmt->error);
    }

    // Commit transaction
    if (!mysqli_commit($connect)) {
        throw new Exception("Failed to commit transaction");
    }

    $response['success'] = true;
    $response['messages'][] = 'Payment confirmed successfully';
    $response['payment_status'] = $newPaymentStatus;
    $response['new_balance'] = $newBalance;

} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($connect);
    $response['success'] = false;
    $response['messages'][] = $e->getMessage();
} finally {
    // Re-enable autocommit
    mysqli_autocommit($connect, TRUE);
    
    // Close the database connection
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($connect)) {
        $connect->close();
    }
}

// Clear any buffered output
while (ob_get_level()) {
    ob_end_clean();
}

// Send JSON response
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit; 