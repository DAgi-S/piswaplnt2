<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    $response = array(
        'success' => false,
        'messages' => 'User not logged in'
    );
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

$response = array();

try {
    // Fetch active products with their details
    $sql = "SELECT p.product_id, p.product_image, p.name, p.selling_price, p.current_stock, 
            COALESCE((SELECT SUM(oi.quantity) FROM order_items oi WHERE oi.product_id = p.product_id), 0) as total_purchased,
            b.name as brand_name, c.name as category_name, p.status 
            FROM products p 
            LEFT JOIN brands b ON p.brand_id = b.brand_id 
            LEFT JOIN categories c ON p.category_id = c.category_id 
            WHERE p.status = 'active' 
            ORDER BY p.name ASC";
            
    $result = $connect->query($sql);
    
    if($result) {
        $data = array();
        while($row = $result->fetch_assoc()) {
            $productImage = "";
            if($row['product_image'] != null && $row['product_image'] != '') {
                $productImage = $row['product_image'];
            }

            $data[] = array(
                'product_image' => $productImage,
                'name' => $row['name'],
                'selling_price' => $row['selling_price'],
                'current_stock' => $row['current_stock'],
                'total_purchased' => $row['total_purchased'],
                'brand_name' => $row['brand_name'],
                'category_name' => $row['category_name'],
                'status' => $row['status'],
                'product_id' => $row['product_id']
            );
        }
        
        $response['success'] = true;
        $response['data'] = $data;
    } else {
        throw new Exception("Error fetching products: " . $connect->error);
    }
    
} catch(Exception $e) {
    $response['success'] = false;
    $response['messages'] = $e->getMessage();
}

// Set proper headers
header('Content-Type: application/json');
echo json_encode($response);
exit();
?> 