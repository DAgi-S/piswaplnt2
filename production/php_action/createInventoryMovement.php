<?php
require_once 'core.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Set JSON header
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('success' => false, 'messages' => 'Invalid request method'));
    exit();
}

try {
    // Validate required inputs
    $requiredFields = ['item_type', 'item_id', 'source_type', 'source_id', 
                      'destination_type', 'destination_id', 'quantity', 
                      'movement_type', 'reference_type', 'reference_id'];
    
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Field {$field} is required");
        }
    }

    // Get and validate inputs
    $itemType = $_POST['item_type'];
    $itemId = intval($_POST['item_id']);
    $sourceType = $_POST['source_type'];
    $sourceId = intval($_POST['source_id']);
    $destinationType = $_POST['destination_type'];
    $destinationId = intval($_POST['destination_id']);
    $quantity = floatval($_POST['quantity']);
    $movementType = $_POST['movement_type'];
    $referenceType = $_POST['reference_type'];
    $referenceId = intval($_POST['reference_id']);
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    $status = isset($_POST['status']) ? $_POST['status'] : 'completed';

    if ($quantity <= 0) {
        throw new Exception("Quantity must be greater than 0");
    }

    // Validate item exists
    $itemQuery = "";
    switch ($itemType) {
        case 'raw_material':
            $itemQuery = "SELECT id, current_stock FROM raw_materials WHERE id = ? AND status = 'active'";
            break;
        case 'finished_good':
            $itemQuery = "SELECT id, current_stock FROM production_products WHERE id = ? AND status = 'active'";
            break;
        case 'product':
            $itemQuery = "SELECT product_id as id, quantity as current_stock FROM products WHERE product_id = ? AND status = 1";
            break;
        default:
            throw new Exception("Invalid item type");
    }

    $stmt = $connect->prepare($itemQuery);
    $stmt->bind_param("i", $itemId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Item not found or inactive");
    }

    $item = $result->fetch_assoc();

    // Validate source location
    if ($sourceType === 'warehouse') {
        $sourceQuery = "SELECT ws.quantity, w.type 
                       FROM warehouse_stock ws 
                       JOIN warehouses w ON ws.warehouse_id = w.id 
                       WHERE ws.warehouse_id = ? 
                       AND ws.item_type = ? 
                       AND ws.item_id = ?
                       AND w.status = 'active'";
        
        $stmt = $connect->prepare($sourceQuery);
        $stmt->bind_param("isi", $sourceId, $itemType, $itemId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Item not found in source warehouse");
        }

        $sourceStock = $result->fetch_assoc();
        if ($sourceStock['quantity'] < $quantity) {
            throw new Exception("Insufficient stock in source warehouse");
        }
    }

    // Validate destination location
    if ($destinationType === 'warehouse') {
        $destQuery = "SELECT type FROM warehouses WHERE id = ? AND status = 'active'";
        $stmt = $connect->prepare($destQuery);
        $stmt->bind_param("i", $destinationId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Destination warehouse not found or inactive");
        }

        $warehouse = $result->fetch_assoc();
        if ($warehouse['type'] !== 'both' && 
            (($itemType === 'raw_material' && $warehouse['type'] !== 'raw_material') ||
             ($itemType === 'finished_good' && $warehouse['type'] !== 'finished_good'))) {
            throw new Exception("Destination warehouse cannot store this type of item");
        }
    }

    // Start transaction
    $connect->begin_transaction();

    try {
        // Create inventory movement log entry
        $sql = "INSERT INTO inventory_movement_log (
                    item_type, item_id, source_type, source_id,
                    destination_type, destination_id, quantity,
                    movement_type, reference_type, reference_id,
                    notes, status, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $connect->prepare($sql);
        $userId = isset($_SESSION['userId']) ? $_SESSION['userId'] : null;
        $stmt->bind_param("sisisisssissi", 
            $itemType, $itemId, $sourceType, $sourceId,
            $destinationType, $destinationId, $quantity,
            $movementType, $referenceType, $referenceId,
            $notes, $status, $userId
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to create inventory movement record");
        }

        // Commit transaction
        $connect->commit();

        echo json_encode(array(
            'success' => true,
            'messages' => 'Inventory movement created successfully'
        ));

    } catch (Exception $e) {
        $connect->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error in createInventoryMovement.php: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'messages' => $e->getMessage()
    ));
}

// Close the connection
if (isset($connect)) {
    $connect->close(); 
}