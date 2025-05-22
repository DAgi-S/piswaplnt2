<?php
require_once 'core.php';
require_once 'db_connect.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Clear any previous output
while (ob_get_level()) ob_end_clean();

header('Content-Type: application/json');

try {
    // Base query with CASE statements to handle both product and raw material types
    $sql = "WITH StockMovements AS (
        SELECT 
            id,
            created_at,
            item_type,
            item_id,
            quantity,
            movement_type,
            reference_type,
            reference_id,
            notes,
            created_by,
            SUM(CASE 
                WHEN movement_type = 'in' THEN quantity 
                WHEN movement_type = 'out' THEN -quantity
            END) OVER (
                PARTITION BY item_type, item_id 
                ORDER BY created_at ASC
            ) as current_stock
        FROM inventory_movement_log
        WHERE status = 'completed'
    )
    SELECT 
        sm.id,
        sm.created_at,
        sm.quantity,
        sm.movement_type,
        sm.reference_type,
        sm.reference_id,
        sm.notes,
        sm.item_type,
        sm.item_id,
        CASE 
            WHEN sm.item_type = 'raw_material' THEN rm.name
            WHEN sm.item_type = 'product' THEN p.name
        END as item_name,
        CASE 
            WHEN sm.item_type = 'raw_material' THEN rm.material_code
            WHEN sm.item_type = 'product' THEN p.product_code
        END as item_code,
        CASE 
            WHEN sm.item_type = 'raw_material' THEN rm.unit
            WHEN sm.item_type = 'product' THEN p.unit
        END as unit,
        COALESCE(sm.current_stock, 0) as current_stock,
        u.username as created_by
    FROM StockMovements sm
    LEFT JOIN products p ON sm.item_id = p.product_id AND sm.item_type = 'product'
    LEFT JOIN raw_materials rm ON sm.item_id = rm.id AND sm.item_type = 'raw_material'
    LEFT JOIN users u ON sm.created_by = u.user_id
    ORDER BY sm.created_at DESC";

    $result = $connect->query($sql);

    if (!$result) {
        throw new Exception("Query failed: " . $connect->error);
    }

    // Fetch all records
    $data = array();
    while ($row = $result->fetch_assoc()) {
        $itemDisplay = $row['item_code'] . ' - ' . $row['item_name'];
        if ($row['unit']) {
            $itemDisplay .= ' (' . $row['unit'] . ')';
        }

        // Format the quantity with sign based on movement type
        $quantity = $row['quantity'];
        $quantityDisplay = number_format(abs($quantity), 2);
        if ($row['unit']) {
            $quantityDisplay .= ' ' . $row['unit'];
        }

        // Format current stock
        $currentStock = number_format($row['current_stock'], 2);
        if ($row['unit']) {
            $currentStock .= ' ' . $row['unit'];
        }

        $data[] = array(
            'id' => $row['id'],
            'created_at' => $row['created_at'],
            'item_name' => $itemDisplay,
            'quantity' => $quantityDisplay,
            'movement_type' => $row['movement_type'],
            'reference_type' => $row['reference_type'],
            'reference_id' => $row['reference_id'],
            'notes' => $row['notes'],
            'created_by' => $row['created_by'],
            'current_stock' => $currentStock
        );
    }

    // Return the response in DataTables format
    echo json_encode(array(
        'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 1,
        'recordsTotal' => count($data),
        'recordsFiltered' => count($data),
        'data' => $data
    ));

} catch (Exception $e) {
    error_log("Error in fetchStockMovements.php: " . $e->getMessage());
    echo json_encode(array(
        'error' => true,
        'message' => $e->getMessage(),
        'data' => array()
    ));
}

$connect->close();
?> 