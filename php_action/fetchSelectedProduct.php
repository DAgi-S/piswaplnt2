<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'core.php';

// Set proper headers for JSON response
header('Content-Type: application/json');

if (!isset($_POST['productId'])) {
    echo json_encode(array(
        'success' => false,
        'messages' => 'Product ID is required'
    ));
    exit();
}

try {
    $productId = intval($_POST['productId']);
    
    $sql = "SELECT p.*, b.name as brand_name, c.name as category_name
            FROM products p 
            LEFT JOIN brands b ON p.brand_id = b.brand_id 
            LEFT JOIN categories c ON p.category_id = c.category_id 
            WHERE p.product_id = ?";
    
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Format the response data
        $response = array(
            'success' => true,
            'product_id' => $row['product_id'],
            'name' => $row['name'],
            'selling_price' => $row['selling_price'],
            'current_stock' => $row['current_stock'],
            'brand_id' => $row['brand_id'],
            'category_id' => $row['category_id'],
            'status' => $row['status'] ?: 'active',
            'brand_name' => $row['brand_name'],
            'category_name' => $row['category_name']
        );
    } else {
        $response = array(
            'success' => false,
            'messages' => 'Product not found'
        );
    }
    
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error in fetchSelectedProduct.php: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'messages' => 'Error fetching product: ' . $e->getMessage()
    ));
}

if (isset($connect)) {
    $connect->close();
} 