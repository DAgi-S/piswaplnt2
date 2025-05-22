<?php
require_once 'core.php';

header('Content-Type: application/json');

$response = array(
    'success' => false,
    'messages' => ''
);

// Validate input
if(empty($_POST['editRawMaterialId']) || empty($_POST['material_code']) || 
   empty($_POST['name']) || empty($_POST['category_id']) || 
   empty($_POST['unit']) || !isset($_POST['min_stock_level']) || 
   !isset($_POST['cost_per_unit']) || empty($_POST['warehouse_id'])) {
    $response['messages'] = "Please fill in all required fields";
    echo json_encode($response);
    exit();
}

try {
    // Start transaction
    $connect->begin_transaction();
    
    // Update raw material
    $sql = "UPDATE raw_materials 
            SET material_code = ?,
                name = ?,
                category_id = ?,
                description = ?,
                unit = ?,
                min_stock_level = ?,
                cost_per_unit = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?";
            
    $stmt = $connect->prepare($sql);
    $stmt->bind_param(
        "ssissddi",
        $_POST['material_code'],
        $_POST['name'],
        $_POST['category_id'],
        $_POST['description'],
        $_POST['unit'],
        $_POST['min_stock_level'],
        $_POST['cost_per_unit'],
        $_POST['editRawMaterialId']
    );
    
    if($stmt->execute()) {
        // Update warehouse_stock
        $sql = "UPDATE warehouse_stock 
                SET warehouse_id = ?
                WHERE item_id = ? AND item_type = 'raw_material'";
                
        $stmt = $connect->prepare($sql);
        $stmt->bind_param(
            "ii",
            $_POST['warehouse_id'],
            $_POST['editRawMaterialId']
        );
        
        if($stmt->execute()) {
            $connect->commit();
            $response['success'] = true;
            $response['messages'] = "Raw material updated successfully";
        } else {
            throw new Exception("Error updating warehouse stock");
        }
    } else {
        throw new Exception("Error updating raw material");
    }
    
} catch(Exception $e) {
    // Rollback transaction on error
    $connect->rollback();
    $response['messages'] = $e->getMessage();
}

echo json_encode($response); 