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
    // Get parameters from request
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $warehouseId = isset($_GET['warehouse_id']) ? intval($_GET['warehouse_id']) : null;

    // Base query for products
    $sql = "SELECT 
                p.product_id as id,
                p.product_code as code,
                p.name,
                p.unit,
                COALESCE(p.current_stock, 0) as total_stock,
                COALESCE(ws.quantity, 0) as warehouse_stock
            FROM products p
            LEFT JOIN warehouse_stock ws ON ws.item_id = p.product_id";
    
    if ($warehouseId) {
        $sql .= " AND ws.warehouse_id = " . intval($warehouseId);
    }
    
    $sql .= " WHERE p.status = 'active'";
    
    // Add search condition if search term exists
    if (!empty($search)) {
        $search = $connect->real_escape_string($search);
        $sql .= " AND (p.product_code LIKE '%{$search}%' OR p.name LIKE '%{$search}%')";
    }
    
    $sql .= " ORDER BY p.name ASC LIMIT 10";

    $result = $connect->query($sql);
    
    if (!$result) {
        throw new Exception("Database error: " . $connect->error);
    }

    $items = array();
    while ($row = $result->fetch_assoc()) {
        $stockValue = $warehouseId ? $row['warehouse_stock'] : $row['total_stock'];
        $unit = $row['unit'] ? ' ' . $row['unit'] : '';
        
        $items[] = array(
            'id' => $row['id'],
            'text' => $row['code'] . ' - ' . $row['name'],
            'code' => $row['code'],
            'name' => $row['name'],
            'current_stock' => floatval($stockValue),
            'warehouse_stock' => floatval($row['warehouse_stock']),
            'total_stock' => floatval($row['total_stock']),
            'unit' => trim($unit)
        );
    }

    echo json_encode($items);

} catch (Exception $e) {
    error_log("Error in fetchItems.php: " . $e->getMessage());
    echo json_encode([
        array(
            'id' => '',
            'text' => 'Error: ' . $e->getMessage(),
            'disabled' => true
        )
    ]);
}

$connect->close();
?>