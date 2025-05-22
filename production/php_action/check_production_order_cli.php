<?php
/**
 * Command-line script to check if a production order was properly processed
 */

// Include database connection
require_once 'db_connect.php';

// Get the order number from argument or use default
$orderNumber = isset($argv[1]) ? $argv[1] : 'PO-20250416-9247';

echo "=== CHECKING PRODUCTION ORDER: {$orderNumber} ===\n\n";

// Check 1: Production Order exists and status
$orderSql = "SELECT * FROM production_orders WHERE order_number = ?";
$stmt = $connect->prepare($orderSql);
$stmt->bind_param("s", $orderNumber);
$stmt->execute();
$orderResult = $stmt->get_result();

if ($orderResult->num_rows > 0) {
    $order = $orderResult->fetch_assoc();
    echo "1. PRODUCTION ORDER DETAILS:\n";
    echo "   Order ID: " . $order['id'] . "\n";
    echo "   Order Number: " . $order['order_number'] . "\n";
    echo "   Product ID: " . $order['product_id'] . "\n";
    echo "   Target Quantity: " . $order['target_quantity'] . "\n";
    echo "   Completed Quantity: " . $order['completed_quantity'] . "\n";
    echo "   Status: " . strtoupper($order['status']) . "\n";
    echo "   Start Date: " . $order['start_date'] . "\n";
    echo "   Expected Completion: " . $order['expected_completion_date'] . "\n";
    echo "   Actual Completion: " . ($order['actual_completion_date'] ?? 'N/A') . "\n";
    echo "\n";
    
    $orderId = $order['id'];
    $productId = $order['product_id'];
    $orderStatus = $order['status'];
    
    // Check 2: Material Requirements and Consumption
    $materialsSql = "SELECT m.*, r.name as material_name, r.current_stock, r.reserved_quantity as stock_reserved_qty
                     FROM production_order_materials m
                     JOIN raw_materials r ON m.material_id = r.id
                     WHERE m.production_order_id = ?";
    $stmt = $connect->prepare($materialsSql);
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $materialsResult = $stmt->get_result();
    
    if ($materialsResult->num_rows > 0) {
        echo "2. MATERIAL REQUIREMENTS AND CONSUMPTION:\n";
        $header = sprintf("%-5s %-20s %-10s %-10s %-10s %-15s %-10s %-10s\n", 
            "ID", "Name", "Required", "Reserved", "Consumed", "Status", "Stock", "Stock Rsv");
        echo "   $header";
        echo "   " . str_repeat("-", strlen($header)) . "\n";
        
        while ($material = $materialsResult->fetch_assoc()) {
            printf("   %-5s %-20s %-10s %-10s %-10s %-15s %-10s %-10s\n",
                $material['material_id'],
                substr($material['material_name'], 0, 18),
                $material['required_quantity'],
                $material['reserved_quantity'],
                $material['consumed_quantity'],
                $material['reservation_status'],
                $material['current_stock'],
                $material['stock_reserved_qty']
            );
        }
        echo "\n";
    } else {
        echo "2. MATERIAL REQUIREMENTS AND CONSUMPTION: None found\n\n";
    }
    
    // Check 3: Material Reservations
    $reservationsSql = "SELECT mr.*, rm.name as material_name
                        FROM material_reservations mr
                        JOIN raw_materials rm ON mr.material_id = rm.id
                        WHERE mr.source_type = 'production_order' AND mr.source_id = ?";
    $stmt = $connect->prepare($reservationsSql);
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $reservationsResult = $stmt->get_result();
    
    if ($reservationsResult->num_rows > 0) {
        echo "3. MATERIAL RESERVATIONS:\n";
        $header = sprintf("%-5s %-20s %-10s %-15s %-12s\n", 
            "ID", "Material", "Quantity", "Status", "Resv. Date");
        echo "   $header";
        echo "   " . str_repeat("-", strlen($header)) . "\n";
        
        while ($reservation = $reservationsResult->fetch_assoc()) {
            printf("   %-5s %-20s %-10s %-15s %-12s\n",
                $reservation['id'],
                substr($reservation['material_name'], 0, 18),
                $reservation['quantity'],
                $reservation['status'],
                $reservation['reservation_date'] ?? 'N/A'
            );
        }
        echo "\n";
    } else {
        echo "3. MATERIAL RESERVATIONS: None found\n";
        echo "   (This may be normal if the order was never confirmed or if reservations were already consumed/released)\n\n";
    }
    
    // Check 4: Inventory Movement
    $movementSql = "SELECT * FROM inventory_movement_log
                    WHERE reference_type = 'production_order' AND reference_id = ?";
    $stmt = $connect->prepare($movementSql);
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $movementResult = $stmt->get_result();
    
    if ($movementResult->num_rows > 0) {
        echo "4. INVENTORY MOVEMENT:\n";
        $header = sprintf("%-5s %-10s %-7s %-10s %-15s %-10s %-20s\n", 
            "ID", "Item Type", "Item ID", "Quantity", "Movement Type", "Status", "Created At");
        echo "   $header";
        echo "   " . str_repeat("-", strlen($header)) . "\n";
        
        while ($movement = $movementResult->fetch_assoc()) {
            printf("   %-5s %-10s %-7s %-10s %-15s %-10s %-20s\n",
                $movement['id'],
                $movement['item_type'],
                $movement['item_id'],
                $movement['quantity'],
                $movement['movement_type'],
                $movement['status'],
                $movement['created_at']
            );
        }
        echo "\n";
    } else {
        $alternativeMovementSql = "SELECT * FROM warehouse_stock_movements
                                  WHERE reference_type = 'production' AND reference_id = ?";
        $stmt = $connect->prepare($alternativeMovementSql);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $altMovementResult = $stmt->get_result();
        
        if ($altMovementResult->num_rows > 0) {
            echo "4. WAREHOUSE STOCK MOVEMENT:\n";
            $header = sprintf("%-5s %-12s %-7s %-10s %-10s %-15s %-20s\n", 
                "ID", "Warehouse", "Item ID", "Item Type", "Quantity", "Movement", "Created At");
            echo "   $header";
            echo "   " . str_repeat("-", strlen($header)) . "\n";
            
            while ($movement = $altMovementResult->fetch_assoc()) {
                printf("   %-5s %-12s %-7s %-10s %-10s %-15s %-20s\n",
                    $movement['id'],
                    $movement['warehouse_id'],
                    $movement['item_id'],
                    $movement['item_type'],
                    $movement['quantity'],
                    $movement['movement_type'],
                    $movement['created_at']
                );
            }
            echo "\n";
        } else {
            echo "4. INVENTORY/STOCK MOVEMENT: None found\n";
            echo "   (This is normal if the order is not completed)\n\n";
        }
    }
    
    // Check 5: Product Stock Update
    $productSql = "SELECT id, name, current_stock FROM production_products WHERE id = ?";
    $stmt = $connect->prepare($productSql);
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $productResult = $stmt->get_result();
    
    if ($productResult->num_rows > 0) {
        $product = $productResult->fetch_assoc();
        echo "5. PRODUCT STOCK:\n";
        echo "   Product ID: " . $product['id'] . "\n";
        echo "   Product Name: " . $product['name'] . "\n";
        echo "   Current Stock: " . $product['current_stock'] . "\n";
        
        // Provide analysis based on order status
        if ($orderStatus == 'completed') {
            if ($product['current_stock'] >= $order['completed_quantity']) {
                echo "   ANALYSIS: Product stock appears to have been updated correctly upon order completion.\n";
            } else {
                echo "   WARNING: Product stock may not have been updated correctly.\n";
                echo "            Current stock is less than the completed quantity.\n";
            }
        } else {
            echo "   NOTE: The order is not marked as completed, so the product stock may not have been updated yet.\n";
        }
        echo "\n";
    } else {
        echo "5. PRODUCT STOCK: Product not found\n\n";
    }
    
    // Check 6: Progress updates
    $progressSql = "SELECT * FROM production_progress WHERE production_order_id = ? ORDER BY id DESC";
    $stmt = $connect->prepare($progressSql);
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $progressResult = $stmt->get_result();
    
    if ($progressResult->num_rows > 0) {
        echo "6. PRODUCTION PROGRESS:\n";
        $header = sprintf("%-5s %-10s %-20s %-12s %-20s\n", 
            "ID", "Quantity", "Notes", "Recorded By", "Recorded At");
        echo "   $header";
        echo "   " . str_repeat("-", strlen($header)) . "\n";
        
        while ($progress = $progressResult->fetch_assoc()) {
            printf("   %-5s %-10s %-20s %-12s %-20s\n",
                $progress['id'],
                $progress['quantity_produced'],
                substr($progress['notes'] ?? 'N/A', 0, 18),
                $progress['recorded_by'] ?? 'System',
                isset($progress['recorded_at']) ? $progress['recorded_at'] : 'N/A'
            );
        }
        echo "\n";
    } else {
        echo "6. PRODUCTION PROGRESS: None found\n";
        echo "   (This might be normal if progress wasn't recorded incrementally)\n\n";
    }
    
    // Overall status
    echo "=== OVERALL PRODUCTION ORDER STATUS ===\n";
    echo "Order Status: " . strtoupper($orderStatus) . "\n";
    
    if ($orderStatus == 'completed') {
        echo "The production order has been completed successfully.\n";
        echo "Completed Quantity: {$order['completed_quantity']} / Target Quantity: {$order['target_quantity']}\n";
        
        if ($order['completed_quantity'] < $order['target_quantity']) {
            echo "NOTE: Order was completed with less than the target quantity.\n";
        }
    } elseif ($orderStatus == 'cancelled') {
        echo "The production order was cancelled.\n";
    } else {
        echo "The production order is still in progress (status: {$orderStatus}).\n";
    }
    echo "\n";
    
} else {
    echo "ERROR: Production Order Not Found\n";
    echo "No production order found with number: {$orderNumber}\n";
}

$connect->close();
?> 