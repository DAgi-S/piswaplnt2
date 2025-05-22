<?php
require_once 'core.php';
require_once 'db_connect.php';

// Set Content Type
header('Content-Type: application/json; charset=utf-8');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Default response
$response = array(
    'success' => false,
    'messages' => array()
);

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data: ' . json_last_error_msg());
    }

    // Log the incoming data for debugging
    error_log("[Date Debug] Received data: " . print_r($input, true));

    // Validate and format order date
    if (empty($input['order_date'])) {
        error_log("[Date Debug] Order date is empty");
        throw new Exception('Order date is required');
    }

    error_log("[Date Debug] Raw order date: " . $input['order_date']);

    // Ensure MySQL strict mode is disabled for this connection
    $connect->query("SET SESSION sql_mode = ''");

    // Parse and validate the order date
    $orderDate = DateTime::createFromFormat('Y-m-d', $input['order_date']);
    if (!$orderDate) {
        error_log("[Date Debug] Failed to parse order date: " . print_r(DateTime::getLastErrors(), true));
        throw new Exception('Invalid order date format. Expected YYYY-MM-DD');
    }

    error_log("[Date Debug] Parsed order date: " . $orderDate->format('Y-m-d'));

    // Set time to start of day for accurate comparison
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    $orderDate->setTime(0, 0, 0);

    error_log("[Date Debug] Today: " . $today->format('Y-m-d'));
    error_log("[Date Debug] Order date for comparison: " . $orderDate->format('Y-m-d'));

    // Validate order date is not in the future
    if ($orderDate > $today) {
        error_log("[Date Debug] Order date is in the future");
        throw new Exception('Order date cannot be in the future');
    }

    // Format the date for MySQL - ensure it's a string
    $orderDateFormatted = $orderDate->format('Y-m-d');
    error_log("[Date Debug] Formatted order date for MySQL: " . $orderDateFormatted);

    // Generate order number based on the validated order date
    $orderNumberDate = $orderDate->format('Ymd');
    $orderNumberQuery = "SELECT MAX(CAST(SUBSTRING_INDEX(order_number, '-', -1) AS UNSIGNED)) as max_number 
                        FROM sales_orders 
                        WHERE order_number LIKE 'SO-$orderNumberDate-%'
                        FOR UPDATE";
    
    error_log("[Date Debug] Order number query: " . $orderNumberQuery);
    
    $result = $connect->query($orderNumberQuery);
    if (!$result) {
        throw new Exception('Error generating order number: ' . $connect->error);
    }
    
    $row = $result->fetch_assoc();
    $nextNumber = ($row['max_number'] ?? 0) + 1;
    $orderNumber = sprintf("SO-%s-%04d", $orderNumberDate, $nextNumber);

    error_log("[Date Debug] Generated order number: " . $orderNumber);

    // Verify order number uniqueness
    $checkDuplicate = $connect->query("SELECT id FROM sales_orders WHERE order_number = '$orderNumber' LIMIT 1");
    if ($checkDuplicate && $checkDuplicate->num_rows > 0) {
        throw new Exception('Failed to generate unique order number. Please try again.');
    }

    // Validate delivery date if provided
    $deliveryDate = null;
    if (!empty($input['delivery_date'])) {
        $deliveryDate = DateTime::createFromFormat('Y-m-d', $input['delivery_date']);
        if (!$deliveryDate) {
            throw new Exception('Invalid delivery date format. Expected YYYY-MM-DD');
        }

        $deliveryDate->setTime(0, 0, 0); // Set time to start of day for accurate comparison

        // Validate delivery date is not before order date
        if ($deliveryDate < $orderDate) {
            throw new Exception('Delivery date cannot be before order date');
        }

        $deliveryDateFormatted = $deliveryDate->format('Y-m-d');
    } else {
        $deliveryDateFormatted = null;
    }

    // Get and validate input data
    $warehouseId = isset($input['warehouse_id']) ? intval($input['warehouse_id']) : 0;
    $clientId = isset($input['client_id']) ? intval($input['client_id']) : 0;
    $notes = isset($input['notes']) ? $input['notes'] : '';
    $items = isset($input['items']) ? $input['items'] : array();
    
    // Validate required data
    if ($warehouseId <= 0) {
        throw new Exception('Please select a warehouse');
    }

    if ($clientId <= 0) {
        throw new Exception('Please select a client');
    }

    if (empty($items)) {
        throw new Exception('Please add at least one item to the order');
    }

    // Start transaction
    $connect->begin_transaction();

    // Calculate totals
    $subtotal = floatval($input['subtotal'] ?? 0);
    $taxAmount = floatval($input['tax_amount'] ?? 0);
    $discountAmount = floatval($input['discount_amount'] ?? 0);
    $withholdingAmount = floatval($input['withholding_amount'] ?? 0);
    $totalAmount = floatval($input['total_amount'] ?? 0);
    $discountPercent = floatval($input['discount_percent'] ?? 0);
    $applyWithholding = isset($input['apply_withholding']) ? (bool)$input['apply_withholding'] : false;

    // Get user ID from session
    $userId = isset($_SESSION['userId']) ? $_SESSION['userId'] : null;
    if (!$userId) {
        throw new Exception('User not authenticated');
    }

    // Insert sales order with direct date value
    $sql = "INSERT INTO sales_orders (
        order_number, client_id, warehouse_id, order_date, delivery_date,
        subtotal, tax_amount, withholding_amount, discount_amount, total_amount,
        paid_amount, balance, payment_status, order_status, notes, created_by,
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, 'unpaid', 'pending', ?, ?, NOW())";

    error_log("[Date Debug] SQL query: " . $sql);
    error_log("[Date Debug] Order date value before binding: " . $orderDateFormatted);

    // Double check the date format
    error_log("[Date Debug] Date validation check: " . (preg_match('/^\d{4}-\d{2}-\d{2}$/', $orderDateFormatted) ? 'valid' : 'invalid'));

    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception('Error preparing order statement: ' . $connect->error);
    }

    $balance = $totalAmount; // Initial balance is total amount

    // Log all parameters being bound
    error_log("[Date Debug] Parameters being bound:");
    error_log("order_number: " . $orderNumber);
    error_log("client_id: " . $clientId);
    error_log("warehouse_id: " . $warehouseId);
    error_log("order_date: " . $orderDateFormatted);
    error_log("delivery_date: " . ($deliveryDateFormatted ?? 'NULL'));

    // Try direct query first to verify date insertion
    $testQuery = "INSERT INTO sales_orders (
        order_number, client_id, warehouse_id, order_date, delivery_date,
        subtotal, tax_amount, withholding_amount, discount_amount, total_amount,
        paid_amount, balance, payment_status, order_status, notes, created_by,
        created_at
    ) VALUES (
        '$orderNumber', $clientId, $warehouseId, '$orderDateFormatted', NULL,
        $subtotal, $taxAmount, $withholdingAmount, $discountAmount, $totalAmount,
        0, $balance, 'unpaid', 'pending', '$notes', $userId, NOW()
    )";

    error_log("[Date Debug] Test query: " . $testQuery);
    
    if (!$connect->query($testQuery)) {
        error_log("[Date Debug] Direct query error: " . $connect->error);
        throw new Exception('Error creating order: ' . $connect->error);
    }

    $orderId = $connect->insert_id;
    error_log("[Date Debug] Order created successfully with ID: " . $orderId);

    // Insert order items
    $itemSql = "INSERT INTO sales_order_items (
        sales_order_id, product_id, quantity, unit_price,
        tax_rate, tax_amount, withholding_tax, withholding_amount,
        discount_percent, discount_amount, subtotal, total
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $itemStmt = $connect->prepare($itemSql);
    if (!$itemStmt) {
        throw new Exception('Error preparing item statement: ' . $connect->error);
    }

    foreach ($items as $item) {
        $productId = intval($item['product_id']);
        $quantity = floatval($item['quantity']);
        $unitPrice = floatval($item['unit_price']);
        
        // Calculate item level totals
        $itemSubtotal = $quantity * $unitPrice;
        $itemTaxRate = 15; // Fixed VAT rate
        $itemTaxAmount = $itemSubtotal * ($itemTaxRate / 100);
        $itemWithholdingRate = $applyWithholding ? 2 : 0;
        $itemWithholdingAmount = $applyWithholding ? ($itemSubtotal * 0.02) : 0;
        $itemDiscountAmount = ($discountPercent > 0) ? ($itemSubtotal * ($discountPercent / 100)) : 0;
        $itemTotal = $itemSubtotal + $itemTaxAmount - $itemWithholdingAmount - $itemDiscountAmount;

        $itemStmt->bind_param('iidddddddddd',
            $orderId,
            $productId,
            $quantity,
            $unitPrice,
            $itemTaxRate,
            $itemTaxAmount,
            $itemWithholdingRate,
            $itemWithholdingAmount,
            $discountPercent,
            $itemDiscountAmount,
            $itemSubtotal,
            $itemTotal
        );

        if (!$itemStmt->execute()) {
            throw new Exception('Error inserting order item: ' . $itemStmt->error);
        }

        // Update stock
        $updateStockSql = "UPDATE warehouse_stock 
                          SET quantity = quantity - ? 
                          WHERE warehouse_id = ? 
                          AND item_id = ? 
                          AND item_type = 'finished_good'";
        
        $stockStmt = $connect->prepare($updateStockSql);
        if (!$stockStmt) {
            throw new Exception('Error preparing stock update: ' . $connect->error);
        }

        $stockStmt->bind_param('dii',
            $quantity,
            $warehouseId,
            $productId
        );

        if (!$stockStmt->execute()) {
            throw new Exception('Error updating stock: ' . $stockStmt->error);
        }
    }

    // Create initial status history record
    $historySql = "INSERT INTO sales_status_history (
        sales_order_id, status, notes, created_by
    ) VALUES (?, 'pending', ?, ?)";
    
    $historyStmt = $connect->prepare($historySql);
    $historyNotes = 'Order created';
    $historyStmt->bind_param('isi',
        $orderId,
        $historyNotes,
        $userId
    );

    if (!$historyStmt->execute()) {
        throw new Exception('Error creating status history: ' . $historyStmt->error);
    }

    // Commit transaction
    $connect->commit();

    $response['success'] = true;
    $response['messages'][] = "Sales order #$orderNumber has been created successfully!";
    $response['order'] = array(
        'id' => $orderId,
        'order_number' => $orderNumber,
        'total_amount' => floatval($totalAmount)
    );

    error_log("[Date Debug] Success response: " . json_encode($response));

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($connect) && $connect->ping()) {
        $connect->rollback();
    }
    $response['messages'][] = $e->getMessage();
    error_log("[Date Debug] Error: " . $e->getMessage());
}

// Remove any whitespace or output before the JSON
if (ob_get_length()) ob_clean();

// Encode with proper options to handle special characters
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); 