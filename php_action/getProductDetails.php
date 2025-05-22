<?php
require_once 'core.php';

if(isset($_POST['productId'])) {
    $productId = $_POST['productId'];
    
    // Get product details including quantity and price
    $sql = "SELECT p.*, 
            COALESCE(SUM(pi.quantity), 0) as total_purchased
            FROM products p
            LEFT JOIN purchase_items pi ON p.product_id = pi.product_id
            WHERE p.product_id = ?
            GROUP BY p.product_id";
            
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($row = $result->fetch_assoc()) {
        $response = array(
            'success' => true,
            'product_id' => $row['product_id'],
            'name' => $row['name'],
            'price' => $row['price'],
            'quantity' => $row['quantity'],
            'purchased' => $row['total_purchased']
        );
    } else {
        $response = array(
            'success' => false,
            'message' => 'Product not found'
        );
    }
    
    echo json_encode($response);
}