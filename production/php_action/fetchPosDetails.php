<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'core.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable error display in output

// Set header to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit();
}

// Check if sale ID is provided
if (!isset($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Sale ID is required'
    ]);
    exit();
}

try {
    $saleId = intval($_GET['id']);

    // Prepare the main query to fetch sale details
    $query = "
        SELECT 
            so.*,
            c.company_name as client_name,
            c.phone as client_phone,
            c.email as client_email,
            sp.payment_method
        FROM sales_orders so
        LEFT JOIN clients c ON so.client_id = c.id
        LEFT JOIN sales_payments sp ON so.id = sp.sales_order_id
        WHERE so.id = ?
        ORDER BY sp.created_at DESC
        LIMIT 1
    ";

    $stmt = $connect->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $connect->error);
    }

    $stmt->bind_param("i", $saleId);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception("Get result failed: " . $stmt->error);
    }
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Sale not found'
        ]);
        exit();
    }

    $saleData = $result->fetch_assoc();

    // Fetch sale items with product details
    $itemsQuery = "
        SELECT 
            soi.*,
            pp.name as product_name,
            pp.product_code,
            pp.selling_price as base_price,
            pp.id as product_id
        FROM sales_order_items soi
        LEFT JOIN production_products pp ON soi.product_id = pp.id
        WHERE soi.sales_order_id = ?
        ORDER BY soi.id ASC
    ";

    $stmt = $connect->prepare($itemsQuery);
    if (!$stmt) {
        throw new Exception("Prepare items query failed: " . $connect->error);
    }

    $stmt->bind_param("i", $saleId);
    if (!$stmt->execute()) {
        throw new Exception("Execute items query failed: " . $stmt->error);
    }

    $itemsResult = $stmt->get_result();
    if (!$itemsResult) {
        throw new Exception("Get items result failed: " . $stmt->error);
    }
    
    $items = [];
    while ($item = $itemsResult->fetch_assoc()) {
        // Debug log for each item
        error_log("Processing item: " . print_r($item, true));
        
        $items[] = [
            'id' => $item['id'],
            'product_id' => $item['product_id'],
            'product_code' => $item['product_code'] ?? 'N/A',
            'name' => $item['product_name'] ?? 'Unknown Item',
            'quantity' => floatval($item['quantity']),
            'price' => floatval($item['unit_price']),
            'tax_rate' => floatval($item['tax_rate']),
            'tax_amount' => floatval($item['tax_amount']),
            'subtotal' => floatval($item['quantity'] * $item['unit_price']),
            'total' => floatval($item['total'])
        ];
    }

    // Debug log
    error_log("Total items fetched: " . count($items));
    error_log("Items data: " . print_r($items, true));

    // Prepare the response data
    $responseData = [
        'id' => intval($saleData['id']),
        'order_number' => $saleData['order_number'],
        'order_date' => $saleData['created_at'],
        'client_name' => $saleData['client_name'] ?? 'N/A',
        'client_phone' => $saleData['client_phone'] ?? '',
        'client_email' => $saleData['client_email'] ?? '',
        'payment_method' => $saleData['payment_method'] ?? 'N/A',
        'subtotal' => floatval($saleData['subtotal']),
        'tax_amount' => floatval($saleData['tax_amount']),
        'withholding_amount' => floatval($saleData['withholding_amount'] ?? 0),
        'discount_amount' => floatval($saleData['discount_amount'] ?? 0),
        'total_amount' => floatval($saleData['total_amount']),
        'paid_amount' => floatval($saleData['paid_amount']),
        'items' => $items
    ];

    // Clean output buffer before sending response
    ob_clean();
    
    // Send JSON response
    echo json_encode([
        'success' => true,
        'data' => $responseData
    ]);

} catch (Exception $e) {
    // Clean output buffer before sending error response
    ob_clean();
    
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching sale details: ' . $e->getMessage()
    ]);
}
?> 