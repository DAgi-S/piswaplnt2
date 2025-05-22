<?php
require_once 'core.php';

header('Content-Type: application/json');

$response = array();

try {
    // Get warehouse ID
    $id = isset($_POST['id']) ? $_POST['id'] : null;
    
    if(empty($id)) {
        throw new Exception("Warehouse ID is required");
    }
    
    // Fetch warehouse details
    $query = "SELECT id, code, name, type, location, description, status 
             FROM warehouses 
             WHERE id = ?";
             
    $stmt = $connect->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows === 0) {
        throw new Exception("Warehouse not found");
    }
    
    $warehouse = $result->fetch_assoc();
    
    $response['success'] = true;
    $response['data'] = $warehouse;
    
} catch(Exception $e) {
    $response['success'] = false;
    $response['messages'] = $e->getMessage();
}

$connect->close();
echo json_encode($response); 