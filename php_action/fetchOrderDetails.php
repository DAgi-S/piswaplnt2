<?php
require_once 'core.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$valid['success'] = false;
$valid['messages'] = array();

if(isset($_POST['orderId'])) {
    $orderId = intval($_POST['orderId']);
    
    try {
        // Get order details
        $sql = "SELECT o.*, u.username 
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.user_id 
                WHERE o.order_id = ?";
        
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $orderData = $result->fetch_assoc();
        
        if($orderData) {
            // Get order items with product details
            $itemSql = "SELECT oi.order_item_id, oi.quantity, oi.rate, oi.total, 
                              p.product_id, p.name, p.product_code, p.selling_price 
                       FROM order_items oi
                       INNER JOIN products p ON oi.product_id = p.product_id 
                       WHERE oi.order_id = ? 
                       ORDER BY oi.order_item_id ASC";
            
            $itemStmt = $connect->prepare($itemSql);
            if (!$itemStmt) {
                throw new Exception("Failed to prepare item statement: " . $connect->error);
            }
            
            $itemStmt->bind_param("i", $orderId);
            if (!$itemStmt->execute()) {
                throw new Exception("Failed to execute item query: " . $itemStmt->error);
            }
            
            $itemResult = $itemStmt->get_result();
            
            // Debug information
            error_log("Order ID: " . $orderId);
            error_log("Number of items found: " . $itemResult->num_rows);
            
            $items = array();
            while($row = $itemResult->fetch_assoc()) {
                // Debug information
                error_log("Processing item: " . json_encode($row));
                
                // Format the data
                $items[] = array(
                    'name' => $row['name'],
                    'quantity' => intval($row['quantity']),
                    'rate' => number_format((float)$row['rate'], 2, '.', ''),
                    'total' => number_format((float)$row['total'], 2, '.', '')
                );
            }
            
            $valid['success'] = true;
            $valid['order'] = $orderData;
            $valid['items'] = $items;
            
            // Debug information
            error_log("Final response: " . json_encode($valid));
            
        } else {
            $valid['success'] = false;
            $valid['messages'] = "Order not found";
            error_log("Order not found for ID: " . $orderId);
        }
        
    } catch(Exception $e) {
        $valid['success'] = false;
        $valid['messages'] = $e->getMessage();
        error_log("Error in fetchOrderDetails.php: " . $e->getMessage());
    }
    
} else {
    $valid['success'] = false;
    $valid['messages'] = "Invalid request";
    error_log("No order ID provided in request");
}

$connect->close();

header('Content-Type: application/json');
echo json_encode($valid); 