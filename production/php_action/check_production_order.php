<?php
/**
 * Script to check if a production order was properly processed
 * This checks all related tables and verifies the process flow worked
 */

// Include database connection
require_once 'db_connect.php';

// Get the order number from GET parameter
$orderNumber = isset($_GET['order_number']) ? $_GET['order_number'] : 'PO-20250416-9247';

echo "<h2>Checking Production Order: {$orderNumber}</h2>";

// Check 1: Production Order exists and status
$orderSql = "SELECT * FROM production_orders WHERE order_number = ?";
$stmt = $connect->prepare($orderSql);
$stmt->bind_param("s", $orderNumber);
$stmt->execute();
$orderResult = $stmt->get_result();

if ($orderResult->num_rows > 0) {
    $order = $orderResult->fetch_assoc();
    echo "<h3>1. Production Order Details ✅</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    foreach ($order as $field => $value) {
        echo "<tr><td>{$field}</td><td>{$value}</td></tr>";
    }
    echo "</table>";
    
    $orderId = $order['id'];
    $productId = $order['product_id'];
    $orderStatus = $order['status'];
    
    // Check 2: Material Requirements and Consumption
    $materialsSql = "SELECT m.*, r.name as material_name, r.current_stock 
                     FROM production_order_materials m
                     JOIN raw_materials r ON m.material_id = r.id
                     WHERE m.production_order_id = ?";
    $stmt = $connect->prepare($materialsSql);
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $materialsResult = $stmt->get_result();
    
    if ($materialsResult->num_rows > 0) {
        echo "<h3>2. Material Requirements and Consumption ✅</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>
                <th>Material ID</th>
                <th>Material Name</th>
                <th>Required Qty</th>
                <th>Reserved Qty</th>
                <th>Consumed Qty</th>
                <th>Reservation Status</th>
                <th>Current Stock</th>
              </tr>";
              
        while ($material = $materialsResult->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$material['material_id']}</td>";
            echo "<td>{$material['material_name']}</td>";
            echo "<td>{$material['required_quantity']}</td>";
            echo "<td>{$material['reserved_quantity']}</td>";
            echo "<td>{$material['consumed_quantity']}</td>";
            echo "<td>{$material['reservation_status']}</td>";
            echo "<td>{$material['current_stock']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<h3>2. Material Requirements and Consumption ❌</h3>";
        echo "<p>No materials found for this order</p>";
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
        echo "<h3>3. Material Reservations ✅</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>
                <th>ID</th>
                <th>Material Name</th>
                <th>Quantity</th>
                <th>Status</th>
                <th>Reservation Date</th>
              </tr>";
              
        while ($reservation = $reservationsResult->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$reservation['id']}</td>";
            echo "<td>{$reservation['material_name']}</td>";
            echo "<td>{$reservation['quantity']}</td>";
            echo "<td>{$reservation['status']}</td>";
            echo "<td>{$reservation['reservation_date']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<h3>3. Material Reservations ❓</h3>";
        echo "<p>No reservations found for this order. This may be normal if the order was never confirmed or if reservations were already consumed or released.</p>";
    }
    
    // Check 4: Inventory Movement
    $movementSql = "SELECT * FROM inventory_movement_log
                    WHERE reference_type = 'production_order' AND reference_id = ?";
    $stmt = $connect->prepare($movementSql);
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $movementResult = $stmt->get_result();
    
    if ($movementResult->num_rows > 0) {
        echo "<h3>4. Inventory Movement ✅</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>
                <th>ID</th>
                <th>Item Type</th>
                <th>Item ID</th>
                <th>Quantity</th>
                <th>Movement Type</th>
                <th>Status</th>
                <th>Created At</th>
              </tr>";
              
        while ($movement = $movementResult->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$movement['id']}</td>";
            echo "<td>{$movement['item_type']}</td>";
            echo "<td>{$movement['item_id']}</td>";
            echo "<td>{$movement['quantity']}</td>";
            echo "<td>{$movement['movement_type']}</td>";
            echo "<td>{$movement['status']}</td>";
            echo "<td>{$movement['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        $alternativeMovementSql = "SELECT * FROM warehouse_stock_movements
                                  WHERE reference_type = 'production' AND reference_id = ?";
        $stmt = $connect->prepare($alternativeMovementSql);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $altMovementResult = $stmt->get_result();
        
        if ($altMovementResult->num_rows > 0) {
            echo "<h3>4. Warehouse Stock Movement ✅</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr>
                    <th>ID</th>
                    <th>Warehouse ID</th>
                    <th>Item ID</th>
                    <th>Item Type</th>
                    <th>Quantity</th>
                    <th>Movement Type</th>
                    <th>Created At</th>
                  </tr>";
                  
            while ($movement = $altMovementResult->fetch_assoc()) {
                echo "<tr>";
                echo "<td>{$movement['id']}</td>";
                echo "<td>{$movement['warehouse_id']}</td>";
                echo "<td>{$movement['item_id']}</td>";
                echo "<td>{$movement['item_type']}</td>";
                echo "<td>{$movement['quantity']}</td>";
                echo "<td>{$movement['movement_type']}</td>";
                echo "<td>{$movement['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<h3>4. Inventory/Stock Movement ❓</h3>";
            echo "<p>No inventory or warehouse movements found for this order. This is normal if the order is not completed.</p>";
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
        echo "<h3>5. Product Stock ✅</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>Product ID</td><td>{$product['id']}</td></tr>";
        echo "<tr><td>Product Name</td><td>{$product['name']}</td></tr>";
        echo "<tr><td>Current Stock</td><td>{$product['current_stock']}</td></tr>";
        echo "</table>";
        
        // Provide analysis based on order status
        if ($orderStatus == 'completed') {
            if ($product['current_stock'] >= $order['completed_quantity']) {
                echo "<p>✅ The product stock appears to have been updated correctly upon order completion.</p>";
            } else {
                echo "<p>❌ The product stock may not have been updated correctly. Current stock is less than the completed quantity.</p>";
            }
        } else {
            echo "<p>ℹ️ The order is not marked as completed, so the product stock may not have been updated yet.</p>";
        }
    } else {
        echo "<h3>5. Product Stock ❌</h3>";
        echo "<p>Product not found</p>";
    }
    
    // Check 6: Progress updates
    $progressSql = "SELECT * FROM production_progress WHERE production_order_id = ? ORDER BY recorded_at DESC";
    $stmt = $connect->prepare($progressSql);
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $progressResult = $stmt->get_result();
    
    if ($progressResult->num_rows > 0) {
        echo "<h3>6. Production Progress ✅</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>
                <th>ID</th>
                <th>Quantity</th>
                <th>Notes</th>
                <th>Recorded By</th>
                <th>Recorded At</th>
              </tr>";
              
        while ($progress = $progressResult->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$progress['id']}</td>";
            echo "<td>{$progress['quantity_produced']}</td>";
            echo "<td>{$progress['notes']}</td>";
            echo "<td>{$progress['recorded_by']}</td>";
            echo "<td>{$progress['recorded_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<h3>6. Production Progress ❓</h3>";
        echo "<p>No progress records found for this order. This might be normal if progress wasn't recorded incrementally.</p>";
    }
    
    // Overall status
    echo "<h3>Overall Production Order Status</h3>";
    echo "<div style='padding: 10px; background-color: #f8f9fa; border-radius: 5px;'>";
    echo "<p><strong>Order Status:</strong> " . strtoupper($orderStatus) . "</p>";
    
    if ($orderStatus == 'completed') {
        echo "<p>✅ The production order has been completed successfully.</p>";
        echo "<p>Completed Quantity: {$order['completed_quantity']} / Target Quantity: {$order['target_quantity']}</p>";
        
        if ($order['completed_quantity'] < $order['target_quantity']) {
            echo "<p>⚠️ Note: Order was completed with less than the target quantity.</p>";
        }
    } elseif ($orderStatus == 'cancelled') {
        echo "<p>❌ The production order was cancelled.</p>";
    } else {
        echo "<p>ℹ️ The production order is still in progress (status: {$orderStatus}).</p>";
    }
    echo "</div>";
    
} else {
    echo "<h3>Production Order Not Found ❌</h3>";
    echo "<p>No production order found with number: {$orderNumber}</p>";
}

$connect->close();
?> 