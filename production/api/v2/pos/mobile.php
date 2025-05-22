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

switch($method) {
    case 'GET':
        // Get products for POS
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $category = isset($_GET['category']) ? $_GET['category'] : '';
        
        try {
            $sql = "SELECT p.*, c.category_name, b.brand_name 
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.category_id 
                    LEFT JOIN brands b ON p.brand_id = b.brand_id 
                    WHERE p.active = 1 AND p.quantity > 0";
            
            $params = [];
            $types = "";
            
            if($search) {
                $sql .= " AND (p.product_name LIKE ? OR p.product_code LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $types .= "ss";
            }
            
            if($category) {
                $sql .= " AND p.category_id = ?";
                $params[] = $category;
                $types .= "i";
            }
            
            $sql .= " ORDER BY p.product_name";
            
            $stmt = $connect->prepare($sql);
            
            if(!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            $products = [];
            while($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $products
            ]);
            
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch products: ' . $e->getMessage()]);
        }
        break;
        
    case 'POST':
        // Process POS checkout
        $data = json_decode(file_get_contents('php://input'), true);
        
        if(!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid request data']);
            exit();
        }
        
        try {
            $connect->begin_transaction();
            
            // Create sales order
            $orderSql = "INSERT INTO sales_orders (client_id, order_date, notes, created_by) 
                        VALUES (?, NOW(), ?, ?)";
            $orderStmt = $connect->prepare($orderSql);
            $orderStmt->bind_param("isi", 
                $data['client_id'],
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
                
                // Update product stock
                $updateStockSql = "UPDATE products SET quantity = quantity - ? WHERE product_id = ?";
                $updateStockStmt = $connect->prepare($updateStockSql);
                $updateStockStmt->bind_param("ii", $item['quantity'], $item['product_id']);
                $updateStockStmt->execute();
            }
            
            // Process payment
            $paymentSql = "INSERT INTO sales_payments (order_id, amount, payment_method, reference_number, notes, created_by) 
                          VALUES (?, ?, ?, ?, ?, ?)";
            $paymentStmt = $connect->prepare($paymentSql);
            $paymentStmt->bind_param("idsssi", 
                $orderId,
                $data['payment']['amount'],
                $data['payment']['method'],
                $data['payment']['reference'],
                $data['payment']['notes'],
                $_SESSION['userId']
            );
            $paymentStmt->execute();
            
            $connect->commit();
            
            // Generate receipt
            $receipt = [
                'order_id' => $orderId,
                'date' => date('Y-m-d H:i:s'),
                'items' => $data['items'],
                'subtotal' => $data['subtotal'],
                'tax' => $data['tax'],
                'discount' => $data['discount'],
                'total' => $data['total'],
                'payment' => $data['payment']
            ];
            
            echo json_encode([
                'success' => true, 
                'message' => 'Checkout completed successfully',
                'receipt' => $receipt
            ]);
            
        } catch(Exception $e) {
            $connect->rollback();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to process checkout: ' . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
} 