<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'core.php';

// Clear any previous output
while (ob_get_level()) ob_end_clean();

header('Content-Type: application/json');

$response = array(
    'success' => false,
    'messages' => ''
);

try {
    // Validate input
    if (!isset($_POST['movement_id']) || empty($_POST['movement_id'])) {
        throw new Exception('Movement ID is required');
    }

    if (!isset($_POST['product_id']) || empty($_POST['product_id'])) {
        throw new Exception('Product is required');
    }

    if (!isset($_POST['quantity']) || !is_numeric($_POST['quantity']) || $_POST['quantity'] <= 0) {
        throw new Exception('Valid quantity is required');
    }

    // Get the original movement data
    $movementId = intval($_POST['movement_id']);
    $stmt = $connect->prepare("SELECT * FROM stock_movements WHERE movement_id = ?");
    $stmt->bind_param('i', $movementId);
    $stmt->execute();
    $result = $stmt->get_result();
    $originalMovement = $result->fetch_assoc();

    if (!$originalMovement) {
        throw new Exception('Stock movement not found');
    }

    // Start transaction
    $connect->begin_transaction();

    // Reverse the original movement's effect on stock
    $reverseQuantity = $originalMovement['movement_type'] == 'in' ? -$originalMovement['quantity'] : $originalMovement['quantity'];
    $updateStockSql = "UPDATE products SET quantity = quantity + ? WHERE product_id = ?";
    $stmt = $connect->prepare($updateStockSql);
    $stmt->bind_param('di', $reverseQuantity, $originalMovement['product_id']);
    $stmt->execute();

    // Apply the new movement
    $newQuantity = floatval($_POST['quantity']);
    $applyQuantity = $originalMovement['movement_type'] == 'in' ? $newQuantity : -$newQuantity;
    $stmt = $connect->prepare($updateStockSql);
    $stmt->bind_param('di', $applyQuantity, $_POST['product_id']);
    $stmt->execute();

    // Update the movement record
    $updateSql = "UPDATE stock_movements SET 
                    product_id = ?,
                    reference_type = ?,
                    reference_id = ?,
                    quantity = ?,
                    notes = ?
                  WHERE movement_id = ?";

    $stmt = $connect->prepare($updateSql);
    $stmt->bind_param(
        'issdsi',
        $_POST['product_id'],
        $_POST['reference_type'],
        $_POST['reference_id'],
        $newQuantity,
        $_POST['notes'],
        $movementId
    );
    $stmt->execute();

    // Commit transaction
    $connect->commit();

    $response['success'] = true;
    $response['messages'] = 'Stock movement updated successfully';

} catch (Exception $e) {
    // Rollback transaction on error
    if ($connect->connect_error === null) {
        $connect->rollback();
    }
    
    $response['messages'] = 'Error updating stock movement: ' . $e->getMessage();
    error_log("Error in updateStockMovement.php: " . $e->getMessage());
}

echo json_encode($response);
$connect->close(); 