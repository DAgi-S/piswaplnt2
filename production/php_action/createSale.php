<?php
require_once 'core.php';
require_once 'db_connect.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['client_id']) || empty($input['items'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields'
    ]);
    exit;
}

try {
    // Start transaction
    $connect->begin_transaction();

    // Create sales order
    $sql = "INSERT INTO sales_orders (
                client_id,
                order_date,
                subtotal,
                tax_amount,
                discount_amount,
                total_amount,
                payment_status,
                order_status,
                created_by,
                created_at
            ) VALUES (?, NOW(), ?, ?, ?, ?, 'pending', 'new', ?, NOW())";

    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error preparing sales order query: " . $connect->error);
    }

    $stmt->bind_param(
        "iddddi",
        $input['client_id'],
        $input['subtotal'],
        $input['tax'],
        $input['discount'],
        $input['total'],
        $_SESSION['userId']
    );

    if (!$stmt->execute()) {
        throw new Exception("Error creating sales order: " . $stmt->error);
    }

    $orderId = $connect->insert_id;

    // Insert order items
    $itemSql = "INSERT INTO sales_order_items (
                    sales_order_id,
                    product_id,
                    quantity,
                    unit_price,
                    tax_rate,
                    tax_amount,
                    discount_percent,
                    discount_amount,
                    subtotal,
                    created_at
                ) VALUES (?, ?, ?, ?, 0.15, ?, 0, 0, ?, NOW())";

    $itemStmt = $connect->prepare($itemSql);
    if (!$itemStmt) {
        throw new Exception("Error preparing order items query: " . $connect->error);
    }

    // Update stock levels
    $updateStockSql = "UPDATE products 
                       SET current_stock = current_stock - ? 
                       WHERE product_id = ?";
    
    $updateStockStmt = $connect->prepare($updateStockSql);
    if (!$updateStockStmt) {
        throw new Exception("Error preparing stock update query: " . $connect->error);
    }

    // Process each item
    foreach ($input['items'] as $item) {
        // Insert order item
        $subtotal = $item['price'] * $item['quantity'];
        $taxAmount = $subtotal * 0.15; // 15% VAT

        $itemStmt->bind_param(
            "iiddddd",
            $orderId,
            $item['product_id'],
            $item['quantity'],
            $item['price'],
            $taxAmount,
            $subtotal
        );

        if (!$itemStmt->execute()) {
            throw new Exception("Error adding order item: " . $itemStmt->error);
        }

        // Update stock
        $updateStockStmt->bind_param("di", $item['quantity'], $item['product_id']);
        if (!$updateStockStmt->execute()) {
            throw new Exception("Error updating stock: " . $updateStockStmt->error);
        }
    }

    // Create payment record if payment method is provided
    if (isset($input['payment_method']) && $input['payment_method']) {
        $paymentSql = "INSERT INTO sales_payments (
                        sales_order_id,
                        payment_date,
                        amount,
                        payment_method,
                        created_by,
                        created_at
                    ) VALUES (?, NOW(), ?, ?, ?, NOW())";

        $paymentStmt = $connect->prepare($paymentSql);
        if (!$paymentStmt) {
            throw new Exception("Error preparing payment query: " . $connect->error);
        }

        $paymentStmt->bind_param(
            "idsi",
            $orderId,
            $input['total'],
            $input['payment_method'],
            $_SESSION['userId']
        );

        if (!$paymentStmt->execute()) {
            throw new Exception("Error creating payment record: " . $paymentStmt->error);
        }

        // Update order payment status
        $updateOrderSql = "UPDATE sales_orders 
                          SET payment_status = 'paid', 
                              paid_amount = ?,
                              balance = 0
                          WHERE id = ?";

        $updateOrderStmt = $connect->prepare($updateOrderSql);
        if (!$updateOrderStmt) {
            throw new Exception("Error preparing order update query: " . $connect->error);
        }

        $updateOrderStmt->bind_param("di", $input['total'], $orderId);
        if (!$updateOrderStmt->execute()) {
            throw new Exception("Error updating order status: " . $updateOrderStmt->error);
        }
    }

    // Create status history record
    $historySql = "INSERT INTO sales_status_history (
                    sales_order_id,
                    status,
                    notes,
                    created_by,
                    created_at
                ) VALUES (?, 'new', 'Order created via POS', ?, NOW())";

    $historyStmt = $connect->prepare($historySql);
    if (!$historyStmt) {
        throw new Exception("Error preparing history query: " . $connect->error);
    }

    $historyStmt->bind_param("ii", $orderId, $_SESSION['userId']);
    if (!$historyStmt->execute()) {
        throw new Exception("Error creating status history: " . $historyStmt->error);
    }

    // Commit transaction
    $connect->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Sale completed successfully',
        'sale_id' => $orderId
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $connect->rollback();

    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Close database connection
$connect->close(); 