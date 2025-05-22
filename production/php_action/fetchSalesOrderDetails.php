<?php
require_once 'core.php';
require_once 'db_connect.php';

// Set Content Type
header('Content-Type: application/json');

// Default response
$response = array(
    'success' => false,
    'messages' => array(),
    'data' => null
);

try {
    // Validate input
    if (!isset($_POST['order_id']) || empty($_POST['order_id'])) {
        throw new Exception('Order ID is required');
    }

    $orderId = intval($_POST['order_id']);

    // Get order details
    $sql = "SELECT 
                so.*,
                c.company_name as client_name,
                COALESCE(u.username, 'System') as created_by_name,
                w.name as warehouse_name,
                COALESCE(so.withholding_amount, 0) as withholding_amount,
                COALESCE(so.grand_total, so.total_amount - so.withholding_amount) as grand_total
            FROM sales_orders so
            LEFT JOIN clients c ON so.client_id = c.id
            LEFT JOIN users u ON so.created_by = u.user_id
            LEFT JOIN warehouses w ON so.warehouse_id = w.id
            WHERE so.id = ?";

    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error preparing order query: " . $connect->error);
    }

    $stmt->bind_param('i', $orderId);
    if (!$stmt->execute()) {
        throw new Exception("Error fetching order: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $order = $result->fetch_assoc();

    if (!$order) {
        throw new Exception('Order not found');
    }

    // Format dates and numbers
    $order['order_date'] = date('Y-m-d', strtotime($order['order_date']));
    $order['delivery_date'] = $order['delivery_date'] ? date('Y-m-d', strtotime($order['delivery_date'])) : null;
    $order['created_at'] = date('Y-m-d H:i:s', strtotime($order['created_at']));
    $order['subtotal'] = floatval($order['subtotal']);
    $order['tax_amount'] = floatval($order['tax_amount']);
    $order['discount_amount'] = floatval($order['discount_amount']);
    $order['total_amount'] = floatval($order['total_amount']);
    $order['withholding_amount'] = floatval($order['withholding_amount']);
    $order['grand_total'] = floatval($order['grand_total']);
    $order['paid_amount'] = floatval($order['paid_amount']);
    $order['balance'] = floatval($order['balance']);

    // Get order items
    $itemsSql = "SELECT 
                    soi.*,
                    pp.name as product_name,
                    pp.unit
                FROM sales_order_items soi
                LEFT JOIN production_products pp ON soi.product_id = pp.id
                WHERE soi.sales_order_id = ?";

    $itemsStmt = $connect->prepare($itemsSql);
    if (!$itemsStmt) {
        throw new Exception("Error preparing items query: " . $connect->error);
    }

    $itemsStmt->bind_param('i', $orderId);
    if (!$itemsStmt->execute()) {
        throw new Exception("Error fetching items: " . $itemsStmt->error);
    }

    $itemsResult = $itemsStmt->get_result();
    $items = array();
    while ($item = $itemsResult->fetch_assoc()) {
        // Format numbers
        $item['quantity'] = floatval($item['quantity']);
        $item['unit_price'] = floatval($item['unit_price']);
        $item['tax_rate'] = floatval($item['tax_rate']);
        $item['tax_amount'] = floatval($item['tax_amount']);
        $item['discount_percent'] = floatval($item['discount_percent']);
        $item['discount_amount'] = floatval($item['discount_amount']);
        $item['subtotal'] = floatval($item['subtotal']);
        $item['total'] = floatval($item['total']);
        $items[] = $item;
    }
    $order['items'] = $items;

    // Get payment history
    $paymentsSql = "SELECT *
                    FROM sales_payments
                    WHERE sales_order_id = ?
                    ORDER BY payment_date DESC";

    $paymentsStmt = $connect->prepare($paymentsSql);
    if (!$paymentsStmt) {
        throw new Exception("Error preparing payments query: " . $connect->error);
    }

    $paymentsStmt->bind_param('i', $orderId);
    if (!$paymentsStmt->execute()) {
        throw new Exception("Error fetching payments: " . $paymentsStmt->error);
    }

    $paymentsResult = $paymentsStmt->get_result();
    $payments = array();
    while ($payment = $paymentsResult->fetch_assoc()) {
        // Format date and amount
        $payment['payment_date'] = date('Y-m-d', strtotime($payment['payment_date']));
        $payment['amount'] = floatval($payment['amount']);
        $payments[] = $payment;
    }
    $order['payments'] = $payments;

    // Set success response
    $response['success'] = true;
    $response['data'] = $order;

} catch (Exception $e) {
    $response['messages'][] = $e->getMessage();
    error_log("Error in fetchSalesOrderDetails.php: " . $e->getMessage());
}

echo json_encode($response); 