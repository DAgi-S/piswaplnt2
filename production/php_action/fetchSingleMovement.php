<?php
require_once '../includes/db_connect.php';

// Set proper content type
header('Content-Type: application/json');

if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(array(
        'success' => false,
        'message' => 'Movement ID is required'
    ));
    exit();
}

try {
    // Base query
    $sql = "SELECT 
                iml.*,
                CASE 
                    WHEN iml.item_type = 'raw_material' THEN rm.name
                    WHEN iml.item_type = 'finished_good' THEN p.name
                    ELSE 'Unknown'
                END as item_name
            FROM inventory_movement_log iml
            LEFT JOIN raw_materials rm ON iml.item_type = 'raw_material' AND iml.item_id = rm.id
            LEFT JOIN production_products p ON iml.item_type IN ('finished_good', 'product') AND iml.item_id = p.id
            WHERE iml.id = ?";

    // Prepare and execute the query
    $stmt = $connect->prepare($sql);
    $stmt->execute(array($_POST['id']));
    $movement = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$movement) {
        echo json_encode(array(
            'success' => false,
            'message' => 'Movement not found'
        ));
        exit();
    }

    // Get source name
    $sourceName = '';
    switch ($movement['source_type']) {
        case 'warehouse':
            $sourceStmt = $connect->prepare("SELECT name FROM warehouses WHERE id = ?");
            $sourceStmt->execute(array($movement['source_id']));
            $sourceResult = $sourceStmt->fetch(PDO::FETCH_ASSOC);
            $sourceName = $sourceResult ? $sourceResult['name'] : 'Unknown';
            break;
        case 'supplier':
            $sourceStmt = $connect->prepare("SELECT company_name as name FROM suppliers WHERE id = ?");
            $sourceStmt->execute(array($movement['source_id']));
            $sourceResult = $sourceStmt->fetch(PDO::FETCH_ASSOC);
            $sourceName = $sourceResult ? $sourceResult['name'] : 'Unknown';
            break;
        // Add more cases as needed
    }
    $movement['source_name'] = $sourceName;

    // Get destination name
    $destinationName = '';
    switch ($movement['destination_type']) {
        case 'warehouse':
            $destStmt = $connect->prepare("SELECT name FROM warehouses WHERE id = ?");
            $destStmt->execute(array($movement['destination_id']));
            $destResult = $destStmt->fetch(PDO::FETCH_ASSOC);
            $destinationName = $destResult ? $destResult['name'] : 'Unknown';
            break;
        case 'production':
            $destStmt = $connect->prepare("SELECT order_number as name FROM production_orders WHERE id = ?");
            $destStmt->execute(array($movement['destination_id']));
            $destResult = $destStmt->fetch(PDO::FETCH_ASSOC);
            $destinationName = $destResult ? 'Production #' . $destResult['name'] : 'Unknown';
            break;
        // Add more cases as needed
    }
    $movement['destination_name'] = $destinationName;

    echo json_encode(array(
        'success' => true,
        'data' => $movement
    ));

} catch (PDOException $e) {
    // Log the error and return a generic error message
    error_log('Database error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => 'An error occurred while fetching movement details'
    ));
} 