<?php
require_once 'core.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$valid['success'] = false;
$valid['messages'] = array();

if($_POST) {
    try {
        // Sanitize and validate inputs
        $orderId = isset($_POST['orderId']) ? intval($_POST['orderId']) : 0;
        $orderDate = isset($_POST['orderDate']) ? date('Y-m-d', strtotime($_POST['orderDate'])) : null;
        $clientName = isset($_POST['clientName']) ? mysqli_real_escape_string($connect, $_POST['clientName']) : '';
        $clientContact = isset($_POST['clientContact']) ? mysqli_real_escape_string($connect, $_POST['clientContact']) : '';
        $clientTin = isset($_POST['clientTin']) ? mysqli_real_escape_string($connect, $_POST['clientTin']) : '';
        
        // Validate numeric inputs
        $subTotal = isset($_POST['subTotalValue']) ? filter_var($_POST['subTotalValue'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : 0;
        $vat = isset($_POST['vatValue']) ? filter_var($_POST['vatValue'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : 0;
        $totalAmount = isset($_POST['totalAmountValue']) ? filter_var($_POST['totalAmountValue'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : 0;
        $grandTotal = isset($_POST['grandTotalValue']) ? filter_var($_POST['grandTotalValue'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : 0;
        
        // Replace deprecated FILTER_SANITIZE_STRING with htmlspecialchars
        $paymentType = isset($_POST['paymentType']) ? htmlspecialchars(trim($_POST['paymentType']), ENT_QUOTES, 'UTF-8') : '';
        $paymentStatus = isset($_POST['paymentStatus']) ? intval($_POST['paymentStatus']) : 0;
        
        // Add withholding tax fields
        $withholdingEnabled = isset($_POST['withholding_enabled']) ? 1 : 0;
        $withholdingAmount = isset($_POST['withholdingValue']) ? filter_var($_POST['withholdingValue'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : 0;

        // Input validation
        if(!$orderId || !$orderDate || !$clientName || !$clientContact) {
            throw new Exception("Required fields are missing");
        }

        // Start transaction
        $connect->begin_transaction();

        // Update the order
        $sql = "UPDATE orders SET 
                order_date = ?, 
                client_name = ?, 
                client_contact = ?, 
                sub_total = ?, 
                vat = ?, 
                total_amount = ?, 
                grand_total = ?, 
                payment_type = ?, 
                payment_status = ?, 
                gstn = ?,
                withholding_tax_enabled = ?,
                withholding_tax_amount = ?
                WHERE order_id = ?";
        
        $stmt = $connect->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $connect->error);
        }
        
        $stmt->bind_param("sssddddsiiddi", 
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
            $withholdingEnabled,
            $withholdingAmount,
            $orderId
        );

        if(!$stmt->execute()) {
            throw new Exception("Error updating order: " . $stmt->error);
        }

        // Remove old order items
        $removeItems = "DELETE FROM order_items WHERE order_id = ?";
        $stmt = $connect->prepare($removeItems);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();

        // Add new order items
        if(isset($_POST['productName']) && is_array($_POST['productName'])) {
            $orderItemSql = "INSERT INTO order_items (order_id, product_id, quantity, rate, total) 
                            VALUES (?, ?, ?, ?, ?)";
            $itemStmt = $connect->prepare($orderItemSql);

            foreach($_POST['productName'] as $key => $productId) {
                if(empty($productId)) continue;

                $quantity = filter_var($_POST['quantity'][$key], FILTER_SANITIZE_NUMBER_INT);
                $rate = filter_var($_POST['rateValue'][$key], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                $total = filter_var($_POST['totalValue'][$key], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

                $itemStmt->bind_param("iiidd", 
                    $orderId,
                    $productId,
                    $quantity,
                    $rate,
                    $total
                );
                
                if(!$itemStmt->execute()) {
                    throw new Exception("Error adding order item: " . $itemStmt->error);
                }
            }
        }

        // If we got here, commit the transaction
        $connect->commit();
        
        $valid['success'] = true;
        $valid['messages'] = "Order Successfully Updated";
        
    } catch(Exception $e) {
        // Something went wrong, rollback the transaction
        $connect->rollback();
        
        $valid['success'] = false;
        $valid['messages'] = $e->getMessage();
        
        error_log("Error in editOrder.php: " . $e->getMessage());
    }

    $connect->close();
    
    echo json_encode($valid);
}