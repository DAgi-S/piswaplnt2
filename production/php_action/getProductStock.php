<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once 'core.php';
require_once 'db_connect.php';

// Clear any previous output
while (ob_get_level()) ob_end_clean();

header('Content-Type: application/json');

$response = array('success' => false, 'stock' => 0, 'messages' => '');

if(isset($_POST['product_id']) && !empty($_POST['product_id'])) {
    $productId = mysqli_real_escape_string($connect, $_POST['product_id']);

    try {
        // Check if product exists and is active
        $productSql = "SELECT status FROM products WHERE product_id = ?";
        $stmt = $connect->prepare($productSql);
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows !== 1) {
            throw new Exception("Invalid product selected.");
        }

        $productData = $result->fetch_assoc();
        if($productData['status'] != 1) {
            throw new Exception("Selected product is not active.");
        }

        // Get current stock
        $stockSql = "SELECT 
                        COALESCE(SUM(CASE 
                            WHEN movement_type = 'in' THEN quantity 
                            WHEN movement_type = 'out' THEN -quantity 
                        END), 0) as current_stock 
                    FROM stock_movements 
                    WHERE product_id = ?";
        
        $stmt = $connect->prepare($stockSql);
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $stockResult = $stmt->get_result()->fetch_assoc();
        
        $response['success'] = true;
        $response['stock'] = number_format($stockResult['current_stock'], 2);

    } catch (Exception $e) {
        $response['success'] = false;
        $response['messages'] = $e->getMessage();
    }

} else {
    $response['success'] = false;
    $response['messages'] = "Invalid product ID";
}

echo json_encode($response);
?> 