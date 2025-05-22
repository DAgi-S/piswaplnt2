<?php
require_once 'core.php';

header('Content-Type: application/json');

$response = array(
    'success' => false,
    'messages' => ''
);

// Validate input
if(empty($_POST['materialId']) || empty($_POST['status'])) {
    $response['messages'] = "Material ID and status are required";
    echo json_encode($response);
    exit();
}

try {
    $materialId = $_POST['materialId'];
    $status = $_POST['status'];
    
    // Validate status value
    if(!in_array($status, ['active', 'inactive'])) {
        throw new Exception("Invalid status value");
    }
    
    // Start transaction
    $connect->begin_transaction();
    
    // Update raw material status
    $sql = "UPDATE raw_materials 
            SET status = ?,
                updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?";
            
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("si", $status, $materialId);
    
    if($stmt->execute()) {
        // Log the status change
        $sql = "INSERT INTO raw_material_movements 
                (material_id, movement_type, quantity, reference_type, notes, created_by) 
                VALUES (?, 'status_change', 0, 'status_update', ?, ?)";
                
        $notes = "Status changed to " . $status;
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("isi", $materialId, $notes, $_SESSION['userId']);
        
        if($stmt->execute()) {
            $connect->commit();
            $response['success'] = true;
            $response['messages'] = "Material status updated successfully";
        } else {
            throw new Exception("Error logging status change");
        }
    } else {
        throw new Exception("Error updating material status");
    }
    
} catch(Exception $e) {
    // Rollback transaction on error
    $connect->rollback();
    $response['messages'] = $e->getMessage();
}

echo json_encode($response); 