<?php
header('Content-Type: application/json');
require_once '../../../includes/header.php';
require_once '../../../php_action/core.php';

// Check if user is logged in
if(!isset($_SESSION['userId'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Get order ID from URL if present
$orderId = isset($_GET['id']) ? $_GET['id'] : null;

// Handle different HTTP methods
switch($method) {
    case 'GET':
        if($orderId) {
            // Get specific order
            $sql = "SELECT so.*, c.client_name, u.username as created_by_name 
                    FROM sales_orders so 
                    LEFT JOIN clients c ON so.client_id = c.client_id 
                    LEFT JOIN users u ON so.created_by = u.user_id 
                    WHERE so.id = ?";
            $stmt = $connect->prepare($sql);
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if($result->num_rows > 0) {
                $order = $result->fetch_assoc();
                
                // Get order items
                $itemsSql = "SELECT soi.*, p.product_name 
                           FROM sales_order_items soi 
                           LEFT JOIN products p ON soi.product_id = p.product_id 
                           WHERE soi.order_id = ?";
                $itemsStmt = $connect->prepare($itemsSql);
                $itemsStmt->bind_param("i", $orderId);
                $itemsStmt->execute();
                $itemsResult = $itemsStmt->get_result();
                
                $order['items'] = [];
                while($item = $itemsResult->fetch_assoc()) {
                    $order['items'][] = $item;
                }
                
                echo json_encode(['success' => true, 'data' => $order]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Order not found']);
            }
        } else {
            // List all orders
            $sql = "SELECT so.*, c.client_name, u.username as created_by_name 
                    FROM sales_orders so 
                    LEFT JOIN clients c ON so.client_id = c.client_id 
                    LEFT JOIN users u ON so.created_by = u.user_id 
                    ORDER BY so.created_at DESC";
            $result = $connect->query($sql);
            
            $orders = [];
            while($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }
            
            echo json_encode(['success' => true, 'data' => $orders]);
        }
        break;
        
    case 'POST':
        // Create new order
        $data = json_decode(file_get_contents('php://input'), true);
        
        if(!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid request data']);
            exit();
        }
        
        try {
            $connect->begin_transaction();
            
            // Insert order
            $orderSql = "INSERT INTO sales_orders (client_id, order_date, delivery_date, notes, created_by) 
                        VALUES (?, ?, ?, ?, ?)";
            $orderStmt = $connect->prepare($orderSql);
            $orderStmt->bind_param("isssi", 
                $data['client_id'],
                $data['order_date'],
                $data['delivery_date'],
                $data['notes'],
                $_SESSION['userId']
            );
            $orderStmt->execute();
            $orderId = $connect->insert_id;
            
            // Insert order items
            $itemSql = "INSERT INTO sales_order_items (order_id, product_id, quantity, unit_price, tax_rate, discount) 
                       VALUES (?, ?, ?, ?, ?, ?)";
            $itemStmt = $connect->prepare($itemSql);
            
            foreach($data['items'] as $item) {
                $itemStmt->bind_param("iiiddd", 
                    $orderId,
                    $item['product_id'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['tax_rate'],
                    $item['discount']
                );
                $itemStmt->execute();
            }
            
            $connect->commit();
            echo json_encode(['success' => true, 'message' => 'Order created successfully', 'order_id' => $orderId]);
        } catch(Exception $e) {
            $connect->rollback();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create order: ' . $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        if(!$orderId) {
            http_response_code(400);
            echo json_encode(['error' => 'Order ID is required']);
            exit();
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if(!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid request data']);
            exit();
        }
        
        try {
            $connect->begin_transaction();
            
            // Update order
            $orderSql = "UPDATE sales_orders 
                        SET client_id = ?, order_date = ?, delivery_date = ?, notes = ? 
                        WHERE id = ?";
            $orderStmt = $connect->prepare($orderSql);
            $orderStmt->bind_param("isssi", 
                $data['client_id'],
                $data['order_date'],
                $data['delivery_date'],
                $data['notes'],
                $orderId
            );
            $orderStmt->execute();
            
            // Delete existing items
            $deleteSql = "DELETE FROM sales_order_items WHERE order_id = ?";
            $deleteStmt = $connect->prepare($deleteSql);
            $deleteStmt->bind_param("i", $orderId);
            $deleteStmt->execute();
            
            // Insert new items
            $itemSql = "INSERT INTO sales_order_items (order_id, product_id, quantity, unit_price, tax_rate, discount) 
                       VALUES (?, ?, ?, ?, ?, ?)";
            $itemStmt = $connect->prepare($itemSql);
            
            foreach($data['items'] as $item) {
                $itemStmt->bind_param("iiiddd", 
                    $orderId,
                    $item['product_id'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['tax_rate'],
                    $item['discount']
                );
                $itemStmt->execute();
            }
            
            $connect->commit();
            echo json_encode(['success' => true, 'message' => 'Order updated successfully']);
        } catch(Exception $e) {
            $connect->rollback();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update order: ' . $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        if(!$orderId) {
            http_response_code(400);
            echo json_encode(['error' => 'Order ID is required']);
            exit();
        }
        
        try {
            $connect->begin_transaction();
            
            // Delete order items first
            $deleteItemsSql = "DELETE FROM sales_order_items WHERE order_id = ?";
            $deleteItemsStmt = $connect->prepare($deleteItemsSql);
            $deleteItemsStmt->bind_param("i", $orderId);
            $deleteItemsStmt->execute();
            
            // Delete order
            $deleteOrderSql = "DELETE FROM sales_orders WHERE id = ?";
            $deleteOrderStmt = $connect->prepare($deleteOrderSql);
            $deleteOrderStmt->bind_param("i", $orderId);
            $deleteOrderStmt->execute();
            
            $connect->commit();
            echo json_encode(['success' => true, 'message' => 'Order deleted successfully']);
        } catch(Exception $e) {
            $connect->rollback();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete order: ' . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
} 