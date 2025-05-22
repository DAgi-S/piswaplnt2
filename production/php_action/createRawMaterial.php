<?php
require_once 'core.php';

header('Content-Type: application/json');

$response = array(
    'success' => false,
    'messages' => '',
    'material' => null
);

// Validate input
if(empty($_POST['material_code']) || empty($_POST['name']) || 
   empty($_POST['category_id']) || empty($_POST['unit']) || 
   !isset($_POST['min_stock_level']) || !isset($_POST['cost_per_unit'])) {
    $response['messages'] = "Please fill in all required fields";
    echo json_encode($response);
    exit();
}

try {
    // Start transaction
    $connect->begin_transaction();
    
    // Check if material code already exists
    $sql = "SELECT id FROM raw_materials WHERE material_code = ? AND status != 'deleted'";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("s", $_POST['material_code']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $response['messages'] = "Material code already exists";
        echo json_encode($response);
        $stmt->close();
        exit();
    }
    $stmt->close();
    
    // Validate category exists and get category name
    $sql = "SELECT id, name FROM raw_material_categories WHERE id = ? AND status = 'active'";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $_POST['category_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows == 0) {
        $response['messages'] = "Selected category does not exist or is inactive";
        echo json_encode($response);
        $stmt->close();
        exit();
    }
    $categoryRow = $result->fetch_assoc();
    $stmt->close();
    
    // Insert new material
    $sql = "INSERT INTO raw_materials (
                material_code, 
                name, 
                category_id, 
                description, 
                unit, 
                min_stock_level, 
                cost_per_unit, 
                current_stock,
                status, 
                created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 0, 'active', ?)";
            
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
        $_SESSION['userId']
    );
    
    if($stmt->execute()) {
        $materialId = $stmt->insert_id;
        
        // Create initial stock movement record
        $sql = "INSERT INTO raw_material_movements (
                    material_id,
                    movement_type,
                    quantity,
                    reference_type,
                    notes,
                    created_by
                ) VALUES (?, 'in', 0, 'initial', 'Initial stock record', ?)";
                
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("ii", $materialId, $_SESSION['userId']);
        $stmt->execute();
        
        // Add entry to warehouse_stock
        $warehouse_id = $_POST['warehouse_id'];
        $sql_warehouse = "INSERT INTO warehouse_stock (warehouse_id, item_type, item_id, quantity) VALUES (?, 'raw_material', ?, 0.00)";
        $stmt = $connect->prepare($sql_warehouse);
        $stmt->bind_param("ii", $warehouse_id, $materialId);
        
        if($stmt->execute()) {
            $response['success'] = true;
            $response['messages'] = "Raw material created successfully!";
            $response['material'] = array(
                'id' => $materialId,
                'material_code' => $_POST['material_code'],
                'name' => $_POST['name'],
                'category' => $categoryRow['name'],
                'unit' => strtoupper($_POST['unit']),
                'min_stock_level' => number_format($_POST['min_stock_level'], 2),
                'cost_per_unit' => number_format($_POST['cost_per_unit'], 2)
            );
            
            // Commit transaction
            $connect->commit();
        } else {
            throw new Exception("Error adding to warehouse stock");
        }
        
    } else {
        throw new Exception("Error adding raw material");
    }
    
    $stmt->close();
    
} catch(Exception $e) {
    // Rollback transaction on error
    $connect->rollback();
    
    $response['messages'] = $e->getMessage();
}

$connect->close();
echo json_encode($response); 