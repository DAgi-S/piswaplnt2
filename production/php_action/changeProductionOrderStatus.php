<?php
require_once 'core.php';
require_once 'db_connect.php';
require_once 'telegram_helper.php';

// Prepare response array
$response = array(
    'success' => false,
    'messages' => '',
    'data' => null
);

if($_POST) {
    $userId = $_SESSION['userId'];
    $orderId = filter_var($_POST['orderId'], FILTER_VALIDATE_INT);
    $newStatus = str_replace(' ', '', strtolower(trim($_POST['status']))); // Remove spaces and normalize
    
    // Debug logging
    error_log("Received status change request - OrderID: $orderId, New Status: $newStatus");
    
    try {
        // Validate inputs
        if(!$orderId) {
            throw new Exception('Invalid order ID');
        }

        // Validate status value
        $validStatuses = array('draft', 'confirmed', 'inprogress', 'completed', 'cancelled');
        if(!in_array($newStatus, $validStatuses)) {
            throw new Exception('Invalid status value: ' . $newStatus);
        }

        // Start transaction
        $connect->begin_transaction();

        // Get current order status and details
        $orderSql = "SELECT 
                     po.*,
                     pp.name as product_name,
                     COALESCE(u.username, 'System') as created_by_name,
                     COALESCE((SELECT SUM(quantity) FROM production_progress 
                              WHERE production_order_id = po.id), 0) as total_progress,
                     COALESCE((SELECT SUM(consumed_quantity) FROM production_order_materials 
                              WHERE production_order_id = po.id), 0) as total_consumed
                     FROM production_orders po
                     LEFT JOIN production_products pp ON po.product_id = pp.id
                     LEFT JOIN users u ON po.created_by = u.user_id
                     WHERE po.id = ?";
        
        $orderStmt = $connect->prepare($orderSql);
        if (!$orderStmt) {
            throw new Exception("Database error: " . $connect->error);
        }

        $orderStmt->bind_param('i', $orderId);
        if (!$orderStmt->execute()) {
            throw new Exception("Error executing query: " . $orderStmt->error);
        }

        $result = $orderStmt->get_result();
        if($result->num_rows === 0) {
            throw new Exception("Production order not found");
        }
        
        $order = $result->fetch_assoc();
        $currentStatus = str_replace(' ', '', strtolower(trim($order['status']))); // Normalize current status
        
        error_log("Current Status: $currentStatus, Attempting to change to: $newStatus");

        // Validate status transition
        $validTransitions = array(
            'draft' => array('confirmed', 'cancelled'),
            'confirmed' => array('inprogress', 'cancelled'),
            'inprogress' => array('completed', 'cancelled'),
            'completed' => array('cancelled'),
            'cancelled' => array()
        );

        if(!isset($validTransitions[$currentStatus])) {
            throw new Exception("Invalid current status: $currentStatus");
        }

        if(!in_array($newStatus, $validTransitions[$currentStatus])) {
            throw new Exception("Cannot change status from '$currentStatus' to '$newStatus'. Valid transitions are: " . implode(', ', $validTransitions[$currentStatus]));
        }

        // Additional validation for specific transitions
        if($newStatus === 'confirmed') {
            // When confirming order, check material stock availability
            $materialsSql = "SELECT 
                           m.id as material_id,
                           m.name,
                           m.current_stock,
                           m.reserved_quantity,
                           pom.required_quantity
                           FROM production_order_materials pom
                           JOIN raw_materials m ON m.id = pom.material_id 
                           WHERE pom.production_order_id = ?";
            $materialsStmt = $connect->prepare($materialsSql);
            $materialsStmt->bind_param('i', $orderId);
            $materialsStmt->execute();
            $materialsResult = $materialsStmt->get_result();
            
            $insufficientMaterials = array();
            while ($material = $materialsResult->fetch_assoc()) {
                $availableStock = $material['current_stock'] - $material['reserved_quantity'];
                if($availableStock < $material['required_quantity']) {
                    $insufficientMaterials[] = sprintf(
                        "%s (Required: %s, Available: %s, Reserved: %s)",
                        $material['name'],
                        number_format($material['required_quantity'], 2),
                        number_format($availableStock, 2),
                        number_format($material['reserved_quantity'], 2)
                    );
                }
            }
            
            if(!empty($insufficientMaterials)) {
                throw new Exception("Insufficient material stock available:\n\n" . 
                                  implode("\n", $insufficientMaterials) . 
                                  "\n\nPlease update material stock before confirming the order.");
            }
            
            // If stock is sufficient, update material reservations
            $materialsStmt->execute(); // Re-execute to refresh the result set
            $materialsResult = $materialsStmt->get_result();
            
            while ($material = $materialsResult->fetch_assoc()) {
                // Update production_order_materials
                $updatePOMSql = "UPDATE production_order_materials 
                                SET reservation_status = 'reserved',
                                    reserved_quantity = required_quantity,
                                    updated_at = CURRENT_TIMESTAMP 
                                WHERE production_order_id = ? 
                                AND material_id = ?";
                $updatePOMStmt = $connect->prepare($updatePOMSql);
                $updatePOMStmt->bind_param('ii', $orderId, $material['material_id']);
                if (!$updatePOMStmt->execute()) {
                    throw new Exception("Error updating material reservation: " . $connect->error);
                }
                
                // Update raw_materials reserved quantity
                $newReservedQty = $material['reserved_quantity'] + $material['required_quantity'];
                $updateRMSql = "UPDATE raw_materials 
                                SET reserved_quantity = ?,
                                    updated_at = CURRENT_TIMESTAMP 
                                WHERE id = ?";
                $updateRMStmt = $connect->prepare($updateRMSql);
                $updateRMStmt->bind_param('di', $newReservedQty, $material['material_id']);
                if (!$updateRMStmt->execute()) {
                    throw new Exception("Error updating material reserved quantity: " . $connect->error);
                }
                
                // Create material_reservations record
                $createReservationSql = "INSERT INTO material_reservations 
                                        (material_id, quantity, source_type, source_id, status, 
                                         notes, created_by, created_at) 
                                        VALUES (?, ?, 'production_order', ?, 'active', 
                                                'Reserved for production order', ?, CURRENT_TIMESTAMP)";
                $createReservationStmt = $connect->prepare($createReservationSql);
                $createReservationStmt->bind_param('idii', 
                    $material['material_id'],
                    $material['required_quantity'],
                    $orderId,
                    $userId
                );
                if (!$createReservationStmt->execute()) {
                    throw new Exception("Error creating material reservation record: " . $connect->error);
                }
            }
        }
        
        else if($newStatus === 'completed') {
            // Get completed quantity from request
            $completedQty = isset($_POST['completedQuantity']) ? floatval($_POST['completedQuantity']) : null;
            if ($completedQty === null || $completedQty <= 0) {
                throw new Exception("Valid completed quantity is required");
            }

            // Convert string values to float for comparison
            $targetQty = floatval($order['target_quantity']);
            $progressQty = floatval($order['total_progress']);
            
            error_log("Completion check - Target: $targetQty, Completed: $completedQty, Progress: $progressQty");
            
            // Validate completed quantity
            if($completedQty < $targetQty && $progressQty < $targetQty) {
                error_log("Completion with reduced quantity - Target: $targetQty, Completed: $completedQty, Progress: $progressQty");
                // Add a warning message to the response
                $response['warning'] = "Order completed with reduced quantity. Target was " . number_format($targetQty, 2) . 
                                     ", completed with " . number_format($completedQty, 2);
            }
            
            // Get product details
            $productSql = "SELECT * FROM production_products WHERE id = ?";
            $productStmt = $connect->prepare($productSql);
            $productStmt->bind_param('i', $order['product_id']);
            $productStmt->execute();
            $productResult = $productStmt->get_result();
            if($productResult->num_rows === 0) {
                throw new Exception("Product not found");
            }
            $product = $productResult->fetch_assoc();
            
            // Get and validate materials consumption
            $materialsSql = "SELECT 
                           m.id as material_id,
                           m.name,
                           m.current_stock,
                           pom.required_quantity,
                           COALESCE(pom.consumed_quantity, 0) as consumed_quantity
                           FROM production_order_materials pom
                           JOIN raw_materials m ON m.id = pom.material_id 
                           WHERE pom.production_order_id = ?";
            $materialsStmt = $connect->prepare($materialsSql);
            $materialsStmt->bind_param('i', $orderId);
            $materialsStmt->execute();
            $materialsResult = $materialsStmt->get_result();
            
            // Process material consumption updates if provided
            if (isset($_POST['materialConsumption'])) {
                $materialUpdates = json_decode($_POST['materialConsumption'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    // Create a lookup array for the updates
                    $materialUpdateMap = array();
                    foreach ($materialUpdates as $update) {
                        $materialUpdateMap[$update['material_id']] = $update['consumed_quantity'];
                    }
                    
                    // Validate and update each material
                    $incompleteMaterials = array();
                    while ($material = $materialsResult->fetch_assoc()) {
                        $materialId = $material['material_id'];
                        $consumedQty = isset($materialUpdateMap[$materialId]) ? 
                                     floatval($materialUpdateMap[$materialId]) : 
                                     floatval($material['consumed_quantity']);
                        
                        if ($consumedQty < $material['required_quantity']) {
                            $incompleteMaterials[] = sprintf(
                                "%s (Required: %s, Consumed: %s)",
                                $material['name'],
                                number_format($material['required_quantity'], 2),
                                number_format($consumedQty, 2)
                            );
                        } else {
                            // Update material consumption
                            $updateMaterialSql = "UPDATE production_order_materials 
                                                SET consumed_quantity = ?,
                                                    updated_at = CURRENT_TIMESTAMP 
                                                WHERE production_order_id = ? 
                                                AND material_id = ?";
                            $updateMaterialStmt = $connect->prepare($updateMaterialSql);
                            $updateMaterialStmt->bind_param('dii', $consumedQty, $orderId, $materialId);
                            if (!$updateMaterialStmt->execute()) {
                                throw new Exception("Error updating material consumption: " . $connect->error);
                            }

                            // Update raw material stock
                            $newStock = $material['current_stock'] - ($consumedQty - floatval($material['consumed_quantity']));
                            if ($newStock < 0) {
                                throw new Exception("Insufficient stock for material: " . $material['name']);
                            }

                            $updateStockSql = "UPDATE raw_materials 
                                             SET current_stock = ?,
                                                 updated_at = CURRENT_TIMESTAMP 
                                             WHERE id = ?";
                            $updateStockStmt = $connect->prepare($updateStockSql);
                            $updateStockStmt->bind_param('di', $newStock, $materialId);
                            if (!$updateStockStmt->execute()) {
                                throw new Exception("Error updating material stock: " . $connect->error);
                            }

                            // Record material consumption in raw_material_movements
                            $consumptionQty = $consumedQty - floatval($material['consumed_quantity']);
                            if ($consumptionQty > 0) {
                                $movementSql = "INSERT INTO raw_material_movements 
                                              (material_id, movement_type, quantity, reference_type, 
                                               reference_id, notes, created_by, created_at) 
                                              VALUES (?, 'out', ?, 'production', ?, ?, ?, CURRENT_TIMESTAMP)";
                                
                                $movementNotes = sprintf(
                                    "Material consumed in Production Order #%s - %s. Completed production.",
                                    $order['order_number'],
                                    $order['product_name']
                                );
                                
                                $movementStmt = $connect->prepare($movementSql);
                                $movementStmt->bind_param("idisi", 
                                    $materialId, 
                                    $consumptionQty, 
                                    $orderId,
                                    $movementNotes,
                                    $userId
                                );
                                
                                if (!$movementStmt->execute()) {
                                    throw new Exception("Error recording stock movement: " . $connect->error);
                                }
                            }
                        }
                    }
                    
                    if (!empty($incompleteMaterials)) {
                        throw new Exception("Cannot complete order: The following materials have not been fully consumed:\n\n" . 
                                          implode("\n", $incompleteMaterials) . 
                                          "\n\nPlease update material consumption before completing the order.");
                    }
                } else {
                    throw new Exception("Invalid material consumption data provided");
                }
            }

            // 1. Update production order status and completed quantity
            $updateOrderSql = "UPDATE production_orders 
                             SET completed_quantity = ?,
                                 actual_completion_date = CURRENT_DATE,
                                 status = 'completed',
                                 updated_at = CURRENT_TIMESTAMP
                             WHERE id = ?";
            $updateOrderStmt = $connect->prepare($updateOrderSql);
            $updateOrderStmt->bind_param('di', $completedQty, $orderId);
            if(!$updateOrderStmt->execute()) {
                throw new Exception("Error updating order completion details: " . $connect->error);
            }

            // 2. Update production product stock
            $newStock = floatval($product['current_stock']) + $completedQty;
            $updateProductSql = "UPDATE production_products 
                               SET current_stock = ?,
                                   updated_at = CURRENT_TIMESTAMP 
                               WHERE id = ?";
            $updateProductStmt = $connect->prepare($updateProductSql);
            $updateProductStmt->bind_param('di', $newStock, $order['product_id']);
            if(!$updateProductStmt->execute()) {
                throw new Exception("Error updating product stock: " . $connect->error);
            }

            // 3. Create inventory movement record instead of stock movement
            $inventoryMovementSql = "INSERT INTO inventory_movement_log 
                                   (item_type, item_id, source_type, destination_type, 
                                    quantity, movement_type, reference_type, reference_id,
                                    notes, status, created_at, created_by) 
                                   VALUES 
                                   ('finished_good', ?, 'production', 'warehouse',
                                    ?, 'production_in', 'production_order', ?,
                                    'Production completion', 'completed', CURRENT_TIMESTAMP, ?)";
            $inventoryMovementStmt = $connect->prepare($inventoryMovementSql);
            $inventoryMovementStmt->bind_param('idii', 
                $order['product_id'],
                $completedQty,
                $orderId,
                $userId
            );
            if(!$inventoryMovementStmt->execute()) {
                throw new Exception("Error recording inventory movement: " . $connect->error);
            }

            // Send Telegram notification for completion
            try {
                $telegram = new TelegramHelper();
                
                // Prepare completion notification data
                $completionData = [
                    'order_number' => $order['order_number'],
                    'product_name' => $order['product_name'],
                    'target_quantity' => $order['target_quantity'],
                    'completed_quantity' => $completedQty,
                    'start_date' => $order['start_date'],
                    'completion_date' => date('Y-m-d'),
                    'created_by' => $order['created_by_name']
                ];

                // Create completion message
                $message = "✅ Production Order Completed!\n\n";
                $message .= "📦 Order: " . $completionData['order_number'] . "\n";
                $message .= "🛠️ Product: " . $completionData['product_name'] . "\n";
                $message .= "📊 Quantity: " . $completedQty . " / " . $completionData['target_quantity'] . "\n";
                $message .= "📅 Start Date: " . $completionData['start_date'] . "\n";
                $message .= "✨ Completion Date: " . $completionData['completion_date'] . "\n";
                $message .= "👤 Created By: " . $completionData['created_by'];

                // Send the notification
                $telegram->sendMessage($message);
            } catch (Exception $e) {
                // Log the error but don't stop the process
                error_log("Telegram completion notification failed: " . $e->getMessage());
            }

            // Skip the general status update since we've already updated it
            $skipGeneralUpdate = true;
        }

        // Update order status (only if not completed, since completed has its own update)
        if (!isset($skipGeneralUpdate)) {
            $updateSql = "UPDATE production_orders 
                         SET status = ?,
                             start_date = CASE 
                                 WHEN ? = 'inprogress' AND start_date IS NULL THEN CURRENT_DATE
                                 ELSE start_date
                             END,
                             actual_completion_date = CASE 
                                 WHEN ? = 'completed' THEN CURRENT_DATE
                                 ELSE actual_completion_date
                             END,
                             updated_at = CURRENT_TIMESTAMP
                         WHERE id = ?";
            
            $updateStmt = $connect->prepare($updateSql);
            $updateStmt->bind_param('sssi', $newStatus, $newStatus, $newStatus, $orderId);
            
            if(!$updateStmt->execute()) {
                throw new Exception("Error updating order status: " . $connect->error);
            }
        }

        // Commit transaction
        $connect->commit();
        
        // Get updated order data for response
        $updatedOrderSql = "SELECT 
                           po.*, 
                           pp.name as product_name,
                           COALESCE(u.username, 'System') as created_by_name
                           FROM production_orders po 
                           LEFT JOIN production_products pp ON po.product_id = pp.id 
                           LEFT JOIN users u ON po.created_by = u.user_id
                           WHERE po.id = ?";
        $updatedOrderStmt = $connect->prepare($updatedOrderSql);
        $updatedOrderStmt->bind_param('i', $orderId);
        $updatedOrderStmt->execute();
        $updatedOrder = $updatedOrderStmt->get_result()->fetch_assoc();
        
        $response = array(
            'success' => true,
            'messages' => "Production order status updated successfully from $currentStatus to $newStatus",
            'data' => array(
                'order' => $updatedOrder,
                'status' => $newStatus,
                'previous_status' => $currentStatus
            )
        );
        
    } catch(Exception $e) {
        if ($connect->connect_error === null) {
            $connect->rollback();
        }
        $response['success'] = false;
        $response['messages'] = $e->getMessage();
        error_log("Error in changeProductionOrderStatus.php: " . $e->getMessage());
    }

    // Ensure proper JSON encoding
    header('Content-Type: application/json');
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

// If we get here, it means no POST data
header('Content-Type: application/json');
echo json_encode([
    'success' => false,
    'messages' => 'Invalid request method'
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$connect->close(); 