<?php
require_once 'core.php';

$output = array('data' => array());

if(isset($_POST['productionOrderId'])) {
    $productionOrderId = filter_var($_POST['productionOrderId'], FILTER_VALIDATE_INT);
    
    if(!$productionOrderId) {
        $output['error'] = 'Invalid production order ID';
        echo json_encode($output);
        exit();
    }
    
    $sql = "SELECT 
                pom.id,
                pom.material_id,
                pom.required_quantity,
                COALESCE(pom.consumed_quantity, 0) as consumed_quantity,
                COALESCE(pom.status, 'pending') as status,
                rm.name as material_name,
                rm.material_code,
                rm.current_stock,
                CASE 
                    WHEN pom.consumed_quantity >= pom.required_quantity THEN 'completed'
                    WHEN pom.consumed_quantity > 0 THEN 'in_progress'
                    WHEN rm.current_stock < pom.required_quantity THEN 'insufficient_stock'
                    ELSE 'pending'
                END as material_status,
                CASE 
                    WHEN rm.current_stock >= pom.required_quantity THEN 'Available'
                    WHEN rm.current_stock > 0 THEN 'Low Stock'
                    ELSE 'Out of Stock'
                END as stock_status
            FROM production_order_materials pom
            LEFT JOIN raw_materials rm ON pom.material_id = rm.id
            WHERE pom.production_order_id = ?
            ORDER BY rm.material_code ASC";
            
    $stmt = $connect->prepare($sql);
    
    if(!$stmt) {
        $output['error'] = 'Database error: ' . $connect->error;
        echo json_encode($output);
        exit();
    }
    
    $stmt->bind_param("i", $productionOrderId);
    
    if(!$stmt->execute()) {
        $output['error'] = 'Error executing query: ' . $stmt->error;
        echo json_encode($output);
        exit();
    }
    
    $result = $stmt->get_result();
    
    if($result->num_rows === 0) {
        $output['data'][] = array(
            'id' => 0,
            'name' => 'No materials assigned',
            'required_quantity' => '0.00',
            'consumed_quantity' => '0.00',
            'current_stock' => '0.00',
            'remaining_quantity' => '0.00',
            'status' => 'Pending',
            'stock_status' => 'N/A'
        );
    } else {
        while($row = $result->fetch_assoc()) {
            // Calculate remaining quantity needed
            $remaining = $row['required_quantity'] - $row['consumed_quantity'];
            
            $output['data'][] = array(
                'id' => $row['id'],
                'name' => $row['material_name'],
                'required_quantity' => number_format($row['required_quantity'], 2),
                'consumed_quantity' => number_format($row['consumed_quantity'], 2),
                'current_stock' => number_format($row['current_stock'], 2),
                'remaining_quantity' => number_format($remaining, 2),
                'status' => ucfirst($row['material_status']),
                'stock_status' => $row['stock_status']
            );
            
            // Update the material status in the database
            $updateStatusSql = "UPDATE production_order_materials 
                               SET status = ?, 
                                   updated_at = NOW() 
                               WHERE id = ?";
            $updateStmt = $connect->prepare($updateStatusSql);
            $updateStmt->bind_param('si', $row['material_status'], $row['id']);
            $updateStmt->execute();
            $updateStmt->close();
        }
    }
    
    $stmt->close();
}

$connect->close();

echo json_encode($output); 