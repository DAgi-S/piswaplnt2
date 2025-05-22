<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Fix the include path
$root = realpath(dirname(__FILE__) . '/..');
require_once $root . '/includes/db_connect.php';

try {
    // Check database connection
    if (!$connect) {
        throw new Exception("Database connection failed");
    }

    // First, get a valid product ID
    $productStmt = $connect->query("SELECT id FROM production_products LIMIT 1");
    $productId = $productStmt->fetch(PDO::FETCH_COLUMN);

    if (!$productId) {
        throw new Exception("No products found in the database");
    }

    // Then, get a valid raw material ID
    $materialStmt = $connect->query("SELECT id FROM raw_materials LIMIT 1");
    $materialId = $materialStmt->fetch(PDO::FETCH_COLUMN);

    if (!$materialId) {
        throw new Exception("No raw materials found in the database");
    }

    // Insert sample BOM record
    $sql = "INSERT INTO bill_of_materials (
        product_id, 
        raw_material_id, 
        quantity, 
        wastage, 
        status
    ) VALUES (?, ?, ?, ?, ?)";

    $stmt = $connect->prepare($sql);
    
    if (!$stmt->execute([
        $productId,
        $materialId,
        10.00, // sample quantity
        5.00,  // sample wastage percentage
        'active'
    ])) {
        throw new Exception("Error inserting BOM record: " . print_r($stmt->errorInfo(), true));
    }

    echo "Sample BOM record created successfully\n";
    echo "Product ID: $productId\n";
    echo "Material ID: $materialId\n";

} catch (Exception $e) {
    error_log("Error inserting sample BOM: " . $e->getMessage());
    echo "Error: " . $e->getMessage();
} 