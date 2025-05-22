<?php
require_once 'db_connect.php';
require_once 'core.php';

// Prepare response array
$response = array(
    'success' => false,
    'messages' => ''
);

if($_POST) {
    $userId = $_SESSION['userId'];
    $orderId = filter_var($_POST['orderId'], FILTER_VALIDATE_INT);
    $newStatus = mysqli_real_escape_string($connect, $_POST['status']);
    
    // Validate inputs
    if(!$orderId) {
        $response['messages'] = 'Invalid order ID';
        echo json_encode($response);
        exit();
    }

    // Validate status transition
    $validStatuses = array('draft', 'confirmed', 'in_progress', 'completed', 'cancelled');
    if(!in_array($newStatus, $validStatuses)) {
        $response['messages'] = 'Invalid status';
        echo json_encode($response);
        exit();
    }

    try {
        // Start transaction
        $connect->begin_transaction();

        // Get current order status and details
        $orderSql = "SELECT po.*, GROUP_CONCAT(pom.product_id) as material_ids, 
                     GROUP_CONCAT(pom.required_quantity) as required_quantities,
                     GROUP_CONCAT(pom.consumed_quantity) as consumed_quantities
                     FROM production_orders po
                     LEFT JOIN production_order_materials pom ON po.production_order_id = pom.production_order_id
                     WHERE po.production_order_id = ?
                     GROUP BY po.production_order_id";
        
        $orderStmt = $connect->prepare($orderSql);
        $orderStmt->bind_param('i', $orderId);
        $orderStmt->execute();
        $result = $orderStmt->get_result();
        
        if($result->num_rows === 0) {
            throw new Exception("Production order not found");
        }
        
        $order = $result->fetch_assoc();
        $currentStatus = $order['status'];

        // Validate status transition
        $validTransitions = array(
            'draft' => array('confirmed', 'cancelled'),
            'confirmed' => array('in_progress', 'cancelled'),
            'in_progress' => array('completed', 'cancelled'),
            'completed' => array(),
            'cancelled' => array()
        );

        if(!in_array($newStatus, $validTransitions[$currentStatus])) {
            throw new Exception("Invalid status transition from {$currentStatus} to {$newStatus}");
        }

        // Handle status-specific actions
        switch($newStatus) {
            case 'confirmed':
                // No additional action needed, materials are already reserved
                break;

            case 'in_progress':
                // Update material status to partially_consumed
                $updateMaterialSql = "UPDATE production_order_materials 
                                    SET status = 'partially_consumed' 
                                    WHERE production_order_id = ?";
                $materialStmt = $connect->prepare($updateMaterialSql);
                $materialStmt->bind_param('i', $orderId);
                if(!$materialStmt->execute()) {
                    throw new Exception("Error updating material status");
                }
                break;

            case 'completed':
                // Validate all materials are consumed
                $materialIds = explode(',', $order['material_ids']);
                $requiredQtys = explode(',', $order['required_quantities']);
                $consumedQtys = explode(',', $order['consumed_quantities']);

                for($i = 0; $i < count($materialIds); $i++) {
                    if(floatval($consumedQtys[$i]) < floatval($requiredQtys[$i])) {
                        throw new Exception("Not all materials have been consumed");
                    }
                }

                // Update material status to fully_consumed
                $updateMaterialSql = "UPDATE production_order_materials 
                                    SET status = 'fully_consumed' 
                                    WHERE production_order_id = ?";
                $materialStmt = $connect->prepare($updateMaterialSql);
                $materialStmt->bind_param('i', $orderId);
                if(!$materialStmt->execute()) {
                    throw new Exception("Error updating material status");
                }

                // Set completed quantity to target quantity
                $updateOrderSql = "UPDATE production_orders 
                                 SET completed_quantity = target_quantity 
                                 WHERE production_order_id = ?";
                $updateOrderStmt = $connect->prepare($updateOrderSql);
                $updateOrderStmt->bind_param('i', $orderId);
                if(!$updateOrderStmt->execute()) {
                    throw new Exception("Error updating completed quantity");
                }
                break;

            case 'cancelled':
                // Return reserved materials to stock if not consumed
                if($currentStatus !== 'completed') {
                    $materialIds = explode(',', $order['material_ids']);
                    $requiredQtys = explode(',', $order['required_quantities']);
                    $consumedQtys = explode(',', $order['consumed_quantities']);

                    for($i = 0; $i < count($materialIds); $i++) {
                        $remainingQty = floatval($requiredQtys[$i]) - floatval($consumedQtys[$i]);
                        if($remainingQty > 0) {
                            // Log return to stock
                            $movementSql = "INSERT INTO stock_movements (
                                             product_id, reference_type, reference_id,
                                             quantity, movement_type, notes, created_by
                                          ) VALUES (?, 'production_order', ?, ?, 'in', ?, ?)";
                            $movementNote = "Returned from cancelled Production Order: " . $order['order_number'];
                            $movementStmt = $connect->prepare($movementSql);
                            $movementStmt->bind_param('iidsi', 
                                $materialIds[$i], $orderId, $remainingQty, $movementNote, $userId
                            );
                            if(!$movementStmt->execute()) {
                                throw new Exception("Error logging stock return");
                            }
                        }
                    }
                }
                break;
        }

        // Update order status
        $updateSql = "UPDATE production_orders SET status = ? WHERE production_order_id = ?";
        $updateStmt = $connect->prepare($updateSql);
        $updateStmt->bind_param('si', $newStatus, $orderId);
        
        if(!$updateStmt->execute()) {
            throw new Exception("Error updating order status");
        }

        // Commit transaction
        $connect->commit();
        
        $response['success'] = true;
        $response['messages'] = 'Production order status updated successfully';
        
    } catch(Exception $e) {
        // Rollback transaction on error
        $connect->rollback();
        $response['messages'] = $e->getMessage();
    }
}

// Close database connection
$connect->close();

echo json_encode($response); 