<?php
require_once 'core.php';

$response = array(
    'success' => false,
    'warehouse_id' => null,
    'messages' => ''
);

if(empty($_POST['materialId'])) {
    $response['messages'] = "Material ID is required";
    echo json_encode($response);
    exit();
}

try {
    $materialId = $_POST['materialId'];
    
    // Get the warehouse where this material is stored
    $sql = "SELECT warehouse_id 
            FROM warehouse_stock 
            WHERE item_type = 'raw_material' 
            AND item_id = ? 
            LIMIT 1";
            
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $materialId);
    
    if($stmt->execute()) {
        $result = $stmt->get_result();
        if($row = $result->fetch_assoc()) {
            $response['success'] = true;
            $response['warehouse_id'] = $row['warehouse_id'];
        }
    }
    
} catch(Exception $e) {
    $response['messages'] = $e->getMessage();
}

echo json_encode($response); 