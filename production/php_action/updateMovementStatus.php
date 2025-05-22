<?php
require_once '../includes/db_connect.php';

// Set proper content type
header('Content-Type: application/json');

if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(array(
        'success' => false,
        'message' => 'Movement ID is required'
    ));
    exit();
}

if (!isset($_POST['status']) || empty($_POST['status']) || !in_array($_POST['status'], ['completed', 'cancelled'])) {
    echo json_encode(array(
        'success' => false,
        'message' => 'Invalid status'
    ));
    exit();
}

try {
    $connect->beginTransaction();

    // First get the movement details
    $stmt = $connect->prepare("SELECT * FROM inventory_movement_log WHERE id = ?");
    $stmt->execute(array($_POST['id']));
    $movement = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$movement) {
        throw new Exception('Movement not found');
    }

    if ($movement['status'] !== 'pending') {
        throw new Exception('Only pending movements can be updated');
    }

    // Update the movement status
    $stmt = $connect->prepare("UPDATE inventory_movement_log SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute(array($_POST['status'], $_POST['id']));

    // If completing the movement, update the stock levels
    if ($_POST['status'] === 'completed') {
        // Update source stock if applicable
        if ($movement['source_type'] === 'warehouse') {
            $updateSource = $connect->prepare("
                UPDATE raw_materials 
                SET current_stock = current_stock - ?, 
                    updated_at = NOW() 
                WHERE id = ?
            ");
            $updateSource->execute(array($movement['quantity'], $movement['item_id']));
        }

        // Update destination stock if applicable
        if ($movement['destination_type'] === 'warehouse') {
            $updateDest = $connect->prepare("
                UPDATE raw_materials 
                SET current_stock = current_stock + ?, 
                    updated_at = NOW() 
                WHERE id = ?
            ");
            $updateDest->execute(array($movement['quantity'], $movement['item_id']));
        }
    }

    $connect->commit();

    echo json_encode(array(
        'success' => true,
        'message' => 'Movement status updated successfully'
    ));

} catch (Exception $e) {
    $connect->rollBack();
    error_log('Error updating movement status: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => $e->getMessage()
    ));
} 