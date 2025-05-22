<?php
// Disable error reporting for the response
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering
ob_start();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'core.php';
require_once 'db_connect.php';

// Clear any previous output and set proper headers
while (ob_get_level()) {
    ob_end_clean();
}

// Set proper headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');

// Get JSON input and decode with error checking
$jsonInput = file_get_contents('php://input');
if (!$jsonInput) {
    echo json_encode(['success' => false, 'message' => 'No input data received']);
    exit;
}

$input = json_decode($jsonInput, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input: ' . json_last_error_msg()]);
    exit;
}

// Start transaction
$connect->begin_transaction();

try {
    // Check if user is logged in
    if (!isset($_SESSION['userId'])) {
        throw new Exception('User not logged in');
    }
    $userId = (int)$_SESSION['userId'];

    // Validate input
    if (!isset($_SESSION['userId'])) {
        throw new Exception('User not logged in');
    }
    $userId = (int)$_SESSION['userId'];

    // Validate required fields
    $requiredFields = array(
        'client_id' => 'Client',
        'items' => 'Cart items',
        'transaction_id' => 'Transaction ID',
        'payment_method' => 'Payment method',
        'payment_amount' => 'Payment amount'
    );

    foreach ($requiredFields as $field => $label) {
        if (!isset($input[$field]) || 
            (is_array($input[$field]) && empty($input[$field])) || 
            (!is_array($input[$field]) && trim($input[$field]) === '')) {
            throw new Exception($label . ' is required');
        }
    }

    // Validate payment data
    $paymentMethod = $input['payment_method'];
    $paymentAmount = floatval($input['payment_amount']);
    $validPaymentMethods = array('Cash', 'Bank Transfer', 'Check', 'Credit Card');

    if (!in_array($paymentMethod, $validPaymentMethods)) {
        throw new Exception('Invalid payment method');
    }

    if ($paymentAmount <= 0) {
        throw new Exception('Payment amount must be greater than zero');
    }

    // Validate transaction ID format and uniqueness
    $transactionId = trim($input['transaction_id']);
    if (!preg_match('/^[A-Za-z0-9-]+$/', $transactionId)) {
        throw new Exception('Invalid transaction ID format');
    }

    // Check for duplicate transaction
    $check_transaction_sql = "SELECT id FROM sales_orders WHERE transaction_id = ? LIMIT 1";
    $check_transaction_stmt = $connect->prepare($check_transaction_sql);
    if (!$check_transaction_stmt) {
        throw new Exception('Error preparing transaction check: ' . $connect->error);
    }

    $check_transaction_stmt->bind_param('s', $transactionId);
    $check_transaction_stmt->execute();
    $check_transaction_result = $check_transaction_stmt->get_result();

    if ($check_transaction_result->num_rows > 0) {
        throw new Exception('This transaction has already been processed');
    }
    $check_transaction_stmt->close();

    // Verify user exists and is active
    $userCheckSql = "SELECT user_id, status FROM users WHERE user_id = ? LIMIT 1";
    $userCheckStmt = $connect->prepare($userCheckSql);
    if (!$userCheckStmt) {
        throw new Exception('Error preparing user check: ' . $connect->error);
    }

    $userCheckStmt->bind_param('i', $userId);
    $userCheckStmt->execute();
    $userResult = $userCheckStmt->get_result();
    $userData = $userResult->fetch_assoc();

    if (!$userData) {
        throw new Exception('Invalid user account');
    }
    if ($userData['status'] != 1) {
        throw new Exception('User account is inactive');
    }
    $userCheckStmt->close();

    // Validate client
    $clientId = (int)$input['client_id'];
    $clientCheckSql = "SELECT id, status FROM clients WHERE id = ? LIMIT 1";
    $clientCheckStmt = $connect->prepare($clientCheckSql);
    if (!$clientCheckStmt) {
        throw new Exception('Error preparing client check: ' . $connect->error);
    }

    $clientCheckStmt->bind_param('i', $clientId);
    $clientCheckStmt->execute();
    $clientResult = $clientCheckStmt->get_result();
    $clientData = $clientResult->fetch_assoc();

    if (!$clientData) {
        throw new Exception('Invalid client');
    }
    if ($clientData['status'] != 1) {
        throw new Exception('Client account is inactive');
    }
    $clientCheckStmt->close();

    // Sanitize and validate amounts
    $subtotal = filter_var($input['subtotal'], FILTER_VALIDATE_FLOAT);
    $tax_amount = filter_var($input['tax_amount'], FILTER_VALIDATE_FLOAT);
    $discount_amount = filter_var($input['discount_amount'], FILTER_VALIDATE_FLOAT);
    $withholding_amount = isset($input['withholding_amount']) ? filter_var($input['withholding_amount'], FILTER_VALIDATE_FLOAT) : 0;
    $total_amount = filter_var($input['total_amount'], FILTER_VALIDATE_FLOAT);

    if ($subtotal === false || $tax_amount === false || $discount_amount === false || $total_amount === false) {
        throw new Exception('Invalid amount values provided');
    }

    if ($subtotal < 0 || $tax_amount < 0 || $discount_amount < 0 || $total_amount < 0) {
        throw new Exception('Amount values cannot be negative');
    }

    // Validate total amount calculation
    $calculatedTotal = $subtotal + $tax_amount - $discount_amount - $withholding_amount;
    if (abs($calculatedTotal - $total_amount) > 0.01) { // Allow for small floating point differences
        throw new Exception('Total amount calculation mismatch');
    }

    // Validate payment amount against total
    if ($paymentAmount > $total_amount) {
        throw new Exception('Payment amount cannot exceed total amount');
    }

    // Generate order number (format: POS-YYYYMMDD-XXXX)
    $date = date('Ymd');
    $sql = "SELECT MAX(CAST(SUBSTRING_INDEX(order_number, '-', -1) AS UNSIGNED)) as last_number 
            FROM sales_orders 
            WHERE order_number LIKE 'POS-$date-%'
            FOR UPDATE";

    $result = $connect->query($sql);
    if (!$result) {
        throw new Exception('Error checking order number: ' . $connect->error);
    }
    
    $row = $result->fetch_assoc();
    $next_number = ($row['last_number'] ?? 0) + 1;
    $order_number = sprintf("POS-%s-%04d", $date, $next_number);

    // Verify the order number is unique
    $check_sql = "SELECT id FROM sales_orders WHERE order_number = ?";
    $check_stmt = $connect->prepare($check_sql);
    if (!$check_stmt) {
        throw new Exception('Error preparing check statement: ' . $connect->error);
    }

    $check_stmt->bind_param('s', $order_number);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        throw new Exception('Failed to generate unique order number. Please try again.');
    }
    $check_stmt->close();

    // Create sales order
    $sql = "INSERT INTO sales_orders (
                order_number, 
                transaction_id,
                client_id, 
                order_date, 
                subtotal, 
                tax_amount, 
                discount_amount,
                withholding_amount,
                total_amount, 
                paid_amount,
                balance,
                payment_status, 
                order_status, 
                created_by, 
                created_at
            ) VALUES (
                ?, ?, ?, NOW(), ?, ?, ?, ?, ?, 
                0, ?, 'unpaid', 'pending', ?, NOW()
            )";

    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception('Error preparing order statement: ' . $connect->error);
    }

    $balance = $total_amount; // Initial balance is total amount
    $stmt->bind_param('ssiddddddi', 
        $order_number,
        $transactionId,
        $clientId, 
        $subtotal,
        $tax_amount,
        $discount_amount,
        $withholding_amount,
        $total_amount,
        $balance,
        $userId // Using the session user ID directly
    );

    if (!$stmt->execute()) {
        throw new Exception('Error creating order: ' . $stmt->error);
    }

    $order_id = $stmt->insert_id;
    $stmt->close();

    // Create order items
    foreach ($input['items'] as $item) {
        $production_product_id = (int)$item['id'];
        
        // Get or create corresponding product_id in products table
        $sql = "SELECT product_id FROM products WHERE production_product_id = ? LIMIT 1";
        $stmt = $connect->prepare($sql);
        if (!$stmt) {
            throw new Exception('Error preparing product check: ' . $connect->error);
        }

        $stmt->bind_param('i', $production_product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Get production product details
            $prod_sql = "SELECT * FROM production_products WHERE id = ? LIMIT 1";
            $prod_stmt = $connect->prepare($prod_sql);
            if (!$prod_stmt) {
                throw new Exception('Error preparing production product query: ' . $connect->error);
            }

            $prod_stmt->bind_param('i', $production_product_id);
            $prod_stmt->execute();
            $prod_result = $prod_stmt->get_result();
            $prod_data = $prod_result->fetch_assoc();
            $prod_stmt->close();

            // Insert into products table
            $insert_sql = "INSERT INTO products (
                production_product_id,
                product_code,
                name,
                description,
                selling_price,
                brand_id,
                unit,
                current_stock,
                min_stock_level,
                production_cost,
                category_id,
                status,
                created_at,
                created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW(), ?)";

            $insert_stmt = $connect->prepare($insert_sql);
            if (!$insert_stmt) {
                throw new Exception('Error preparing product insert: ' . $connect->error);
            }

            $insert_stmt->bind_param('isssisddddii',
                $production_product_id,
                $prod_data['product_code'],
                $prod_data['name'],
                $prod_data['description'],
                $prod_data['selling_price'],
                $prod_data['brand_id'],
                $prod_data['unit'],
                $prod_data['current_stock'],
                $prod_data['min_stock_level'],
                $prod_data['production_cost'],
                $prod_data['category_id'],
                $userId
            );

            if (!$insert_stmt->execute()) {
                throw new Exception('Error inserting product: ' . $insert_stmt->error);
            }

            $product_id = $insert_stmt->insert_id;
            $insert_stmt->close();
        } else {
            $row = $result->fetch_assoc();
            $product_id = $row['product_id'];
        }
        $stmt->close();

        $quantity = (int)$item['quantity'];
        $unit_price = (float)$item['price'];
        $item_subtotal = $quantity * $unit_price;
        $tax_rate = 15; // Fixed VAT rate
        $tax_amount = ($item_subtotal * $tax_rate) / 100;
        $total = $item_subtotal + $tax_amount;

        $sql = "INSERT INTO sales_order_items (
                    sales_order_id, 
                    product_id, 
                    quantity, 
                    unit_price,
                    tax_rate, 
                    tax_amount, 
                    subtotal, 
                    total,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $connect->prepare($sql);
        if (!$stmt) {
            throw new Exception('Error preparing item statement: ' . $connect->error);
        }

        $stmt->bind_param('iiiddddd',
            $order_id,
            $product_id, // Using the mapped product_id
            $quantity,
            $unit_price,
            $tax_rate,
            $tax_amount,
            $item_subtotal,
            $total
        );

        if (!$stmt->execute()) {
            throw new Exception('Error creating order item: ' . $stmt->error);
        }

        $stmt->close();

        // Update product stock in both tables
        $sql = "UPDATE production_products 
                SET current_stock = current_stock - ? 
                WHERE id = ?";
        
        $stmt = $connect->prepare($sql);
        if (!$stmt) {
            throw new Exception('Error preparing stock update: ' . $connect->error);
        }

        $stmt->bind_param('di', $quantity, $production_product_id);
        if (!$stmt->execute()) {
            throw new Exception('Error updating production stock: ' . $stmt->error);
        }
        $stmt->close();

        // Also update products table
        $sql = "UPDATE products 
                SET current_stock = current_stock - ? 
                WHERE product_id = ?";
        
        $stmt = $connect->prepare($sql);
        if (!$stmt) {
            throw new Exception('Error preparing products stock update: ' . $connect->error);
        }

        $stmt->bind_param('di', $quantity, $product_id);
        if (!$stmt->execute()) {
            throw new Exception('Error updating products stock: ' . $stmt->error);
        }
        $stmt->close();
    }

    // If we got here, commit the transaction
    $connect->commit();

    // Return success response with order details
    echo json_encode([
        'success' => true,
        'message' => 'Order created successfully',
        'order' => [
            'id' => $order_id,
            'order_number' => $order_number,
            'total_amount' => $total_amount
        ]
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $connect->rollback();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($connect)) {
        $connect->close();
    }
} 