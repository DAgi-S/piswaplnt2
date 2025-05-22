<?php
require_once 'core.php';
require_once 'db_connect.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Ensure clean output
ob_start();

try {
    // Get warehouse ID from request
    $warehouseId = isset($_POST['warehouseId']) ? intval($_POST['warehouseId']) : 0;

    if (!$warehouseId) {
        throw new Exception("Invalid warehouse ID");
    }

    // Prepare the SQL query
    $sql = "SELECT 
                ws.id,
                ws.item_type,
                ws.item_id,
                ws.quantity,
                CASE 
                    WHEN ws.item_type = 'raw_material' THEN rm.material_code 
                    WHEN ws.item_type = 'finished_good' THEN p.product_code 
                END as item_code,
                CASE 
                    WHEN ws.item_type = 'raw_material' THEN rm.name 
                    WHEN ws.item_type = 'finished_good' THEN p.name 
                END as item_name,
                CASE 
                    WHEN ws.item_type = 'raw_material' THEN rm.unit 
                    WHEN ws.item_type = 'finished_good' THEN p.unit 
                END as unit,
                ws.quantity as quantity
            FROM warehouse_stock ws
            LEFT JOIN raw_materials rm ON ws.item_id = rm.id AND ws.item_type = 'raw_material'
            LEFT JOIN products p ON ws.item_id = p.product_id AND ws.item_type = 'finished_good'
            WHERE ws.warehouse_id = ?";

    $stmt = $connect->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Query preparation failed: " . $connect->error);
    }

    $stmt->bind_param("i", $warehouseId);
    
    if (!$stmt->execute()) {
        throw new Exception("Query execution failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    
    $data = array();
    
    while ($row = $result->fetch_assoc()) {
        $data[] = array(
            "id" => $row['id'],
            "item_id" => $row['item_id'],
            "item_type" => $row['item_type'],
            "item_code" => $row['item_code'] ?? '',
            "item_name" => $row['item_name'] ?? '',
            "type" => $row['item_type'] ?? '',
            "unit" => $row['unit'] ?? '',
            "quantity" => number_format($row['quantity'], 2)
        );
    }

    // Clear any previous output
    ob_clean();

    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode(array("data" => $data));

} catch (Exception $e) {
    // Log error
    error_log("Warehouse Stock Error: " . $e->getMessage());
    
    // Clear any previous output
    ob_clean();
    
    // Send error response
    header('Content-Type: application/json');
    echo json_encode(array(
        "error" => true,
        "message" => "Error loading stock data: " . $e->getMessage()
    ));
}

// Close database connection
$stmt->close();
$connect->close(); 