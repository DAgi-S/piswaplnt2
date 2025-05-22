<?php
require_once 'core.php';
require_once 'db_connect.php';

// Set Content Type
header('Content-Type: application/json');

// Default response
$response = array(
    'success' => false,
    'data' => array(),
    'messages' => array()
);

try {
    // Get warehouse ID from request
    $warehouseId = isset($_GET['warehouse_id']) ? intval($_GET['warehouse_id']) : 0;
    
    if ($warehouseId <= 0) {
        throw new Exception('Invalid warehouse ID');
    }

    // Verify warehouse exists and is active
    $warehouseCheck = $connect->prepare("SELECT id FROM warehouses WHERE id = ? AND status = 1");
    $warehouseCheck->bind_param('i', $warehouseId);
    $warehouseCheck->execute();
    if ($warehouseCheck->get_result()->num_rows === 0) {
        throw new Exception('Invalid or inactive warehouse');
    }
    $warehouseCheck->close();

    // Get VAT rate
    $vatRate = 15; // Default VAT rate
    $taxResult = $connect->query("SELECT rate FROM tax_rates WHERE name LIKE '%VAT%' AND status = 1 LIMIT 1");
    if ($taxResult && $taxResult->num_rows > 0) {
        $vatRate = floatval($taxResult->fetch_assoc()['rate']);
    }

    // Get products with stock information
    $sql = "SELECT 
                p.id,
                p.product_code,
                p.name,
                p.selling_price,
                p.unit,
                p.min_stock_level,
                COALESCE(ws.quantity, p.current_stock) as warehouse_stock,
                c.name as category_name,
                b.name as brand_name,
                CASE 
                    WHEN ws.quantity IS NOT NULL THEN ws.quantity
                    WHEN p.current_stock IS NOT NULL THEN p.current_stock
                    ELSE 0 
                END as actual_stock
            FROM production_products p
            LEFT JOIN warehouse_stock ws ON ws.item_id = p.id 
                AND ws.warehouse_id = ?
                AND ws.item_type = 'finished_good'
            LEFT JOIN production_categories c ON p.category_id = c.id
            LEFT JOIN production_brands b ON p.brand_id = b.id
            WHERE p.status = 1
            HAVING actual_stock > 0
            ORDER BY p.name ASC";

    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error preparing query: " . $connect->error);
    }

    $stmt->bind_param('i', $warehouseId);
    if (!$stmt->execute()) {
        throw new Exception("Error executing query: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $products = array();

    while ($row = $result->fetch_assoc()) {
        $warehouseStock = floatval($row['actual_stock']);
        $minStock = floatval($row['min_stock_level']);
        
        // Format display name
        $displayName = $row['name'];
        if (!empty($row['product_code'])) {
            $displayName .= ' (' . $row['product_code'] . ')';
        }
        if (!empty($row['brand_name'])) {
            $displayName .= ' - ' . $row['brand_name'];
        }

        // Add stock info to display name
        $displayName .= ' [Stock: ' . number_format($warehouseStock, 2) . ' ' . ($row['unit'] ?: 'units') . ']';

        $products[] = array(
            'id' => $row['id'],
            'name' => $displayName,
            'product_code' => $row['product_code'],
            'category' => $row['category_name'],
            'brand' => $row['brand_name'],
            'unit' => $row['unit'],
            'selling_price' => number_format(floatval($row['selling_price']), 2, '.', ''),
            'current_stock' => number_format($warehouseStock, 2, '.', ''),
            'min_stock_level' => number_format($minStock, 2, '.', ''),
            'vat_rate' => $vatRate,
            'has_stock' => true
        );
    }

    $stmt->close();
    
    $response['success'] = true;
    $response['data'] = $products;

} catch (Exception $e) {
    $response['messages'][] = $e->getMessage();
}

$connect->close();
echo json_encode($response); 