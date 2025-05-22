<?php
require_once 'core.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

function logError($message, $data = []) {
    $logFile = 'error_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
    if (!empty($data)) {
        $logMessage .= "Data: " . print_r($data, true) . PHP_EOL;
    }
    error_log($logMessage, 3, $logFile);
}

if($_POST) {
    try {
        // Validate required fields
        $requiredFields = ['orderDate', 'fsNumber', 'clientName', 'clientContact'];
        $missingFields = [];
        
        foreach($requiredFields as $field) {
            if(!isset($_POST[$field]) || empty($_POST[$field])) {
                $missingFields[] = $field;
            }
        }
        
        if(!empty($missingFields)) {
            throw new Exception("Missing required fields: " . implode(', ', $missingFields));
        }

        $connect->begin_transaction();

        // Get form data and sanitize
        $orderDate = date('Y-m-d', strtotime($_POST['orderDate']));
        $fsNumber = mysqli_real_escape_string($connect, $_POST['fsNumber']);
        $clientName = mysqli_real_escape_string($connect, $_POST['clientName']);
        $clientContact = mysqli_real_escape_string($connect, $_POST['clientContact']);
        $clientTin = mysqli_real_escape_string($connect, $_POST['clientTin'] ?? '');
        $subTotal = filter_var($_POST['subTotal'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $vat = filter_var($_POST['vat'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $totalAmount = $subTotal + $vat;
        $grandTotal = filter_var($_POST['grandTotal'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $paymentType = filter_var($_POST['paymentType'], FILTER_SANITIZE_NUMBER_INT);
        $userId = $_SESSION['userId'];

        // Debug log
        logError("Order Data:", [
            'orderDate' => $orderDate,
            'fsNumber' => $fsNumber,
            'clientName' => $clientName,
            'subTotal' => $subTotal,
            'vat' => $vat,
            'totalAmount' => $totalAmount
        ]);

        // Insert order - Note the exact number of placeholders (?)
        $sql = "INSERT INTO orders (
            order_date, 
            client_name, 
            client_contact, 
            sub_total, 
            vat, 
            total_amount, 
            grand_total, 
            payment_type, 
            order_status, 
            user_id, 
            gstn, 
            fsnum
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?)";
        
        $stmt = $connect->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $connect->error);
        }

        // Bind exactly 11 parameters to match the 11 placeholders (excluding the hardcoded 1)
        $bindResult = $stmt->bind_param(
            "sssddddiiis", // 11 parameters: 3 strings, 4 doubles, 3 integers, 1 string
            $orderDate,    // s
            $clientName,   // s
            $clientContact,// s
            $subTotal,     // d
            $vat,         // d
            $totalAmount,  // d
            $grandTotal,   // d
            $paymentType,  // i
            $userId,       // i
            $clientTin,    // s
            $fsNumber      // s
        );

        if (!$bindResult) {
            throw new Exception("Binding parameters failed: " . $stmt->error);
        }

        if (!$stmt->execute()) {
            throw new Exception("Order insertion failed: " . $stmt->error);
        }

        $orderId = $connect->insert_id;

        // Process order items
        if (!isset($_POST['productName']) || !is_array($_POST['productName'])) {
            throw new Exception("No products selected");
        }

        foreach($_POST['productName'] as $key => $productId) {
            // Validate product data
            if (!isset($_POST['quantity'][$key], $_POST['price'][$key], $_POST['total'][$key])) {
                throw new Exception("Missing product details for item #" . ($key + 1));
            }

            $quantity = filter_var($_POST['quantity'][$key], FILTER_SANITIZE_NUMBER_INT);
            $price = filter_var($_POST['price'][$key], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $total = filter_var($_POST['total'][$key], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

            // Check product availability
            $checkStmt = $connect->prepare("SELECT quantity FROM products WHERE product_id = ?");
            $checkStmt->bind_param("i", $productId);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $currentStock = $result->fetch_assoc()['quantity'];

            if ($currentStock < $quantity) {
                throw new Exception("Insufficient stock for product ID: " . $productId);
            }

            // Update product quantity
            $updateStmt = $connect->prepare("UPDATE products SET quantity = quantity - ? WHERE product_id = ?");
            if (!$updateStmt) {
                throw new Exception("Prepare failed for product update: " . $connect->error);
            }
            $updateStmt->bind_param("ii", $quantity, $productId);
            if (!$updateStmt->execute()) {
                throw new Exception("Failed to update product quantity: " . $updateStmt->error);
            }

            // Insert order item
            $itemStmt = $connect->prepare("INSERT INTO order_items (
                order_id, product_id, quantity, rate, total, order_item_status
            ) VALUES (?, ?, ?, ?, ?, 1)");
            if (!$itemStmt) {
                throw new Exception("Prepare failed for order item: " . $connect->error);
            }
            $itemStmt->bind_param("iiidd", $orderId, $productId, $quantity, $price, $total);
            if (!$itemStmt->execute()) {
                throw new Exception("Failed to insert order item: " . $itemStmt->error);
            }
        }

        $connect->commit();
        echo json_encode([
            'success' => true, 
            'message' => 'Order created successfully',
            'orderId' => $orderId
        ]);

    } catch (Exception $e) {
        $connect->rollback();
        logError("Order Creation Error: " . $e->getMessage(), $_POST);
        echo json_encode([
            'success' => false, 
            'message' => 'Error creating order: ' . $e->getMessage()
        ]);
    } finally {
        $connect->close();
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
} 