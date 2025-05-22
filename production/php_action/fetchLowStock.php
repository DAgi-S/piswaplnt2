<?php
// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/error.log');

// Basic headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'core.php';

try {
    // Debug database connection
    if (!isset($connect)) {
        throw new Exception("Database connection not initialized");
    }

    // Test connection
    if ($connect->connect_error) {
        throw new Exception("Connection failed: " . $connect->connect_error);
    }

    // First check if tables exist
    $tables_check = $connect->query("SHOW TABLES LIKE 'products'");
    if (!$tables_check) {
        throw new Exception("Cannot check tables: " . $connect->error);
    }
    if ($tables_check->num_rows === 0) {
        throw new Exception("Required tables not found");
    }

    // Get products with low stock
    $products_sql = "SELECT 
        p.product_id,
        p.product_code,
        p.name,
        p.min_stock_level,
        p.unit,
        p.current_stock,
        p.status
    FROM products p
    WHERE p.status = 'active'
        AND (p.current_stock <= p.min_stock_level OR p.min_stock_level IS NULL)";

    $products_result = $connect->query($products_sql);
    if (!$products_result) {
        throw new Exception("Products query failed: " . $connect->error);
    }

    // Get raw materials with low stock
    $materials_sql = "SELECT 
        rm.id,
        rm.material_code as code,
        rm.name,
        rm.min_stock_level,
        rm.unit,
        rm.current_stock,
        rm.status
    FROM raw_materials rm
    WHERE rm.status = 'active'
        AND (rm.current_stock <= rm.min_stock_level OR rm.min_stock_level IS NULL)";

    $materials_result = $connect->query($materials_sql);
    if (!$materials_result) {
        throw new Exception("Raw materials query failed: " . $connect->error);
    }

    $data = array();

    // Process products
    while ($row = $products_result->fetch_assoc()) {
        $current_stock = floatval($row['current_stock']);
        $min_stock = floatval($row['min_stock_level']);
        
        $data[] = array(
            'id' => 'P' . $row['product_id'],
            'name' => htmlspecialchars($row['name']),
            'type' => '<span class="label label-primary">Product</span>',
            'warehouse' => 'Default', // Since warehouse is not in current schema
            'current_stock' => number_format($current_stock, 2) . ' ' . $row['unit'],
            'minimum_stock' => number_format($min_stock, 2) . ' ' . $row['unit'],
            'reorder_point' => 'N/A',
            'status' => getStatusLabel($current_stock, $min_stock),
            'action' => getActionButtons('P' . $row['product_id'], 'product', $row['name'])
        );
    }

    // Process raw materials
    while ($row = $materials_result->fetch_assoc()) {
        $current_stock = floatval($row['current_stock']);
        $min_stock = floatval($row['min_stock_level']);
        
        $data[] = array(
            'id' => 'R' . $row['id'],
            'name' => htmlspecialchars($row['name']),
            'type' => '<span class="label label-info">Raw Material</span>',
            'warehouse' => 'Default', // Since warehouse is not in current schema
            'current_stock' => number_format($current_stock, 2) . ' ' . $row['unit'],
            'minimum_stock' => number_format($min_stock, 2) . ' ' . $row['unit'],
            'reorder_point' => 'N/A',
            'status' => getStatusLabel($current_stock, $min_stock),
            'action' => getActionButtons('R' . $row['id'], 'raw', $row['name'])
        );
    }

    // Send response
    echo json_encode([
        'draw' => isset($_REQUEST['draw']) ? intval($_REQUEST['draw']) : 1,
        'recordsTotal' => count($data),
        'recordsFiltered' => count($data),
        'data' => $data
    ]);

} catch (Exception $e) {
    error_log("Error in fetchLowStock.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($connect)) {
        $connect->close();
    }
}

// Helper function to get status label
function getStatusLabel($current_stock, $min_stock) {
    if ($current_stock <= 0) {
        return '<span class="label label-danger">OUT OF STOCK</span>';
    } elseif ($current_stock <= $min_stock) {
        return '<span class="label label-warning">LOW STOCK</span>';
    }
    return '<span class="label label-success">OK</span>';
}

// Helper function to get action buttons
function getActionButtons($id, $type, $name) {
    $itemId = substr($id, 1);
    return '<div class="btn-group">
        <button class="btn btn-info btn-sm view-details" 
            data-id="'.$itemId.'" 
            data-type="'.$type.'" 
            data-name="'.htmlspecialchars($name).'">
            <i class="fa fa-eye"></i> View
        </button>
    </div>';
}
?> 