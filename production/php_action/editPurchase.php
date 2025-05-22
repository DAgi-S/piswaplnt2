<?php
require_once 'core.php';

// Clear any previous output that might corrupt JSON
while (ob_get_level()) ob_end_clean();
header('Content-Type: application/json');

// Check user permission
if (!hasPermission('purchase.edit')) {
    echo json_encode([
        'success' => false,
        'messages' => 'You do not have permission to edit purchases'
    ]);
    exit();
}

$response = array(
    'success' => false,
    'messages' => ''
);

if($_POST) {
    try {
        $connect->begin_transaction();

        // Debug log
        error_log("Received POST data: " . print_r($_POST, true));

        // Validate required fields
        if(!isset($_POST['purchaseId']) || empty($_POST['purchaseId'])) {
            throw new Exception("Purchase ID is required");
        }
        if(!isset($_POST['supplier']) || empty($_POST['supplier'])) {
            throw new Exception("Please select a supplier");
        }

        // Validate and format purchase date
        if (!isset($_POST['purchaseDate']) || empty($_POST['purchaseDate'])) {
            throw new Exception("Purchase date is required");
        }

        $inputDate = $_POST['purchaseDate'];
        $dateObj = DateTime::createFromFormat('Y-m-d', $inputDate);
        if (!$dateObj || $dateObj->format('Y-m-d') !== $inputDate) {
            throw new Exception("Invalid date format. Expected YYYY-MM-DD");
        }

        // Validate that date is not in future
        $today = new DateTime();
        $today->setTime(23, 59, 59);
        $dateObj->setTime(0, 0, 0);
        if ($dateObj > $today) {
            throw new Exception("Purchase date cannot be in the future");
        }

        // Format the date for MySQL
        $purchaseDate = $dateObj->format('Y-m-d');
        
        // Get form data
        $purchaseId = intval($_POST['purchaseId']);
        $supplierId = intval($_POST['supplier']);
        $warehouseId = isset($_POST['warehouse']) ? intval($_POST['warehouse']) : null;
        $subTotal = floatval($_POST['subTotalValue']);
        $vat = floatval($_POST['vat']);
        $vatAmount = ($subTotal * $vat) / 100;
        $withholdingEnabled = isset($_POST['withholding_enabled']) ? 1 : 0;
        $withholdingAmount = $withholdingEnabled ? floatval($_POST['withholding_amount']) : 0;
        $grandTotal = floatval($_POST['grandTotalValue']);
        $note = isset($_POST['note']) ? $_POST['note'] : '';

        // Update purchase header
        $sql = "UPDATE purchases SET 
                supplier_id = ?,
                purchase_date = ?,
                warehouse_id = ?,
                sub_total = ?,
                vat = ?,
                vat_amount = ?,
                withholding_tax_enabled = ?,
                withholding_tax_amount = ?,
                grand_total = ?,
                note = ?,
                updated_at = NOW()
                WHERE id = ?";

        $stmt = $connect->prepare($sql);
        if(!$stmt) {
            throw new Exception("Error preparing purchase update: " . $connect->error);
        }

        $stmt->bind_param("isiddddddss", 
            $supplierId,
            $purchaseDate,
            $warehouseId,
            $subTotal,
            $vat,
            $vatAmount,
            $withholdingEnabled,
            $withholdingAmount,
            $grandTotal,
            $note,
            $purchaseId
        );

        if(!$stmt->execute()) {
            throw new Exception("Error updating purchase: " . $stmt->error);
        }

        // Delete existing purchase items and their movements
        $sql = "DELETE FROM purchase_items WHERE purchase_id = ?";
        $stmt = $connect->prepare($sql);
        if(!$stmt) {
            throw new Exception("Error preparing delete items query: " . $connect->error);
        }
        
        $stmt->bind_param("i", $purchaseId);
        if(!$stmt->execute()) {
            throw new Exception("Error deleting old purchase items: " . $stmt->error);
        }

        // Get and reverse previous stock movements
        $movementSql = "SELECT material_id, quantity, warehouse_id FROM raw_material_movements 
                       WHERE reference_type = 'purchase' AND reference_id = ?";
        $movementStmt = $connect->prepare($movementSql);
        $movementStmt->bind_param("i", $purchaseId);
        $movementStmt->execute();
        $previousMovements = $movementStmt->get_result();

        // Record reversal movements and update current stock
        $reversalSql = "INSERT INTO raw_material_movements 
                       (material_id, movement_type, quantity, reference_type, reference_id, notes, warehouse_id, destination_location_id, created_by) 
                       VALUES (?, 'out', ?, 'purchase_reversal', ?, ?, ?, ?, ?)";
        $reversalStmt = $connect->prepare($reversalSql);

        // Prepare statement for updating raw materials stock
        $updateStockSql = "UPDATE raw_materials SET current_stock = current_stock - ? WHERE id = ?";
        $updateStockStmt = $connect->prepare($updateStockSql);

        while ($prevMove = $previousMovements->fetch_assoc()) {
            $reversalNote = "Reversal of Purchase - ID: $purchaseId";
            $reversalStmt->bind_param("idisiii",
                $prevMove['material_id'],
                $prevMove['quantity'],
                $purchaseId,
                $reversalNote,
                $prevMove['warehouse_id'],
                $defaultLocationId,
                $_SESSION['userId']
            );
            $reversalStmt->execute();

            // Update current stock by subtracting the reversed quantity
            $updateStockStmt->bind_param("di", $prevMove['quantity'], $prevMove['material_id']);
            $updateStockStmt->execute();
        }

        // Delete old movements
        $deleteMovementsSql = "DELETE FROM raw_material_movements 
                             WHERE reference_type = 'purchase' AND reference_id = ?";
        $deleteMovementsStmt = $connect->prepare($deleteMovementsSql);
        $deleteMovementsStmt->bind_param("i", $purchaseId);
        $deleteMovementsStmt->execute();

        // Process purchase items
        if(isset($_POST['productName']) && is_array($_POST['productName'])) {
            $productNames = $_POST['productName'];
            $quantities = $_POST['quantity'];
            $rates = $_POST['rate'];

            if(empty($productNames)) {
                throw new Exception("No products provided");
            }

            // Set default location_id
            $defaultLocationId = 1;

            // Prepare statements for items and movements
            $itemSql = "INSERT INTO purchase_items (purchase_id, raw_material_id, warehouse_id, location_id, quantity, rate, total) 
                       VALUES (?, ?, ?, ?, ?, ?, ?)";
            $itemStmt = $connect->prepare($itemSql);

            $movementSql = "INSERT INTO raw_material_movements 
                           (material_id, movement_type, quantity, reference_type, reference_id, notes, warehouse_id, destination_location_id, created_by) 
                           VALUES (?, 'in', ?, 'purchase', ?, ?, ?, ?, ?)";
            $movementStmt = $connect->prepare($movementSql);

            foreach($productNames as $index => $productId) {
                if(empty($productId)) continue;

                $quantity = floatval($quantities[$index]);
                $rate = floatval($rates[$index]);
                $total = $quantity * $rate;

                // Insert purchase item
                $itemStmt->bind_param("iiidddd",
                    $purchaseId,
                    $productId,
                    $warehouseId,
                    $defaultLocationId,
                    $quantity,
                    $rate,
                    $total
                );

                if(!$itemStmt->execute()) {
                    throw new Exception("Error adding purchase item: " . $itemStmt->error);
                }

                // Record movement
                $movementNote = "Purchase Update - ID: $purchaseId";
                $movementStmt->bind_param("idisiii",
                    $productId,
                    $quantity,
                    $purchaseId,
                    $movementNote,
                    $warehouseId,
                    $defaultLocationId,
                    $_SESSION['userId']
                );

                if(!$movementStmt->execute()) {
                    throw new Exception("Error updating stock movement: " . $movementStmt->error);
                }

                // Update current stock by adding the new quantity
                $updateStockSql = "UPDATE raw_materials SET current_stock = current_stock + ? WHERE id = ?";
                $updateStockStmt = $connect->prepare($updateStockSql);
                $updateStockStmt->bind_param("di", $quantity, $productId);
                if(!$updateStockStmt->execute()) {
                    throw new Exception("Error updating material stock: " . $updateStockStmt->error);
                }
            }
        }

        $connect->commit();
        
        $response['success'] = true;
        $response['messages'] = "Purchase successfully updated";

    } catch(Exception $e) {
        $connect->rollback();
        $response['success'] = false;
        $response['messages'] = $e->getMessage();
        error_log("Error in editPurchase.php: " . $e->getMessage());
    }
}

echo json_encode($response);

// Close connection
if(isset($connect)) {
    $connect->close();
} 