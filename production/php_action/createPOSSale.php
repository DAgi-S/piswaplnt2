<?php
require_once 'core.php';
require_once 'db_connect.php';

// Set proper headers
header('Content-Type: application/json');

// Default response
$response = array(
    'success' => false,
    'messages' => array()
);

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data');
    }

    // Validate required fields
    $required_fields = array(
        'client_id' => 'Client',
        'items' => 'Items',
        'payment_method' => 'Payment Method',
        'subtotal' => 'Subtotal',
        'tax_amount' => 'Tax Amount',
        'total_amount' => 'Total Amount',
        'paid_amount' => 'Payment Amount'
    );

    foreach ($required_fields as $field => $label) {
        if (!isset($input[$field]) || (is_string($input[$field]) && trim($input[$field]) === '') || 
            (is_array($input[$field]) && empty($input[$field]))) {
            throw new Exception($label . ' is required');
        }
    }

    // Validate amounts
    if (!is_numeric($input['total_amount']) || $input['total_amount'] <= 0) {
        throw new Exception('Invalid total amount');
    }
    if (!is_numeric($input['paid_amount']) || $input['paid_amount'] <= 0) {
        throw new Exception('Invalid payment amount');
    }
    if ($input['paid_amount'] < $input['total_amount']) {
        throw new Exception('Payment amount cannot be less than total amount for POS sales');
    }

    // Start transaction
    $connect->begin_transaction();

    try {
        // Lock all required tables
        $connect->query("LOCK TABLES 
            sales_orders WRITE, 
            sales_order_items WRITE, 
            production_products WRITE, 
            sales_payments WRITE"
        );
        
        // Generate order number
        $date = date('Ymd');
        $orderNumberQuery = "SELECT MAX(CAST(SUBSTRING_INDEX(order_number, '-', -1) AS UNSIGNED)) as max_num 
                           FROM sales_orders 
                           WHERE order_number LIKE 'POS-{$date}-%'
                           FOR UPDATE";
        
        $orderNumberResult = $connect->query($orderNumberQuery);
        if (!$orderNumberResult) {
            throw new Exception("Error generating order number: " . $connect->error);
        }
        
        $maxNum = $orderNumberResult->fetch_assoc()['max_num'];
        $nextNum = ($maxNum > 0) ? $maxNum + 1 : 1;
        $orderNumber = "POS-{$date}-" . sprintf('%04d', $nextNum);

        // Verify the order number is unique
        $checkDuplicate = $connect->query("SELECT id FROM sales_orders WHERE order_number = '{$orderNumber}' LIMIT 1");
        if ($checkDuplicate && $checkDuplicate->num_rows > 0) {
            // If duplicate found, increment until we find a unique number
            do {
                $nextNum++;
                $orderNumber = "POS-{$date}-" . sprintf('%04d', $nextNum);
                $checkDuplicate = $connect->query("SELECT id FROM sales_orders WHERE order_number = '{$orderNumber}' LIMIT 1");
            } while ($checkDuplicate && $checkDuplicate->num_rows > 0);
        }

        // Insert sales order
        $orderSql = "INSERT INTO sales_orders (
            order_number,
            client_id,
            warehouse_id,
            order_date,
            subtotal,
            tax_amount,
            discount_amount,
            withholding_amount,
            total_amount,
            paid_amount,
            payment_status,
            order_status,
            created_by
        ) VALUES (?, ?, ?, CURDATE(), ?, ?, ?, ?, ?, ?, 'unpaid', 'completed', ?)";

        $stmt = $connect->prepare($orderSql);
        if (!$stmt) {
            throw new Exception("Error preparing order query: " . $connect->error);
        }

        // Debug log
        error_log("Order SQL: " . $orderSql);
        error_log("Client ID: " . $input['client_id']);
        error_log("Warehouse ID: 1");
        error_log("Subtotal: " . $input['subtotal']);
        error_log("Tax Amount: " . $input['tax_amount']);
        error_log("Discount Amount: " . ($input['discount_amount'] ?? 0));
        error_log("Withholding Amount: " . ($input['withholding_amount'] ?? 0));
        error_log("Total Amount: " . $input['total_amount']);
        error_log("Paid Amount: " . $input['paid_amount']);
        error_log("User ID: " . ($_SESSION['userId'] ?? 0));

        $userId = isset($_SESSION['userId']) ? $_SESSION['userId'] : 0;
        $discountAmount = isset($input['discount_amount']) ? floatval($input['discount_amount']) : 0;
        $withholdingAmount = isset($input['withholding_amount']) ? floatval($input['withholding_amount']) : 0;
        $warehouseId = 1; // Default warehouse ID

        // Convert all numeric values to proper format
        $clientId = intval($input['client_id']);
        $subtotal = floatval($input['subtotal']);
        $taxAmount = floatval($input['tax_amount']);
        $totalAmount = floatval($input['total_amount']);
        $paidAmount = floatval($input['paid_amount']);

        $stmt->bind_param('siiddddddi', 
            $orderNumber,
            $clientId,
            $warehouseId,
            $subtotal,
            $taxAmount,
            $discountAmount,
            $withholdingAmount,
            $totalAmount,
            $paidAmount,
            $userId
        );

        if (!$stmt->execute()) {
            throw new Exception("Error creating order: " . $stmt->error);
        }

        $orderId = $stmt->insert_id;
        $stmt->close();

        // Insert order items
        $itemSql = "INSERT INTO sales_order_items (
            sales_order_id,
            product_id,
            quantity,
            unit_price,
            tax_rate,
            tax_amount,
            total
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";

        $itemStmt = $connect->prepare($itemSql);
        if (!$itemStmt) {
            throw new Exception("Error preparing items query: " . $connect->error);
        }

        foreach ($input['items'] as $item) {
            // Convert all values to proper format
            $productId = intval($item['id']);
            $quantity = floatval($item['quantity']);
            $price = floatval($item['price']);
            $taxRate = floatval($item['tax_rate']);
            $taxAmount = floatval($item['tax_amount']);
            $total = $quantity * $price;

            // Debug log
            error_log("Item details - Product ID: $productId, Quantity: $quantity, Price: $price, Tax Rate: $taxRate, Tax Amount: $taxAmount, Total: $total");

            $itemStmt->bind_param('iiddddd',
                $orderId,
                $productId,
                $quantity,
                $price,
                $taxRate,
                $taxAmount,
                $total
            );

            if (!$itemStmt->execute()) {
                throw new Exception("Error adding item: " . $itemStmt->error);
            }

            // Update stock
            $updateStockSql = "UPDATE production_products 
                              SET current_stock = current_stock - ? 
                              WHERE id = ?";
            $stockStmt = $connect->prepare($updateStockSql);
            if (!$stockStmt) {
                throw new Exception("Error preparing stock update: " . $connect->error);
            }

            $stockStmt->bind_param('di', $quantity, $productId);
            if (!$stockStmt->execute()) {
                throw new Exception("Error updating stock: " . $stockStmt->error);
            }
            $stockStmt->close();
        }

        $itemStmt->close();

        // Create payment record
        $paymentSql = "INSERT INTO sales_payments (
            sales_order_id,
            payment_date,
            amount,
            payment_method,
            created_by
        ) VALUES (?, CURDATE(), ?, ?, ?)";

        $paymentStmt = $connect->prepare($paymentSql);
        if (!$paymentStmt) {
            throw new Exception("Error preparing payment query: " . $connect->error);
        }

        $paymentStmt->bind_param('idsi',
            $orderId,
            $input['paid_amount'],
            $input['payment_method'],
            $userId
        );

        if (!$paymentStmt->execute()) {
            throw new Exception("Error recording payment: " . $paymentStmt->error);
        }

        $paymentStmt->close();

        // Commit transaction
        $connect->commit();

        $response['success'] = true;
        $response['messages'][] = 'Sale completed successfully';
        $response['sale_id'] = $orderId;
        $response['order_number'] = $orderNumber;

    } catch (Exception $e) {
        $connect->query("UNLOCK TABLES");
        $connect->rollback();
        throw $e;
    }

    // Unlock tables after successful transaction
    $connect->query("UNLOCK TABLES");

} catch (Exception $e) {
    $response['success'] = false;
    $response['messages'][] = $e->getMessage();
    error_log("Error in createPOSSale.php: " . $e->getMessage());
}

echo json_encode($response); 