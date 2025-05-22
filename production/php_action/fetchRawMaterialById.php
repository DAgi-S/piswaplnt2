<?php
require_once 'core.php';
require_once 'db_connect.php';

header('Content-Type: application/json');

$response = array(
    'success' => false,
    'messages' => ''
);

// Validate input
if(empty($_POST['materialId'])) {
    $response['messages'] = "Material ID is required";
    echo json_encode($response);
    exit();
}

try {
    $materialId = $_POST['materialId'];
    
    // Get raw material details including warehouse
    $sql = "SELECT 
                rm.*,
                ws.warehouse_id,
                rmc.name as category_name
            FROM raw_materials rm
            LEFT JOIN warehouse_stock ws ON ws.item_id = rm.id AND ws.item_type = 'raw_material'
            LEFT JOIN raw_material_categories rmc ON rm.category_id = rmc.id
            WHERE rm.id = ?";
            
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $materialId);
    
    if($stmt->execute()) {
        $result = $stmt->get_result();
        if($row = $result->fetch_assoc()) {
            $response = array_merge($response, $row);
            $response['success'] = true;
        } else {
            $response['messages'] = "Material not found";
        }
    } else {
        $response['messages'] = "Error fetching material details";
    }
    
} catch(Exception $e) {
    error_log("Error in fetchRawMaterialById.php: " . $e->getMessage());
    $response['messages'] = "An error occurred while fetching the material details";
}

$connect->close();
echo json_encode($response); 