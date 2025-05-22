<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Fix the include path
$root = realpath(dirname(__FILE__) . '/..');
require_once $root . '/includes/db_connect.php';

// Set proper headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

try {
    // Check database connection
    if (!$connect) {
        throw new Exception("Database connection failed");
    }

    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate product ID
    if (!isset($_POST['product_id']) || !is_numeric($_POST['product_id'])) {
        throw new Exception('Valid product ID is required');
    }

    // Get product details
    $productSql = "SELECT p.id, p.product_code, p.name as product_name
                  FROM production_products p
                  WHERE p.id = ?";
    
    $productStmt = $connect->prepare($productSql);
    if (!$productStmt->execute([$_POST['product_id']])) {
        throw new Exception("Failed to fetch product details");
    }

    $product = $productStmt->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        throw new Exception("Product not found");
    }

    // Get BOM items with raw material costs
    $sql = "SELECT 
                b.id,
                b.quantity_required as quantity,
                b.wastage_percent as wastage,
                r.name as raw_material_name,
                r.unit,
                r.cost_per_unit as unit_cost,
                r.current_stock
            FROM product_bom b
            JOIN raw_materials r ON b.material_id = r.id
            WHERE b.product_id = ? AND b.status = 'active'
            ORDER BY r.name ASC";

    $stmt = $connect->prepare($sql);
    if (!$stmt->execute([$_POST['product_id']])) {
        throw new Exception("Failed to fetch BOM items");
    }

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate costs and prepare items array
    $total_cost = 0;
    foreach ($items as &$item) {
        // Calculate quantity including wastage
        $quantity_with_wastage = $item['quantity'] * (1 + ($item['wastage'] / 100));
        $item['quantity'] = $quantity_with_wastage;
        
        // Calculate total cost for this item
        $item['total_cost'] = $quantity_with_wastage * $item['unit_cost'];
        $total_cost += $item['total_cost'];
        
        // Format numbers
        $item['quantity'] = number_format($item['quantity'], 2);
        $item['unit_cost'] = number_format($item['unit_cost'], 2);
        $item['total_cost'] = number_format($item['total_cost'], 2);
    }

    // Prepare response data
    $response = [
        'success' => true,
        'data' => [
            'product_id' => $product['id'],
            'product_code' => $product['product_code'],
            'product_name' => $product['product_name'],
            'items' => $items,
            'total_cost' => number_format($total_cost, 2)
        ]
    ];

    // Clear any previous output
    while (ob_get_level()) {
        ob_end_clean();
    }

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error in generateBOMInvoice.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Clear any previous output
    while (ob_get_level()) {
        ob_end_clean();
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'messages' => $e->getMessage()
    ]);
} 