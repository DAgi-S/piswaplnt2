<?php 	
require_once 'core.php';
require_once 'telegram_notification.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user has permission to create orders
if (!hasPermission('order.create')) {
    $valid['success'] = false;
    $valid['messages'] = "You don't have permission to create orders";
    echo json_encode($valid);
    exit();
}

$valid['success'] = false;
$valid['messages'] = array();

if($_POST) {	
    try {
        // Validate and sanitize inputs
        $orderDate = isset($_POST['orderDate']) ? date('Y-m-d', strtotime($_POST['orderDate'])) : null;
        $fsNum = isset($_POST['FsNum']) ? mysqli_real_escape_string($connect, $_POST['FsNum']) : '';
        $clientId = isset($_POST['clientId']) ? intval($_POST['clientId']) : 0;
        
        // Get client details
        $clientQuery = "SELECT company_name, phone, tin_number FROM clients WHERE id = ?";
        $clientStmt = $connect->prepare($clientQuery);
        $clientStmt->bind_param("i", $clientId);
        $clientStmt->execute();
        $clientResult = $clientStmt->get_result();
        $clientData = $clientResult->fetch_assoc();
        
        if (!$clientData) {
            throw new Exception("Invalid client selected");
        }
        
        $clientName = $clientData['company_name'];
        $clientContact = $clientData['phone'];
        $clientTin = $clientData['tin_number'];
        
        // Get other form data
        $subTotal = isset($_POST['subTotalValue']) ? filter_var($_POST['subTotalValue'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : 0;
        $vat = isset($_POST['vatValue']) ? filter_var($_POST['vatValue'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : 0;
        $totalAmount = isset($_POST['totalAmountValue']) ? filter_var($_POST['totalAmountValue'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : 0;
        $grandTotal = isset($_POST['grandTotalValue']) ? filter_var($_POST['grandTotalValue'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : 0;
        
        $paymentType = isset($_POST['paymentType']) ? mysqli_real_escape_string($connect, $_POST['paymentType']) : '';
        $paymentStatus = isset($_POST['paymentStatus']) ? intval($_POST['paymentStatus']) : 0;
        
        $withholdingEnabled = isset($_POST['withholding_enabled']) ? 1 : 0;
        $withholdingAmount = isset($_POST['withholdingValue']) ? filter_var($_POST['withholdingValue'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : 0;
        
        $note = isset($_POST['note']) ? mysqli_real_escape_string($connect, $_POST['note']) : '';
        
        // Validate required fields
        if(!$orderDate || !$clientId || !$paymentType || !$paymentStatus) {
            throw new Exception("Required fields are missing: Please fill in all required fields");
        }
        
        // Validate product data
        if(!isset($_POST['productName']) || !is_array($_POST['productName']) || empty($_POST['productName'])) {
            throw new Exception("Please add at least one product");
        }
        
        // Start transaction
        $connect->begin_transaction();
        
        // Insert order
        $orderSql = "INSERT INTO orders (order_date, client_name, client_contact, sub_total, vat, 
                     total_amount, grand_total, payment_type, payment_status, gstn, fsnum, 
                     withholding_tax_enabled, withholding_tax_amount, user_id) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $connect->prepare($orderSql);
        if(!$stmt) {
            throw new Exception("Prepare failed: " . $connect->error);
        }
        
        $userId = isset($_SESSION['userId']) ? $_SESSION['userId'] : 1; // Default to 1 if not set
        
        $stmt->bind_param("sssddddsissiid",
            $orderDate,
            $clientName,
            $clientContact,
            $subTotal,
            $vat,
            $totalAmount,
            $grandTotal,
            $paymentType,
            $paymentStatus,
            $clientTin,
            $fsNum,
            $withholdingEnabled,
            $withholdingAmount,
            $userId
        );
        
        if(!$stmt->execute()) {
            throw new Exception("Error creating order: " . $stmt->error);
        }
        
        $orderId = $connect->insert_id;
        
        // Insert order items
        $orderItemSql = "INSERT INTO order_items (order_id, product_id, quantity, rate, total, order_item_status) 
                        VALUES (?, ?, ?, ?, ?, 1)";
        $itemStmt = $connect->prepare($orderItemSql);
        if(!$itemStmt) {
            throw new Exception("Prepare failed for order items: " . $connect->error);
        }
        
        foreach($_POST['productName'] as $key => $productId) {
            if(empty($productId)) continue;
            
            $quantity = filter_var($_POST['quantity'][$key], FILTER_SANITIZE_NUMBER_INT);
            $rate = filter_var($_POST['rateValue'][$key], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $total = filter_var($_POST['totalValue'][$key], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            
            // Verify product exists and has enough stock
            $checkStockSql = "SELECT current_stock FROM products WHERE product_id = ? AND status = 'active'";
            $stockStmt = $connect->prepare($checkStockSql);
            $stockStmt->bind_param("i", $productId);
            $stockStmt->execute();
            $stockResult = $stockStmt->get_result();
            $stockData = $stockResult->fetch_assoc();
            
            if(!$stockData) {
                throw new Exception("Product not found or inactive: ID " . $productId);
            }
            
            if($stockData['current_stock'] < $quantity) {
                throw new Exception("Insufficient stock for product ID " . $productId);
            }
            
            // Insert order item
            $itemStmt->bind_param("iiddd", 
                $orderId,
                $productId,
                $quantity,
                $rate,
                $total
            );
            
            if(!$itemStmt->execute()) {
                throw new Exception("Error adding order item: " . $itemStmt->error);
            }
            
            // Update product stock
            $updateStockSql = "UPDATE products SET current_stock = current_stock - ? WHERE product_id = ?";
            $updateStmt = $connect->prepare($updateStockSql);
            $updateStmt->bind_param("di", $quantity, $productId);
            
            if(!$updateStmt->execute()) {
                throw new Exception("Error updating product stock: " . $updateStmt->error);
            }
        }
        
        // If we got here, commit the transaction
        $connect->commit();
        
        // Prepare order details for notification
        $productDetails = array();
        foreach($_POST['productName'] as $key => $productId) {
            if(empty($productId)) continue;
            
            // Get product name
            $productQuery = "SELECT name FROM products WHERE product_id = ?";
            $productStmt = $connect->prepare($productQuery);
            $productStmt->bind_param("i", $productId);
            $productStmt->execute();
            $productResult = $productStmt->get_result();
            $productData = $productResult->fetch_assoc();
            
            $quantity = $_POST['quantity'][$key];
            $rate = $_POST['rateValue'][$key];
            $total = $_POST['totalValue'][$key];
            
            $productDetails[] = sprintf(
                "%s x%d @ %s = %s",
                $productData['name'],
                $quantity,
                number_format($rate, 2),
                number_format($total, 2)
            );
        }

        // Create Telegram notification message
        $paymentStatusText = '';
        switch($paymentStatus) {
            case 1: $paymentStatusText = 'Full Payment'; break;
            case 2: $paymentStatusText = 'Advance Payment'; break;
            case 3: $paymentStatusText = 'No Payment'; break;
            default: $paymentStatusText = 'Unknown';
        }

        $telegramMessage = "🛍️ <b>New Order Created</b>\n\n".
            "Date: " . date('d M Y', strtotime($orderDate)) . "\n".
            "FS Number: " . ($fsNum ? $fsNum : 'N/A') . "\n".
            "Client: " . $clientName . "\n".
            "Contact: " . $clientContact . "\n".
            "TIN: " . ($clientTin ? $clientTin : 'N/A') . "\n\n".
            "<b>Products:</b>\n" . implode("\n", $productDetails) . "\n\n".
            "Sub Total: " . number_format($subTotal, 2) . "\n".
            "VAT (15%): " . number_format($vat, 2) . "\n";

        if($withholdingEnabled) {
            $telegramMessage .= "Withholding (2%): " . number_format($withholdingAmount, 2) . "\n";
        }

        $telegramMessage .= "Grand Total: " . number_format($grandTotal, 2) . "\n".
            "Payment Type: " . $paymentType . "\n".
            "Payment Status: " . $paymentStatusText;

        if(!empty($note)) {
            $telegramMessage .= "\nNote: " . $note;
        }

        // Send Telegram notification
        sendTelegramNotification($telegramMessage);
        
        $valid['success'] = true;
        $valid['messages'] = "Order Successfully Created";
        
    } catch(Exception $e) {
        // Something went wrong, rollback the transaction
        $connect->rollback();
        
        $valid['success'] = false;
        $valid['messages'] = $e->getMessage();
        
        error_log("Error in createOrder.php: " . $e->getMessage());
    }
    
    $connect->close();
    
    echo json_encode($valid);
}