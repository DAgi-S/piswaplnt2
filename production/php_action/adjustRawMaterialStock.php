<?php
require_once 'core.php';

$response = array(
    'success' => false,
    'messages' => ''
);

if(empty($_POST['material_id']) || !isset($_POST['quantity']) || empty($_POST['movement_type'])) {
    $response['messages'] = "Required fields are missing";
    echo json_encode($response);
    exit();
}

try {
    // Start transaction
    $connect->begin_transaction();

    $materialId = $_POST['material_id'];
    $quantity = $_POST['quantity'];
    $movementType = $_POST['movement_type'];
    $referenceType = $_POST['reference_type'];
    $notes = $_POST['notes'];
    $warehouseId = $_POST['warehouse_id'];

    // Get current stock from raw_materials
    $sql = "SELECT current_stock FROM raw_materials WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $materialId);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentStock = $result->fetch_assoc()['current_stock'];

    // Calculate new stock based on movement type
    $newStock = $currentStock;
    if($movementType == 'in') {
        $newStock += $quantity;
    } else if($movementType == 'out') {
        if($currentStock < $quantity) {
            throw new Exception("Insufficient stock");
        }
        $newStock -= $quantity;
    } else {
        $newStock = $quantity; // For direct adjustment
    }

    // Update raw_materials stock
    $sql = "UPDATE raw_materials SET current_stock = ? WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("di", $newStock, $materialId);
    if(!$stmt->execute()) {
        throw new Exception("Error updating raw material stock");
    }

    // Record in raw_material_movements
    $sql = "INSERT INTO raw_material_movements (material_id, movement_type, quantity, reference_type, notes, created_by) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("isdssi", $materialId, $movementType, $quantity, $referenceType, $notes, $_SESSION['userId']);
    if(!$stmt->execute()) {
        throw new Exception("Error recording movement");
    }

    // Update warehouse_stock
    $sql = "UPDATE warehouse_stock 
            SET quantity = ? 
            WHERE warehouse_id = ? AND item_type = 'raw_material' AND item_id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("dii", $newStock, $warehouseId, $materialId);
    if(!$stmt->execute()) {
        throw new Exception("Error updating warehouse stock");
    }

    // Record in warehouse_stock_movements
    $sql = "INSERT INTO warehouse_stock_movements 
            (warehouse_id, item_type, item_id, movement_type, quantity, reference_type, notes, created_by) 
            VALUES (?, 'raw_material', ?, ?, ?, ?, ?, ?)";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("iisdssi", $warehouseId, $materialId, $movementType, $quantity, $referenceType, $notes, $_SESSION['userId']);
    if(!$stmt->execute()) {
        throw new Exception("Error recording warehouse movement");
    }

    // If all operations successful, commit transaction
    $connect->commit();
    
    $response['success'] = true;
    $response['messages'] = "Stock adjusted successfully";

} catch(Exception $e) {
    // Rollback transaction on error
    $connect->rollback();
    $response['messages'] = $e->getMessage();
}

echo json_encode($response); 