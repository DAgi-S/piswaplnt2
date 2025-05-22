<?php
require_once 'core.php';
require_once 'db_connect.php';

// Set Content Type
header('Content-Type: application/json');

// Default response
$response = array(
    'success' => false,
    'messages' => array(),
    'order' => null
);

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $response['messages'][] = 'Invalid order ID';
    echo json_encode($response);
    exit();
}

try {
    $orderId = intval($_GET['id']);

    // Fetch order details with client and creator information
    $sql = "SELECT so.*, 
            c.company_name as client_name, 
            c.phone, c.email, c.address,
            COALESCE(a.account_owner, 'System') as created_by_name
            FROM sales_orders so
            LEFT JOIN clients c ON so.client_id = c.id
            LEFT JOIN accounts a ON so.created_by = a.id
            WHERE so.id = ?";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();

    if (!$order) {
        throw new Exception('Order not found');
    }

    // Fetch order items
    $itemsSql = "SELECT soi.*, 
                 pp.name as product_name, 
                 pp.product_code
                 FROM sales_order_items soi
                 LEFT JOIN products p ON soi.product_id = p.product_id
                 LEFT JOIN production_products pp ON p.product_code = pp.product_code
                 WHERE soi.sales_order_id = ?";

    $itemsStmt = $connect->prepare($itemsSql);
    $itemsStmt->bind_param('i', $orderId);
    $itemsStmt->execute();
    $items = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Fetch payments
    $paymentsSql = "SELECT sp.*,
                    COALESCE(a.account_owner, 'System') as created_by_name
                    FROM sales_payments sp
                    LEFT JOIN accounts a ON sp.created_by = a.id
                    WHERE sp.sales_order_id = ?
                    ORDER BY sp.payment_date DESC";

    $paymentsStmt = $connect->prepare($paymentsSql);
    $paymentsStmt->bind_param('i', $orderId);
    $paymentsStmt->execute();
    $payments = $paymentsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Format dates and numbers
    $order['order_date'] = date('Y-m-d', strtotime($order['order_date']));
    $order['delivery_date'] = $order['delivery_date'] ? date('Y-m-d', strtotime($order['delivery_date'])) : null;
    $order['created_at'] = date('Y-m-d H:i:s', strtotime($order['created_at']));
    
    $order['subtotal'] = number_format($order['subtotal'], 2);
    $order['tax_amount'] = floatval($order['tax_amount']);
    $order['discount_amount'] = floatval($order['discount_amount']);
    $order['total_amount'] = floatval($order['total_amount']);
    $order['paid_amount'] = floatval($order['paid_amount']);
    $order['balance'] = number_format($order['balance'], 2);

    // Format items
    foreach ($items as &$item) {
        $item['quantity'] = number_format($item['quantity'], 2);
        $item['unit_price'] = number_format($item['unit_price'], 2);
        $item['tax_rate'] = number_format($item['tax_rate'], 2);
        $item['tax_amount'] = floatval($item['tax_amount']);
        $item['discount_percent'] = number_format($item['discount_percent'], 2);
        $item['discount_amount'] = floatval($item['discount_amount']);
        $item['subtotal'] = number_format($item['subtotal'], 2);
        $item['total'] = number_format($item['total'], 2);
    }

    // Format payments
    foreach ($payments as &$payment) {
        $payment['payment_date'] = date('Y-m-d', strtotime($payment['payment_date']));
        $payment['amount'] = floatval($payment['amount']);
        $payment['created_at'] = date('Y-m-d H:i:s', strtotime($payment['created_at']));
    }

    // Build response
    $response['success'] = true;
    $response['order'] = array_merge($order, array(
        'items' => $items,
        'payments' => $payments
    ));

} catch (Exception $e) {
    $response['messages'][] = $e->getMessage();
}

echo json_encode($response); 