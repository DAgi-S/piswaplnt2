<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

require_once '../includes/db_connect.php';

// Set JSON header
header('Content-Type: application/json');

try {
    // Check if we have a valid database connection
    if (!$connect) {
        throw new Exception("Database connection failed");
    }

    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate product ID
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        throw new Exception('Valid product ID is required');
    }

    $productId = intval($_POST['id']);

    // Fetch product details with category and brand names
    $sql = "SELECT 
                p.*,
                pc.name as category_name,
                pb.name as brand_name
            FROM production_products p
            LEFT JOIN production_categories pc ON p.category_id = pc.id
            LEFT JOIN production_brands pb ON p.brand_id = pb.id
            WHERE p.id = ?";

    $stmt = $connect->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement");
    }

    if (!$stmt->execute([$productId])) {
        throw new Exception("Failed to execute query");
    }

    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception('Product not found');
    }

    // Format numeric values
    $product['current_stock'] = floatval($product['current_stock']);
    $product['min_stock_level'] = floatval($product['min_stock_level']);
    $product['production_cost'] = floatval($product['production_cost']);
    $product['selling_price'] = floatval($product['selling_price']);

    // Format response data
    $response = [
        'success' => true,
        'data' => [
            'id' => $product['id'],
            'product_code' => $product['product_code'],
            'name' => $product['name'],
            'category_id' => $product['category_id'],
            'brand_id' => $product['brand_id'],
            'unit' => $product['unit'] ?: 'pcs',
            'min_stock_level' => $product['min_stock_level'],
            'production_cost' => $product['production_cost'],
            'selling_price' => $product['selling_price'],
            'description' => $product['description'],
            'status' => $product['status'],
            'category_name' => $product['category_name'] ?: 'Uncategorized',
            'brand_name' => $product['brand_name'] ?: 'No Brand'
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error in fetchSingleProduct.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'messages' => $e->getMessage()
    ]);
} 