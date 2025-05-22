<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'core.php';

// Set proper headers for JSON response
header('Content-Type: application/json');

try {
    if (!$connect) {
        throw new Exception("Database connection failed");
    }

    // Debug connection info
    error_log("Database connection status: " . ($connect ? "Connected" : "Not connected"));

    $sql = "SELECT p.*, b.name as brand_name, c.name as category_name,
            COALESCE((SELECT SUM(pi.quantity) FROM purchase_items pi WHERE pi.product_id = p.product_id), 0) as total_purchased
            FROM products p 
            LEFT JOIN brands b ON p.brand_id = b.brand_id 
            LEFT JOIN categories c ON p.category_id = c.category_id 
            WHERE p.status != 2";

    error_log("Executing SQL query: " . $sql);
    $result = $connect->query($sql);

    if (!$result) {
        throw new Exception("Database error: " . $connect->error);
    }

    $output = array('data' => array());

    while ($row = $result->fetch_assoc()) {
        $productId = $row['product_id'];
        
        // Handle product image with default
        $imageHtml = '<img src="assets/images/photo_default.png" class="img-thumbnail" style="width:50px;height:50px;">';
        if (!empty($row['image'])) {
            $imagePath = $row['image'];
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $imagePath)) {
                $imageHtml = '<img src="'.$imagePath.'" class="img-thumbnail" style="width:50px;height:50px;">';
            }
        }
        
        // Clean and format the data
        $row['price'] = number_format((float)$row['price'], 2);
        $row['quantity'] = intval($row['quantity']);
        $row['status'] = intval($row['status']);
        $row['total_purchased'] = intval($row['total_purchased']);
        
        $output['data'][] = array(
            'product_id' => $productId,
            'image' => $imageHtml,
            'name' => htmlspecialchars($row['name']),
            'price' => $row['price'],
            'quantity' => $row['quantity'],
            'brand_name' => htmlspecialchars($row['brand_name'] ?? ''),
            'category_name' => htmlspecialchars($row['category_name'] ?? ''),
            'status' => $row['status'],
            'total_purchased' => $row['total_purchased']
        );
    }

    error_log("Found " . count($output['data']) . " products");
    echo json_encode($output);

} catch (Exception $e) {
    error_log("Error in fetchProduct.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode(array(
        'error' => true,
        'message' => 'An error occurred while fetching products: ' . $e->getMessage()
    ));
}

if (isset($connect)) {
    $connect->close();
} 