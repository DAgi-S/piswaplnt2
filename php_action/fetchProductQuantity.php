<?php
require_once 'core.php';

if(isset($_POST['productId'])) {
    $productId = $_POST['productId'];
    
    // Get available quantity from products table
    $sql = "SELECT quantity FROM products WHERE product_id = $productId";
    $result = $connect->query($sql);
    $row = $result->fetch_array();
    $available = $row['quantity'];
    
    // Get total purchased quantity from purchase_items table
    $sql = "SELECT COALESCE(SUM(quantity), 0) as total_purchased 
            FROM purchase_items 
            WHERE product_id = $productId";
    $result = $connect->query($sql);
    $row = $result->fetch_array();
    $purchased = $row['total_purchased'];
    
    $response = array(
        'available' => $available,
        'purchased' => $purchased
    );
    
    echo json_encode($response);
} 