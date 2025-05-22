<?php
require_once 'core.php';
require_once 'db_connect.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set proper headers
header('Content-Type: application/json');

// Start output buffering
ob_start();

// Default response
$response = array(
    'success' => false,
    'messages' => array(),
    'data' => null
);

try {
    // Log the incoming request
    error_log("Received request data: " . print_r($_POST, true));

    // Validate input
    if (!isset($_POST['order_number']) || empty($_POST['order_number'])) {
        throw new Exception('Order number is required');
    }

    $orderNumber = $_POST['order_number'];
    error_log("Processing order number: " . $orderNumber);

    // Get order details with client information
    $sql = "SELECT 
                so.*,
                c.company_name,
                c.tin_number,
                c.phone,
                c.email,
                c.address,
                COALESCE(SUM(sp.amount), 0) as total_paid_amount,
                (SELECT COALESCE(SUM(quantity * unit_price), 0) 
                 FROM sales_order_items 
                 WHERE sales_order_id = so.id) as total_subtotal,
                (SELECT COALESCE(SUM(quantity * unit_price * (tax_rate/100)), 0) 
                 FROM sales_order_items 
                 WHERE sales_order_id = so.id) as total_tax,
                (SELECT COALESCE(SUM(withholding_amount), 0)
                 FROM sales_order_items
                 WHERE sales_order_id = so.id) as total_withholding,
                (SELECT COALESCE(SUM(discount_amount), 0)
                 FROM sales_order_items
                 WHERE sales_order_id = so.id) as total_discount,
                CASE 
                    WHEN COALESCE(SUM(sp.amount), 0) = 0 THEN 'Unpaid'
                    WHEN COALESCE(SUM(sp.amount), 0) >= (
                        (SELECT COALESCE(SUM(quantity * unit_price), 0) 
                         FROM sales_order_items 
                         WHERE sales_order_id = so.id) +
                        (SELECT COALESCE(SUM(quantity * unit_price * (tax_rate/100)), 0) 
                         FROM sales_order_items 
                         WHERE sales_order_id = so.id) -
                        (SELECT COALESCE(SUM(withholding_amount), 0)
                         FROM sales_order_items
                         WHERE sales_order_id = so.id) -
                        (SELECT COALESCE(SUM(discount_amount), 0)
                         FROM sales_order_items
                         WHERE sales_order_id = so.id)
                    ) THEN 'Paid'
                    ELSE 'Partially Paid'
                END as calculated_payment_status
            FROM sales_orders so
            LEFT JOIN clients c ON so.client_id = c.id
            LEFT JOIN sales_payments sp ON so.id = sp.sales_order_id
            WHERE so.order_number = ?
            GROUP BY so.id, c.company_name, c.tin_number, c.phone, c.email, c.address";

    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error preparing order query: " . $connect->error);
    }

    $stmt->bind_param('s', $orderNumber);
    if (!$stmt->execute()) {
        throw new Exception("Error executing order query: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception("Order not found: " . $orderNumber);
    }

    $orderData = $result->fetch_assoc();
    $stmt->close();

    error_log("Found order data: " . print_r($orderData, true));

    // Fetch order items with proper calculations
    $itemsSql = "SELECT 
                    soi.*,
                    COALESCE(p.name, pp.name) as product_name,
                    COALESCE(p.product_code, pp.product_code) as product_code
                FROM sales_order_items soi
                LEFT JOIN products p ON soi.product_id = p.product_id
                LEFT JOIN production_products pp ON soi.product_id = pp.id
                WHERE soi.sales_order_id = ?
                ORDER BY soi.id ASC";

    $stmt = $connect->prepare($itemsSql);
    if (!$stmt) {
        throw new Exception("Error preparing items query: " . $connect->error);
    }

    $stmt->bind_param('i', $orderData['id']);
    if (!$stmt->execute()) {
        throw new Exception("Error executing items query: " . $stmt->error);
    }

    $itemsResult = $stmt->get_result();
    $items = array();
    
    while ($item = $itemsResult->fetch_assoc()) {
        $items[] = array(
            'product_name' => $item['product_name'] . ' (' . $item['product_code'] . ')',
            'quantity' => floatval($item['quantity']),
            'rate' => floatval($item['unit_price']),
            'tax_rate' => floatval($item['tax_rate']),
            'tax_amount' => floatval($item['quantity']) * floatval($item['unit_price']) * (floatval($item['tax_rate']) / 100),
            'withholding_amount' => floatval($item['withholding_amount']),
            'discount_amount' => floatval($item['discount_amount']),
            'subtotal' => floatval($item['quantity']) * floatval($item['unit_price']),
            'total' => (floatval($item['quantity']) * floatval($item['unit_price'])) * (1 + floatval($item['tax_rate']) / 100)
        );
    }
    $stmt->close();

    error_log("Found items: " . count($items));

    // Get payment history
    $paymentsSql = "SELECT 
                        sp.*,
                        COALESCE(a.account_owner, 'System') as processed_by
                    FROM sales_payments sp
                    LEFT JOIN accounts a ON sp.created_by = a.id
                    WHERE sp.sales_order_id = ?
                    ORDER BY sp.payment_date ASC";

    $stmt = $connect->prepare($paymentsSql);
    $stmt->bind_param('i', $orderData['id']);
    $stmt->execute();
    $paymentsResult = $stmt->get_result();
    
    $payments = array();
    while ($payment = $paymentsResult->fetch_assoc()) {
        $payments[] = array(
            'payment_date' => date('Y-m-d', strtotime($payment['payment_date'])),
            'amount' => number_format($payment['amount'], 2),
            'payment_method' => $payment['payment_method'],
            'reference' => $payment['reference_number'],
            'notes' => $payment['notes']
        );
    }
    $stmt->close();

    // Use the totals from the main query
    $subtotal = floatval($orderData['total_subtotal']);
    $totalTax = floatval($orderData['total_tax']);
    $withholdingAmount = floatval($orderData['total_withholding']);
    $discountAmount = floatval($orderData['total_discount']);
    
    // Calculate grand total correctly
    $grandTotal = $subtotal + $totalTax - $withholdingAmount - $discountAmount;

    // Format the response data with raw numbers
    $response['data'] = array(
        'invoice_number' => $orderData['order_number'],
        'client' => $orderData['company_name'],
        'client_tin' => $orderData['tin_number'],
        'client_phone' => $orderData['phone'],
        'client_email' => $orderData['email'],
        'client_address' => $orderData['address'],
        'sale_date' => date('Y-m-d', strtotime($orderData['order_date'])),
        'order_status' => $orderData['order_status'],
        'payment_status' => $orderData['calculated_payment_status'],
        'sub_total' => $subtotal,
        'vat_amount' => $totalTax,
        'withholding_amount' => $withholdingAmount,
        'discount_amount' => $discountAmount,
        'grand_total' => $grandTotal,
        'paid_amount' => floatval($orderData['total_paid_amount']),
        'items' => $items,
        'payments' => array_map(function($payment) {
            return array(
                'payment_date' => date('Y-m-d', strtotime($payment['payment_date'])),
                'amount' => floatval($payment['amount']),
                'payment_method' => $payment['payment_method'],
                'reference' => $payment['reference_number'],
                'notes' => $payment['notes']
            );
        }, $payments)
    );

    $response['success'] = true;

} catch (Exception $e) {
    $response['success'] = false;
    $response['messages'] = $e->getMessage();
    error_log("Error in fetchSalesOnReport.php: " . $e->getMessage());
}

// Clean output buffer
while (ob_get_level()) {
    ob_end_clean();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); 