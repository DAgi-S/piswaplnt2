<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'core.php';

// Set proper headers for JSON response
header('Content-Type: application/json');

$response = array(
    'success' => false,
    'messages' => ''
);

if(isset($_POST['productId'])) {
    try {
        $productId = intval($_POST['productId']);

        // Start transaction
        $connect->begin_transaction();

        // First, get the product image path
        $sql = "SELECT product_image FROM products WHERE product_id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();

        // Delete the product
        $sql = "DELETE FROM products WHERE product_id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("i", $productId);

        if($stmt->execute()) {
            // If product had an image, delete it from the filesystem
            if(!empty($product['product_image'])) {
                $imagePath = '../' . $product['product_image'];
                if(file_exists($imagePath)) {
                    @unlink($imagePath);
                }
            }

            $connect->commit();
            
            $response['success'] = true;
            $response['messages'] = "Product successfully removed";
        } else {
            throw new Exception("Error deleting product: " . $stmt->error);
        }

    } catch(Exception $e) {
        // Rollback transaction on error
        $connect->rollback();
        
        $response['success'] = false;
        $response['messages'] = $e->getMessage();
        
        error_log("Error in removeProduct.php: " . $e->getMessage());
    }
} else {
    $response['success'] = false;
    $response['messages'] = "Invalid request parameters";
}

$connect->close();

echo json_encode($response); 