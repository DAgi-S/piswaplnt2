<?php
require_once 'core.php';
require_once 'db_connect.php';
require_once 'middleware.php';

header('Content-Type: application/json');

if (!hasPermission('create_quotations')) {
    echo json_encode([
        'success' => false,
        'messages' => 'Access denied'
    ]);
    exit();
}

$response = array();

try {
    if (!$connect) {
        throw new Exception("Database connection failed");
    }

    // Debug connection
    error_log("Database connection successful");

    // Fetch all active products
    $sql = "SELECT 
                p.product_id,
                p.product_code,
                p.name,
                p.selling_price,
                p.unit,
                CONCAT(p.name, ' (', p.product_code, ')') as display_name
            FROM products p 
            WHERE p.status = 'active' 
            ORDER BY p.name ASC";
            
    $result = $connect->query($sql);
    
    if($result) {
        $data = array();
        while($row = $result->fetch_assoc()) {
            // Debug data
            error_log("Processing product: " . json_encode($row));
            
            $data[] = array(
                'product_id' => $row['product_id'],
                'product_code' => $row['product_code'],
                'name' => $row['display_name'],
                'selling_price' => $row['selling_price'],
                'unit' => $row['unit']
            );
            
            // Debug each product data
            error_log("Added product data: " . json_encode(end($data)));
        }
        
        $response['success'] = true;
        $response['data'] = $data;
        
        // Debug final response
        error_log("Final response data: " . json_encode($response));
    } else {
        throw new Exception("Error fetching products: " . $connect->error);
    }
    
} catch(Exception $e) {
    error_log("Products fetch error: " . $e->getMessage());
    $response['success'] = false;
    $response['messages'] = $e->getMessage();
}

echo json_encode($response); 