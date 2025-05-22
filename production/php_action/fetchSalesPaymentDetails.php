<?php
require_once '../includes/db_connect.php';

// Initialize response array
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

    // Build the query to get payment details
    $query = "SELECT 
        sp.id as payment_id,
        sp.payment_date,
        sp.amount,
        sp.payment_method,
        sp.status as payment_status,
        sp.reference_number,
        sp.notes,
        sp.payment_proof,
        sp.created_at,
        sp.sales_order_id,
        so.order_number,
        so.subtotal as order_subtotal,
        so.tax_amount as order_tax_amount,
        so.discount_amount as order_discount_amount,
        so.withholding_amount as order_withholding_amount,
        so.total_amount as order_total_amount,
        so.paid_amount as order_paid_amount,
        so.balance as order_balance,
        so.payment_status as order_payment_status,
        so.order_status,
        c.company_name as client_name,
        c.tin_number as client_tin,
        c.phone as client_phone,
        c.email as client_email,
        c.address as client_address,
        a.account_platform as account_name,
        a.account_owner,
        u.full_name as created_by_name,
        u.email as created_by_email
    FROM sales_payments sp
    LEFT JOIN sales_orders so ON sp.sales_order_id = so.id
    LEFT JOIN clients c ON so.client_id = c.id
    LEFT JOIN accounts a ON sp.account_id = a.id
    LEFT JOIN users u ON sp.created_by = u.user_id
    WHERE sp.id = ?";

    // Execute query
    $stmt = $connect->prepare($query);
    $stmt->execute([$paymentId]);
    
    // Check if payment exists
    if ($stmt->rowCount() === 0) {
        throw new Exception('Payment not found');
    }

    // Get payment details
    $paymentDetails = $stmt->fetch(PDO::FETCH_ASSOC);

    // Format dates
    $paymentDetails['payment_date'] = date('Y-m-d H:i:s', strtotime($paymentDetails['payment_date']));
    $paymentDetails['created_at'] = date('Y-m-d H:i:s', strtotime($paymentDetails['created_at']));

    // Handle payment proof path
    if (!empty($paymentDetails['payment_proof'])) {
        // Remove any 'assets/payment_proofs/' prefix if it exists
        $fileName = basename($paymentDetails['payment_proof']);
        if (strpos($fileName, 'payment_') !== 0) {
            $fileName = 'payment_' . $fileName;
        }
        $paymentDetails['payment_proof'] = '../uploads/payment_proofs/' . $fileName;
    }

    // Format amounts
    $paymentDetails['amount'] = number_format($paymentDetails['amount'], 2, '.', '');
    $paymentDetails['order_subtotal'] = number_format($paymentDetails['order_subtotal'], 2, '.', '');
    $paymentDetails['order_tax_amount'] = number_format($paymentDetails['order_tax_amount'], 2, '.', '');
    $paymentDetails['order_discount_amount'] = number_format($paymentDetails['order_discount_amount'], 2, '.', '');
    $paymentDetails['order_withholding_amount'] = number_format($paymentDetails['order_withholding_amount'], 2, '.', '');
    $paymentDetails['order_total_amount'] = number_format($paymentDetails['order_total_amount'], 2, '.', '');
    $paymentDetails['order_paid_amount'] = number_format($paymentDetails['order_paid_amount'], 2, '.', '');
    $paymentDetails['order_balance'] = number_format($paymentDetails['order_balance'], 2, '.', '');

    // Get other payments for this order
    $otherPaymentsQuery = "SELECT 
        id as payment_id,
        payment_date,
        amount,
        payment_method,
        status,
        reference_number,
        notes,
        created_at
    FROM sales_payments 
    WHERE sales_order_id = ? AND id != ?
    ORDER BY payment_date DESC";

    $stmt = $connect->prepare($otherPaymentsQuery);
    $stmt->execute([$paymentDetails['sales_order_id'], $paymentId]);
    $otherPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format other payments
    foreach ($otherPayments as &$payment) {
        $payment['payment_date'] = date('Y-m-d H:i:s', strtotime($payment['payment_date']));
        $payment['created_at'] = date('Y-m-d H:i:s', strtotime($payment['created_at']));
        $payment['amount'] = number_format($payment['amount'], 2, '.', '');
    }

    // Add other payments to response
    $paymentDetails['other_payments'] = $otherPayments;

    // Set success response
    $response['success'] = true;
    $response['data'] = $paymentDetails;

} catch (Exception $e) {
    $response['messages'][] = $e->getMessage();
}

// Send response
header('Content-Type: application/json');
echo json_encode($response); 