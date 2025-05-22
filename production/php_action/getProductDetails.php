<?php
require_once 'core.php';
require_once 'db_connect.php';

// Set header type to JSON
header('Content-Type: application/json');

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get POST data
$productId = isset($_POST['id']) ? intval($_POST['id']) : 0;

// Validate input
if (!$productId) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit();
}

try {
    // Prepare and execute the select query
    $stmt = $connect->prepare("
        SELECT 
            pp.*,
            pc.name as category_name,
            pb.name as brand_name
        FROM production_products pp
        LEFT JOIN production_categories pc ON pp.category_id = pc.id
        LEFT JOIN production_brands pb ON pp.brand_id = pb.id
        WHERE pp.id = ?
    ");
    
    $stmt->bind_param('i', $productId);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            echo json_encode([
                'success' => true,
                'data' => [
                    'id' => $row['id'],
                    'product_code' => $row['product_code'],
                    'name' => $row['name'],
                    'category_id' => $row['category_id'],
                    'brand_id' => $row['brand_id'],
                    'unit' => $row['unit'],
                    'current_stock' => $row['current_stock'],
                    'min_stock_level' => $row['min_stock_level'],
                    'production_cost' => $row['production_cost'],
                    'selling_price' => $row['selling_price'],
                    'description' => $row['description'],
                    'status' => $row['status'],
                    'category_name' => $row['category_name'],
                    'brand_name' => $row['brand_name']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
        }
    } else {
        throw new Exception("Error fetching product details: " . $stmt->error);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Error in getProductDetails.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error fetching product details']);
}

$connect->close(); 