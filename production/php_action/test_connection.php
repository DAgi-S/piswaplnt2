<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'core.php';

header('Content-Type: application/json');

try {
    if (!isset($connect)) {
        throw new Exception("Database connection not initialized");
    }

    if ($connect->connect_error) {
        throw new Exception("Connection failed: " . $connect->connect_error);
    }

    // Test basic query
    $result = $connect->query("SELECT 1 as test");
    if (!$result) {
        throw new Exception("Query failed: " . $connect->error);
    }

    // Test warehouse table
    $warehouse_test = $connect->query("SELECT COUNT(*) as count FROM warehouses");
    if (!$warehouse_test) {
        throw new Exception("Warehouse table query failed: " . $connect->error);
    }
    $warehouse_count = $warehouse_test->fetch_object()->count;

    // Test products table
    $products_test = $connect->query("SELECT COUNT(*) as count FROM products");
    if (!$products_test) {
        throw new Exception("Products table query failed: " . $connect->error);
    }
    $products_count = $products_test->fetch_object()->count;

    echo json_encode([
        'success' => true,
        'message' => 'Connection successful',
        'data' => [
            'warehouses' => $warehouse_count,
            'products' => $products_count
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

if (isset($connect)) {
    $connect->close();
} 