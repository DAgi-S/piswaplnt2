<?php
require_once 'core.php';
require_once 'db_connect.php';

header('Content-Type: application/json');

$response = array(
    'success' => false,
    'available' => 0,
    'message' => ''
);

if (!isset($_POST['product_id']) || empty($_POST['product_id'])) {
    $response['message'] = 'Product ID is required';
    echo json_encode($response);
    exit();
}

try {
    $productId = intval($_POST['product_id']);
    
    // Calculate current stock using a transaction for consistency
    $connect->begin_transaction();
    
    $sql = "SELECT 
                COALESCE(SUM(
                    CASE 
                        WHEN movement_type = 'in' THEN quantity 
                        ELSE -quantity 
                    END
                ), 0) as current_stock
            FROM stock_movements 
            WHERE product_id = ?";
            
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $connect->commit();
    
    $response['success'] = true;
    $response['available'] = floatval($row['current_stock']);
    $response['message'] = 'Stock availability checked successfully';
    
} catch (Exception $e) {
    $connect->rollback();
    $response['message'] = 'Error checking stock availability: ' . $e->getMessage();
} finally {
    echo json_encode($response);
} 