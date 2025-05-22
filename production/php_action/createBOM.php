<?php
require_once 'core.php';
require_once 'db_connect.php';

// Clear any previous output that might corrupt JSON
ob_clean();
header('Content-Type: application/json');

// Start transaction
$connect->begin_transaction();

try {
    // Get form data
    $productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $rawMaterialIds = isset($_POST['raw_material_id']) ? $_POST['raw_material_id'] : array();
    $quantities = isset($_POST['quantity']) ? $_POST['quantity'] : array();
    $wastages = isset($_POST['wastage']) ? $_POST['wastage'] : array();
    $userId = isset($_SESSION['userId']) ? $_SESSION['userId'] : null;

    // Debug log
    error_log("Received data - Product ID: " . $productId);
    error_log("Raw Materials: " . print_r($rawMaterialIds, true));
    error_log("Quantities: " . print_r($quantities, true));
    error_log("Wastages: " . print_r($wastages, true));

    // Validate inputs
    if (empty($productId)) {
        throw new Exception("Product selection is required");
    }

    if (empty($rawMaterialIds) || empty($quantities) || empty($wastages)) {
        throw new Exception("Materials, quantities, and wastage percentages are required");
    }

    if (count($rawMaterialIds) !== count($quantities) || count($rawMaterialIds) !== count($wastages)) {
        throw new Exception("Mismatch in materials, quantities, or wastage data");
    }

    // Check for duplicate materials in the input
    $uniqueMaterials = array_unique($rawMaterialIds);
    if (count($uniqueMaterials) !== count($rawMaterialIds)) {
        throw new Exception("Duplicate materials are not allowed in the same BOM");
    }

    // Check if product exists and is active
    $productCheck = $connect->prepare("SELECT id FROM production_products WHERE id = ? AND status = 'active'");
    if (!$productCheck) {
        throw new Exception("Database error while checking product: " . $connect->error);
    }
    $productCheck->bind_param("i", $productId);
    $productCheck->execute();
    if ($productCheck->get_result()->num_rows === 0) {
        throw new Exception("Selected product is not valid or active");
    }
    $productCheck->close();

    // Check if BOM already exists for this product
    $bomCheck = $connect->prepare("SELECT id FROM product_bom WHERE product_id = ? AND status = 'active'");
    if (!$bomCheck) {
        throw new Exception("Database error while checking existing BOM: " . $connect->error);
    }
    $bomCheck->bind_param("i", $productId);
    $bomCheck->execute();
    if ($bomCheck->get_result()->num_rows > 0) {
        throw new Exception("An active BOM already exists for this product");
    }
    $bomCheck->close();

    // Prepare insert statement
    $insertStmt = $connect->prepare("
        INSERT INTO product_bom (
            product_id,
            material_id,
            quantity_required,
            wastage_percent,
            status,
            created_at,
            updated_at,
            created_by
        ) VALUES (?, ?, ?, ?, 'active', CURRENT_TIMESTAMP(), CURRENT_TIMESTAMP(), ?)
    ");

    if (!$insertStmt) {
        throw new Exception("Failed to prepare insert statement: " . $connect->error);
    }

    // Insert each material
    foreach ($rawMaterialIds as $index => $materialId) {
        // Skip empty entries
        if (empty($materialId)) continue;

        // Validate material
        $materialCheck = $connect->prepare("SELECT id FROM raw_materials WHERE id = ? AND status = 'active'");
        if (!$materialCheck) {
            throw new Exception("Database error while checking material: " . $connect->error);
        }
        $materialCheck->bind_param("i", $materialId);
        $materialCheck->execute();
        if ($materialCheck->get_result()->num_rows === 0) {
            throw new Exception("Selected material (ID: $materialId) is not valid or active");
        }
        $materialCheck->close();

        $quantity = floatval($quantities[$index]);
        if ($quantity <= 0) {
            throw new Exception("Quantity must be greater than 0 for all materials");
        }

        $wastage = floatval($wastages[$index]);
        if ($wastage < 0 || $wastage > 100) {
            throw new Exception("Wastage percentage must be between 0 and 100");
        }

        // Insert BOM entry
        if (!$insertStmt->bind_param("iiddi", $productId, $materialId, $quantity, $wastage, $userId)) {
            throw new Exception("Error binding parameters: " . $insertStmt->error);
        }
        
        if (!$insertStmt->execute()) {
            throw new Exception("Error inserting BOM entry: " . $insertStmt->error . " SQL State: " . $insertStmt->sqlstate);
        }

        // Log successful insertion
        error_log("Successfully inserted BOM entry - Material ID: $materialId, Quantity: $quantity, Wastage: $wastage");
    }

    $insertStmt->close();

    // If we get here, commit the transaction
    $connect->commit();

    echo json_encode(array(
        'success' => true,
        'messages' => 'Bill of Materials created successfully'
    ));

} catch (Exception $e) {
    // Rollback the transaction on error
    $connect->rollback();
    
    error_log("Error in createBOM.php: " . $e->getMessage());
    
    echo json_encode(array(
        'success' => false,
        'messages' => $e->getMessage()
    ));
}

// Close the database connection
$connect->close(); 