<?php
require_once 'core.php';
require_once 'db_connect.php';

// Clear any previous output that might corrupt JSON
while (ob_get_level()) ob_end_clean();
header('Content-Type: application/json');

$response = array(
    'success' => false,
    'messages' => ''
);

if($_POST) {
    try {
        // Debug log
        error_log("Received POST data: " . print_r($_POST, true));
        
        $supplier = $_POST['supplier'];
        
        // Validate and format purchase date
        if (!isset($_POST['purchase_date']) || empty($_POST['purchase_date'])) {
            throw new Exception("Purchase date is required");
        }

        $inputDate = $_POST['purchase_date'];
        error_log("Received purchase date: " . $inputDate);

        // Parse and validate the date
        $dateObj = DateTime::createFromFormat('Y-m-d', $inputDate);
        if (!$dateObj || $dateObj->format('Y-m-d') !== $inputDate) {
            throw new Exception("Invalid date format. Expected YYYY-MM-DD");
        }

        // Validate that date is not in future
        $today = new DateTime();
        $today->setTime(23, 59, 59); // Set to end of day
        $dateObj->setTime(0, 0, 0); // Set to start of day
        if ($dateObj > $today) {
            throw new Exception("Purchase date cannot be in the future");
        }

        // Format the date for MySQL
        $purchaseDate = $dateObj->format('Y-m-d');
        error_log("Final purchase date to be inserted: " . $purchaseDate);
        
        $warehouseId = isset($_POST['warehouse']) ? intval($_POST['warehouse']) : 0;
        $subTotal = $_POST['subTotalValue'];
        $vat = $_POST['vat'];
        $grandTotal = $_POST['grandTotalValue'];
        $withholding_enabled = isset($_POST['withholding_enabled']) ? 1 : 0;
        $withholding_amount = $withholding_enabled ? $_POST['withholding_amount'] : 0;
        $note = $_POST['note'];
        
        // Validate warehouse
        if(!$warehouseId) {
            throw new Exception("Please select a warehouse");
        }
        
        // Generate purchase number (format: PO-YYYYMMDD-XXX)
        $today = date('Ymd');
        $query = "SELECT purchase_number FROM purchases WHERE purchase_number LIKE 'PO-$today%' ORDER BY purchase_number DESC LIMIT 1";
        $result = $connect->query($query);
        
        if($result && $result->num_rows > 0) {
            $row = $result->fetch_array();
            $lastNumber = substr($row['purchase_number'], -3);
            $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '001';
        }
        
        $purchaseNumber = "PO-$today-$nextNumber";
        
        // Start transaction
        $connect->begin_transaction();
        
        // Debug log
        error_log("Inserting purchase with date: " . $purchaseDate);
        
        $sql = "INSERT INTO purchases (
            purchase_number, 
            supplier_id, 
            purchase_date, 
            sub_total, 
            vat, 
            grand_total, 
            withholding_tax_enabled, 
            withholding_tax_amount, 
            note, 
            status, 
            active, 
            created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1, ?)";
                
        $stmt = $connect->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $connect->error);
        }
        
        // Debug log
        error_log("SQL Query: " . $sql);
        error_log("Parameters: purchaseNumber=" . $purchaseNumber . ", supplier=" . $supplier . ", purchaseDate=" . $purchaseDate);
        
        if (!$stmt->bind_param("sssdddddsi", 
            $purchaseNumber, 
            $supplier, 
            $purchaseDate,  // Changed binding type back to 's' for date
            $subTotal, 
            $vat, 
            $grandTotal, 
            $withholding_enabled, 
            $withholding_amount, 
            $note, 
            $_SESSION['userId']
        )) {
            throw new Exception("Error binding parameters: " . $stmt->error);
        }
        
        if(!$stmt->execute()) {
            throw new Exception("Error creating purchase order: " . $stmt->error . " SQL State: " . $stmt->sqlstate);
        }
        
        $purchase_id = $connect->insert_id;
        
        // Add purchase items
        $productName = $_POST['productName'];
        $quantity = $_POST['quantity'];
        $rate = $_POST['rate'];
        
        // Set default location_id
        $defaultLocationId = 1;
        
        // Insert purchase items
        $itemSql = "INSERT INTO purchase_items (purchase_id, raw_material_id, warehouse_id, location_id, quantity, rate, total) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $itemStmt = $connect->prepare($itemSql);
        
        // Create a movement record for each item
        $movementSql = "INSERT INTO raw_material_movements (material_id, movement_type, quantity, reference_type, reference_id, notes, warehouse_id, source_location_id, destination_location_id, created_by) VALUES (?, 'in', ?, 'purchase', ?, ?, ?, NULL, ?, ?)";
        $movementStmt = $connect->prepare($movementSql);
        
        // Update raw materials stock
        $updateSql = "UPDATE raw_materials SET current_stock = current_stock + ? WHERE id = ?";
        $updateStmt = $connect->prepare($updateSql);
        
        // Update location utilization
        $updateLocationSql = "UPDATE storage_locations SET current_utilization = current_utilization + ? WHERE id = ?";
        $updateLocationStmt = $connect->prepare($updateLocationSql);
        
        for($x = 0; $x < count($productName); $x++) {
            $total = $quantity[$x] * $rate[$x];
            
            // Insert purchase item
            $itemStmt->bind_param("iiidddd", 
                $purchase_id, 
                $productName[$x], 
                $warehouseId,
                $defaultLocationId,
                $quantity[$x], 
                $rate[$x], 
                $total
            );
            if(!$itemStmt->execute()) {
                throw new Exception("Error adding purchase item: " . $itemStmt->error);
            }
            
            // Record movement
            $movementNote = "Purchase Order: " . $purchaseNumber;
            $movementStmt->bind_param("idisiii", 
                $productName[$x], 
                $quantity[$x], 
                $purchase_id, 
                $movementNote,
                $warehouseId,
                $defaultLocationId,
                $_SESSION['userId']
            );
            if(!$movementStmt->execute()) {
                throw new Exception("Error recording material movement: " . $movementStmt->error);
            }
            
            // Update stock
            $updateStmt->bind_param("di", $quantity[$x], $productName[$x]);
            if(!$updateStmt->execute()) {
                throw new Exception("Error updating stock: " . $updateStmt->error);
            }
            
            // Update location utilization
            $updateLocationStmt->bind_param("di", $quantity[$x], $defaultLocationId);
            if(!$updateLocationStmt->execute()) {
                throw new Exception("Error updating location utilization: " . $updateLocationStmt->error);
            }
        }
        
        // Commit transaction
        $connect->commit();
        
        $response['success'] = true;
        $response['messages'] = "Purchase order created successfully";
    } catch(Exception $e) {
        // Rollback transaction on error
        $connect->rollback();
        $response['success'] = false;
        $response['messages'] = $e->getMessage();
        
        // Log the error
        error_log("Purchase Order Error: " . $e->getMessage());
    }
    
    $connect->close();
}

echo json_encode($response); 