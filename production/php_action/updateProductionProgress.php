<?php
require_once 'core.php';
require_once 'classes/RawMaterialManager.php';

// Prepare response array
$response = array(
    'success' => false,
    'messages' => ''
);

if($_POST) {
    // Get the production order ID first so we can use it in other queries
    $productionOrderId = filter_var($_POST['production_order_id'], FILTER_VALIDATE_INT);
    $quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_FLOAT);
    $notes = mysqli_real_escape_string($connect, $_POST['notes']);

    // Validate inputs
    if(!$productionOrderId || $quantity <= 0) {
        $response['messages'] = 'Invalid input parameters';
        echo json_encode($response);
        exit();
    }
    
    // Now get user ID using our new helper function
    $userId = getCurrentUserId();
    error_log("Using user ID from helper: " . $userId);
    
    try {
        // Start transaction
        $connect->begin_transaction();

        // Get order details
        $orderSql = "SELECT target_quantity, completed_quantity, order_number 
                    FROM production_orders 
                    WHERE id = ?";
        $orderStmt = $connect->prepare($orderSql);
        $orderStmt->bind_param("i", $productionOrderId);
        $orderStmt->execute();
        $orderResult = $orderStmt->get_result();
        $order = $orderResult->fetch_assoc();
        $orderStmt->close();

        // Calculate new total progress
        $newTotalProgress = $order['completed_quantity'] + $quantity;
        if($newTotalProgress > $order['target_quantity']) {
            throw new Exception("Progress cannot exceed target quantity");
        }

        // Calculate progress percentage
        $progressPercentage = $quantity / $order['target_quantity'];

        // Get materials for this order
        $materialsSql = "SELECT 
                            pom.material_id,
                            pom.required_quantity,
                            pom.consumed_quantity,
                            rm.name as material_name,
                            rm.current_stock
                        FROM production_order_materials pom
                        JOIN raw_materials rm ON pom.material_id = rm.id
                        WHERE pom.production_order_id = ?";
        
        $materialsStmt = $connect->prepare($materialsSql);
        $materialsStmt->bind_param("i", $productionOrderId);
        $materialsStmt->execute();
        $materialsResult = $materialsStmt->get_result();
        
        // Initialize RawMaterialManager
        $rawMaterialManager = new RawMaterialManager($connect);
        
        // Process each material
        while($material = $materialsResult->fetch_assoc()) {
            $materialToConsume = floatval($material['required_quantity']) * $progressPercentage;
            
            // Consume material using RawMaterialManager
            $consumeResult = $rawMaterialManager->consumeMaterial(
                $productionOrderId,
                $material['material_id'],
                $materialToConsume,
                "Consumed in Production Order #" . $order['order_number'],
                $userId
            );
            
            if(!$consumeResult['success']) {
                throw new Exception("Error consuming material " . $material['material_name'] . ": " . $consumeResult['message']);
            }
        }
        $materialsStmt->close();

        // Record progress
        $progressSql = "INSERT INTO production_progress 
                       (production_order_id, quantity, notes, created_at, created_by) 
                       VALUES (?, ?, ?, CURRENT_TIMESTAMP, ?)";
        
        $progressStmt = $connect->prepare($progressSql);
        $progressStmt->bind_param('idsi', $productionOrderId, $quantity, $notes, $userId);
        
        if(!$progressStmt->execute()) {
            throw new Exception("Error recording progress");
        }
        $progressStmt->close();

        // Update order completed quantity
        $updateOrderSql = "UPDATE production_orders 
                         SET completed_quantity = ?,
                             updated_at = CURRENT_TIMESTAMP
                         WHERE id = ?";
        
        $updateOrderStmt = $connect->prepare($updateOrderSql);
        $updateOrderStmt->bind_param('di', $newTotalProgress, $productionOrderId);
        
        if(!$updateOrderStmt->execute()) {
            throw new Exception("Error updating order progress");
        }
        $updateOrderStmt->close();

        // Commit transaction
        $connect->commit();
        
        $response = array(
            'success' => true,
            'messages' => 'Progress updated successfully',
            'data' => array(
                'new_progress' => $newTotalProgress,
                'target_quantity' => $order['target_quantity'],
                'progress_percentage' => ($newTotalProgress / $order['target_quantity']) * 100
            )
        );
        
    } catch(Exception $e) {
        // Rollback transaction on error
        $connect->rollback();
        $response = array(
            'success' => false,
            'messages' => $e->getMessage(),
            'data' => null
        );
    }
}

// Set proper content type header
header('Content-Type: application/json');
echo json_encode($response);
$connect->close(); 