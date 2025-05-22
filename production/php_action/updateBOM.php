<?php
require_once 'core.php';
require_once 'db_connect.php';

// Clear any previous output that might corrupt JSON
while (ob_get_level()) ob_end_clean();
header('Content-Type: application/json');

// Start transaction
$connect->begin_transaction();

try {
    // Get form data
    $bomId = isset($_POST['bom_id']) ? intval($_POST['bom_id']) : 0;
    $productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $materials = isset($_POST['materials']) ? $_POST['materials'] : array();
    $quantities = isset($_POST['quantities']) ? $_POST['quantities'] : array();
    $wastages = isset($_POST['wastage']) ? $_POST['wastage'] : array();
    $userId = isset($_SESSION['userId']) ? $_SESSION['userId'] : null;

    // Validate inputs
    if (empty($bomId) || empty($productId)) {
        throw new Exception("BOM ID and Product ID are required");
    }

    if (empty($materials) || empty($quantities) || empty($wastages)) {
        throw new Exception("Materials, quantities, and wastage percentages are required");
    }

    if (count($materials) !== count($quantities) || count($materials) !== count($wastages)) {
        throw new Exception("Mismatch in materials, quantities, or wastage data");
    }

    // Check for duplicate materials in the input
    $uniqueMaterials = array_unique($materials);
    if (count($uniqueMaterials) !== count($materials)) {
        throw new Exception("Duplicate materials are not allowed in the same BOM");
    }

    // First, get all existing BOM entries for this product
    $existingBomStmt = $connect->prepare("
        SELECT id, material_id 
        FROM product_bom 
        WHERE product_id = ? AND status = 'active'
    ");
    if (!$existingBomStmt) {
        throw new Exception("Failed to prepare existing BOM query");
    }
    $existingBomStmt->bind_param("i", $productId);
    $existingBomStmt->execute();
    $existingResult = $existingBomStmt->get_result();
    
    // Create a map of existing material entries
    $existingMaterials = array();
    while ($row = $existingResult->fetch_assoc()) {
        $existingMaterials[$row['material_id']] = $row['id'];
    }
    $existingBomStmt->close();

    // Prepare update statement for existing entries
    $updateStmt = $connect->prepare("
        UPDATE product_bom 
        SET quantity_required = ?,
            wastage_percent = ?,
            updated_at = NOW()
        WHERE id = ? AND product_id = ? AND material_id = ?
    ");

    // Prepare insert statement for new entries
    $insertStmt = $connect->prepare("
        INSERT INTO product_bom (
            product_id,
            material_id,
            quantity_required,
            wastage_percent,
            status,
            created_by,
            created_at
        ) VALUES (?, ?, ?, ?, 'active', ?, NOW())
    ");

    // Track which materials are processed
    $processedMaterials = array();

    // Process each material
    foreach ($materials as $index => $materialId) {
        // Validate material
        if (empty($materialId)) {
            throw new Exception("Material selection is required for all rows");
        }

        $quantity = floatval($quantities[$index]);
        if ($quantity <= 0) {
            throw new Exception("Quantity must be greater than 0 for all materials");
        }

        $wastage = floatval($wastages[$index]);
        if ($wastage < 0 || $wastage > 100) {
            throw new Exception("Wastage percentage must be between 0 and 100");
        }

        // Check if material exists and is active
        $materialCheck = $connect->prepare("SELECT id FROM raw_materials WHERE id = ? AND status = 'active'");
        if (!$materialCheck) {
            throw new Exception("Database error while checking material");
        }
        $materialCheck->bind_param("i", $materialId);
        $materialCheck->execute();
        if ($materialCheck->get_result()->num_rows === 0) {
            throw new Exception("Selected material is not valid or active");
        }
        $materialCheck->close();

        // If material exists in current BOM, update it
        if (isset($existingMaterials[$materialId])) {
            $bomEntryId = $existingMaterials[$materialId];
            $updateStmt->bind_param("ddiis", $quantity, $wastage, $bomEntryId, $productId, $materialId);
            if (!$updateStmt->execute()) {
                throw new Exception("Error updating BOM entry: " . $updateStmt->error);
            }
        } else {
            // Insert new BOM entry
            $insertStmt->bind_param("iiddi", $productId, $materialId, $quantity, $wastage, $userId);
            if (!$insertStmt->execute()) {
                throw new Exception("Error inserting BOM entry: " . $insertStmt->error);
            }
        }

        $processedMaterials[] = $materialId;
    }

    // Deactivate any existing materials that weren't in the update
    $materialsToDeactivate = array_diff(array_keys($existingMaterials), $processedMaterials);
    if (!empty($materialsToDeactivate)) {
        $deactivateStmt = $connect->prepare("
            UPDATE product_bom 
            SET status = 'inactive', 
                updated_at = NOW()
            WHERE product_id = ? 
            AND material_id IN (" . implode(',', $materialsToDeactivate) . ")
            AND status = 'active'
        ");
        
        if (!$deactivateStmt) {
            throw new Exception("Failed to prepare deactivate statement");
        }
        
        $deactivateStmt->bind_param("i", $productId);
        if (!$deactivateStmt->execute()) {
            throw new Exception("Error deactivating old BOM entries: " . $deactivateStmt->error);
        }
        $deactivateStmt->close();
    }

    if (isset($updateStmt)) $updateStmt->close();
    if (isset($insertStmt)) $insertStmt->close();

    // If we get here, commit the transaction
    $connect->commit();

    echo json_encode(array(
        'success' => true,
        'messages' => 'Bill of Materials updated successfully'
    ));

} catch (Exception $e) {
    // Rollback the transaction on error
    $connect->rollback();
    
    error_log("Error in updateBOM.php: " . $e->getMessage());
    
    echo json_encode(array(
        'success' => false,
        'messages' => $e->getMessage()
    ));
}

// Close the database connection
$connect->close(); 