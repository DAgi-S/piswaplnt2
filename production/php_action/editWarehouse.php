<?php
require_once 'core.php';

header('Content-Type: application/json');

$response = array();

try {
    // Get warehouse details from POST
    $id = isset($_POST['id']) ? $_POST['id'] : null;
    $code = isset($_POST['code']) ? trim($_POST['code']) : null;
    $name = isset($_POST['name']) ? trim($_POST['name']) : null;
    $type = isset($_POST['type']) ? trim($_POST['type']) : null;
    $location = isset($_POST['location']) ? trim($_POST['location']) : null;
    $description = isset($_POST['description']) ? trim($_POST['description']) : null;
    
    // Validate required fields
    if(empty($id)) {
        throw new Exception("Warehouse ID is required");
    }
    if(empty($code)) {
        throw new Exception("Warehouse code is required");
    }
    if(empty($name)) {
        throw new Exception("Warehouse name is required");
    }
    if(!in_array($type, array('raw_material', 'finished_good', 'both'))) {
        throw new Exception("Invalid warehouse type");
    }
    
    // Start transaction
    $connect->begin_transaction();
    
    // Check if code exists for other warehouses
    $checkQuery = "SELECT id FROM warehouses WHERE code = ? AND id != ?";
    $checkStmt = $connect->prepare($checkQuery);
    $checkStmt->bind_param("si", $code, $id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if($checkResult->num_rows > 0) {
        throw new Exception("Warehouse code already exists");
    }
    
    // Update warehouse
    $query = "UPDATE warehouses SET 
        code = ?,
        name = ?,
        type = ?,
        location = ?,
        description = ?,
        updated_at = CURRENT_TIMESTAMP
        WHERE id = ?";
        
    $stmt = $connect->prepare($query);
    $stmt->bind_param("sssssi", $code, $name, $type, $location, $description, $id);
    
    if($stmt->execute()) {
        if($stmt->affected_rows > 0) {
            $connect->commit();
            $response['success'] = true;
            $response['messages'] = "Warehouse updated successfully";
        } else {
            throw new Exception("No changes made to warehouse");
        }
    } else {
        throw new Exception("Error updating warehouse");
    }
    
} catch(Exception $e) {
    if(isset($connect) && $connect->connect_errno == 0) {
        $connect->rollback();
    }
    $response['success'] = false;
    $response['messages'] = $e->getMessage();
}

$connect->close();
echo json_encode($response); 