<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once 'core.php';
require_once 'db_connect.php';

// Clear any previous output
while (ob_get_level()) ob_end_clean();

header('Content-Type: application/json');

$valid['success'] = array('success' => false, 'messages' => array());

if($_POST) {
    $movementId = mysqli_real_escape_string($connect, $_POST['movement_id']);
    $productId = mysqli_real_escape_string($connect, $_POST['product_id']);
    $referenceType = mysqli_real_escape_string($connect, $_POST['reference_type']);
    $referenceId = mysqli_real_escape_string($connect, $_POST['reference_id']);
    $quantity = mysqli_real_escape_string($connect, $_POST['quantity']);
    $notes = mysqli_real_escape_string($connect, isset($_POST['notes']) ? $_POST['notes'] : '');
    $userId = $_SESSION['userId'];

    try {
        // Start transaction
        $connect->begin_transaction();

        // Get original movement details
        $originalSql = "SELECT product_id, quantity, movement_type FROM stock_movements WHERE movement_id = ?";
        $stmt = $connect->prepare($originalSql);
        $stmt->bind_param("i", $movementId);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows !== 1) {
            throw new Exception("Stock movement not found.");
        }

        $originalData = $result->fetch_assoc();

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

        // Revert original movement's effect on stock
        if($originalData['movement_type'] === 'in') {
            // Subtract the original quantity
            $updateSql = "UPDATE products SET quantity = quantity - ? WHERE product_id = ?";
        } else {
            // Add back the original quantity
            $updateSql = "UPDATE products SET quantity = quantity + ? WHERE product_id = ?";
        }

        $stmt = $connect->prepare($updateSql);
        $stmt->bind_param("di", $originalData['quantity'], $originalData['product_id']);
        
        if(!$stmt->execute()) {
            throw new Exception("Error reverting original stock: " . $stmt->error);
        }

        // Update the movement record
        $sql = "UPDATE stock_movements 
                SET product_id = ?, 
                    reference_type = ?, 
                    reference_id = ?, 
                    quantity = ?, 
                    notes = ? 
                WHERE movement_id = ?";
        
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("issdsi", $productId, $referenceType, $referenceId, $quantity, $notes, $movementId);
        
        if(!$stmt->execute()) {
            throw new Exception("Error updating stock movement: " . $stmt->error);
        }

        // Apply the new quantity to product stock
        if($originalData['movement_type'] === 'in') {
            // Add the new quantity
            $updateSql = "UPDATE products SET quantity = quantity + ? WHERE product_id = ?";
        } else {
            // Subtract the new quantity
            $updateSql = "UPDATE products SET quantity = quantity - ? WHERE product_id = ?";
        }

        $stmt = $connect->prepare($updateSql);
        $stmt->bind_param("di", $quantity, $productId);
        
        if(!$stmt->execute()) {
            throw new Exception("Error updating stock: " . $stmt->error);
        }

        // Check if resulting stock is valid
        $checkSql = "SELECT quantity FROM products WHERE product_id = ?";
        $stmt = $connect->prepare($checkSql);
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $currentStock = $result->fetch_assoc()['quantity'];

        if($currentStock < 0) {
            throw new Exception("Cannot update movement: Would result in negative stock");
        }

        $connect->commit();
        $valid['success'] = true;
        $valid['messages'] = "Stock movement successfully updated";

    } catch (Exception $e) {
        $connect->rollback();
        $valid['success'] = false;
        $valid['messages'] = $e->getMessage();
    }

} else {
    $valid['success'] = false;
    $valid['messages'] = "Invalid request";
}

echo json_encode($valid); 