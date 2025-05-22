<?php
require_once 'core.php';
require_once 'db_connect.php';

// Set proper headers
header('Content-Type: application/json');

// Default response
$response = array(
    'success' => false,
    'message' => '',
    'data' => null
);

try {
    // Validate sale ID
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('Invalid sale ID');
    }

    $saleId = intval($_GET['id']);

    // Fetch sale details with client name
    $saleSql = "SELECT 
        so.*,
        c.company_name as client_name
    FROM sales_orders so
    LEFT JOIN clients c ON so.client_id = c.id
    WHERE so.id = ?";

    $stmt = $connect->prepare($saleSql);
    if (!$stmt) {
        throw new Exception("Error preparing sale query: " . $connect->error);
    }

    $stmt->bind_param('i', $saleId);
    if (!$stmt->execute()) {
        throw new Exception("Error fetching sale: " . $stmt->error);
    }

    $saleResult = $stmt->get_result();
    if ($saleResult->num_rows === 0) {
        throw new Exception("Sale not found");
    }

    $saleData = $saleResult->fetch_assoc();
    $stmt->close();

    // Fetch sale items with product details
    $itemsSql = "SELECT 
        soi.*,
        p.name,
        p.product_code
    FROM sales_order_items soi
    LEFT JOIN production_products p ON soi.product_id = p.id
    WHERE soi.sales_order_id = ?";

    $stmt = $connect->prepare($itemsSql);
    if (!$stmt) {
        throw new Exception("Error preparing items query: " . $connect->error);
    }

    $stmt->bind_param('i', $saleId);
    if (!$stmt->execute()) {
        throw new Exception("Error fetching items: " . $stmt->error);
    }

    $itemsResult = $stmt->get_result();
    $items = array();
    while ($item = $itemsResult->fetch_assoc()) {
        $items[] = array(
            'id' => $item['product_id'],
            'name' => $item['name'],
            'product_code' => $item['product_code'],
            'quantity' => $item['quantity'],
            'price' => $item['unit_price'],
            'tax_rate' => $item['tax_rate'],
            'tax_amount' => $item['tax_amount'],
            'total' => $item['total']
        );
    }
    $stmt->close();

    // Prepare response data
    $response['data'] = array(
        'id' => $saleData['id'],
        'order_number' => $saleData['order_number'],
        'client_name' => $saleData['client_name'],
        'order_date' => $saleData['order_date'],
        'subtotal' => floatval($saleData['subtotal']),
        'tax_amount' => floatval($saleData['tax_amount']),
        'discount_amount' => floatval($saleData['discount_amount']),
        'withholding_amount' => floatval($saleData['withholding_amount']),
        'total_amount' => floatval($saleData['total_amount']),
        'paid_amount' => floatval($saleData['paid_amount']),
        'payment_method' => $saleData['payment_status'],
        'items' => $items
    );

    $response['success'] = true;

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log("Error in fetchSaleDetails.php: " . $e->getMessage());
}

echo json_encode($response); 