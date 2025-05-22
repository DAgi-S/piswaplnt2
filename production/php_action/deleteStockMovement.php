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

    try {
        // Start transaction
        $connect->begin_transaction();

        // Get movement details before deleting
        $sql = "SELECT product_id, quantity, movement_type FROM stock_movements WHERE movement_id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("i", $movementId);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows !== 1) {
            throw new Exception("Stock movement not found.");
        }

        $movementData = $result->fetch_assoc();

        // Update product stock based on movement type
        if($movementData['movement_type'] === 'in') {
            // If it was a stock in, subtract the quantity
            $updateSql = "UPDATE products SET quantity = quantity - ? WHERE product_id = ?";
        } else {
            // If it was a stock out, add back the quantity
            $updateSql = "UPDATE products SET quantity = quantity + ? WHERE product_id = ?";
        }

        $stmt = $connect->prepare($updateSql);
        $stmt->bind_param("di", $movementData['quantity'], $movementData['product_id']);
        
        if(!$stmt->execute()) {
            throw new Exception("Error updating stock: " . $stmt->error);
        }

        // Delete the movement record
        $sql = "DELETE FROM stock_movements WHERE movement_id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("i", $movementId);
        
        if(!$stmt->execute()) {
            throw new Exception("Error deleting stock movement: " . $stmt->error);
        }

        // Check if resulting stock is valid
        $checkSql = "SELECT quantity FROM products WHERE product_id = ?";
        $stmt = $connect->prepare($checkSql);
        $stmt->bind_param("i", $movementData['product_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $currentStock = $result->fetch_assoc()['quantity'];

        if($currentStock < 0) {
            throw new Exception("Cannot delete movement: Would result in negative stock");
        }

        $connect->commit();
        $valid['success'] = true;
        $valid['messages'] = "Stock movement successfully deleted";

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