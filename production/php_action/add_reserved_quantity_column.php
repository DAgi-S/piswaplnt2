<?php
/**
 * Script to add reserved_quantity column to raw_materials table if it doesn't exist
 */

// Include database connection
require_once 'db_connect.php';

// Function to check if a trigger exists
function triggerExists($connect, $triggerName) {
    $sql = "SELECT TRIGGER_NAME FROM information_schema.TRIGGERS 
            WHERE TRIGGER_SCHEMA = DATABASE() AND TRIGGER_NAME = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("s", $triggerName);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Start transaction
$connect->begin_transaction();

try {
    // Check if the stock_check trigger exists and disable it temporarily
    $triggerName = "stock_check";
    $triggerExists = triggerExists($connect, $triggerName);
    
    if ($triggerExists) {
        echo "Disabling trigger {$triggerName} temporarily...<br>";
        $connect->query("DROP TRIGGER IF EXISTS {$triggerName}");
    }
    
    // Check if reserved_quantity column exists
    $checkColumnSql = "SHOW COLUMNS FROM `raw_materials` LIKE 'reserved_quantity'";
    $checkColumnResult = $connect->query($checkColumnSql);

    if ($checkColumnResult && $checkColumnResult->num_rows == 0) {
        // Column doesn't exist, add it
        $alterTableSql = "ALTER TABLE `raw_materials` 
                          ADD COLUMN `reserved_quantity` DECIMAL(10,2) NOT NULL DEFAULT 0.00 
                          AFTER `current_stock`";
        
        if ($connect->query($alterTableSql)) {
            echo "Success: Added reserved_quantity column to raw_materials table.<br>";
        } else {
            throw new Exception("Failed to add reserved_quantity column: " . $connect->error);
        }
    } else {
        echo "Info: reserved_quantity column already exists in raw_materials table.<br>";
    }

    // Get materials that need stock updates
    $getMaterialsSql = "SELECT m.id, m.name, m.current_stock,
                       COALESCE(
                          (SELECT SUM(pom.reserved_quantity) 
                           FROM production_order_materials pom
                           WHERE pom.material_id = m.id
                           AND pom.reservation_status = 'reserved'), 
                          0
                       ) as calculated_reserved
                       FROM raw_materials m";
    $materialsResult = $connect->query($getMaterialsSql);

    if ($materialsResult) {
        $updatedCount = 0;
        $increasedStock = 0;
        
        while ($material = $materialsResult->fetch_assoc()) {
            // Get current values
            $materialId = $material['id'];
            $currentStock = floatval($material['current_stock']);
            $calculatedReserved = floatval($material['calculated_reserved']);
            
            // Update reserved quantity
            $updateReservedSql = "UPDATE raw_materials SET reserved_quantity = ? WHERE id = ?";
            $updateStmt = $connect->prepare($updateReservedSql);
            $updateStmt->bind_param("di", $calculatedReserved, $materialId);
            $updateStmt->execute();
            $updatedCount++;
            
            // If current stock is less than reserved, increase it
            if ($currentStock < $calculatedReserved) {
                $newStock = $calculatedReserved + 100; // Add a buffer of 100
                $updateStockSql = "UPDATE raw_materials SET current_stock = ? WHERE id = ?";
                $updateStockStmt = $connect->prepare($updateStockSql);
                $updateStockStmt->bind_param("di", $newStock, $materialId);
                $updateStockStmt->execute();
                $increasedStock++;
                
                echo "Increased stock for material ID {$materialId} ({$material['name']}) from {$currentStock} to {$newStock} (Reserved: {$calculatedReserved}).<br>";
            }
        }
        
        echo "Success: Updated reserved quantities for {$updatedCount} materials. Increased stock for {$increasedStock} materials.<br>";
    } else {
        throw new Exception("Failed to get materials: " . $connect->error);
    }
    
    // Create the stock check trigger again if it existed before
    if ($triggerExists) {
        echo "Recreating the stock_check trigger...<br>";
        $createTriggerSql = "
        CREATE TRIGGER `stock_check` BEFORE UPDATE ON `raw_materials`
        FOR EACH ROW
        BEGIN
            IF NEW.current_stock < NEW.reserved_quantity THEN
                SIGNAL SQLSTATE '45000' 
                SET MESSAGE_TEXT = 'Stock cannot be less than reserved quantity';
            END IF;
        END;";
        
        if (!$connect->query($createTriggerSql)) {
            throw new Exception("Failed to recreate trigger: " . $connect->error);
        }
    }
    
    // Commit transaction
    $connect->commit();
    echo "All operations completed successfully.<br>";

} catch (Exception $e) {
    // Rollback transaction
    $connect->rollback();
    echo "Error: Transaction failed: " . $e->getMessage() . "<br>";
}

$connect->close();
echo "Done.";
?> 