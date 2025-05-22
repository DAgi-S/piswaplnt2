<?php
require_once 'core.php';

header('Content-Type: application/json');

$response = array();

try {
    // Get warehouse ID and new status
    $id = isset($_POST['id']) ? $_POST['id'] : null;
    $status = isset($_POST['status']) ? $_POST['status'] : null;
    
    if(empty($id)) {
        throw new Exception("Warehouse ID is required");
    }
    
    if(!in_array($status, array('active', 'inactive'))) {
        throw new Exception("Invalid status value");
    }
    
    // Start transaction
    $connect->begin_transaction();
    
    // Update warehouse status
    $query = "UPDATE warehouses SET status = ? WHERE id = ?";
    $stmt = $connect->prepare($query);
    $stmt->bind_param("si", $status, $id);
    
    if($stmt->execute()) {
        if($stmt->affected_rows > 0) {
            $connect->commit();
            $response['success'] = true;
            $response['messages'] = "Warehouse status updated successfully";
        } else {
            throw new Exception("Warehouse not found");
        }
    } else {
        throw new Exception("Error updating warehouse status");
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
