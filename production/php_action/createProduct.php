<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/db_connect.php';

header('Content-Type: application/json');

try {
    // Validate required fields
    $required_fields = ['productCode', 'name', 'categoryId', 'brandId', 'unit', 'currentStock', 'minStockLevel', 'productionCost', 'sellingPrice', 'status'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            throw new Exception("$field is required");
        }
    }

    // Validate numeric fields
    $numeric_fields = ['currentStock', 'minStockLevel', 'productionCost', 'sellingPrice'];
    foreach ($numeric_fields as $field) {
        if (!is_numeric($_POST[$field])) {
            throw new Exception("$field must be a number");
        }
    }

    // Check if product code already exists
    $stmt = $connect->prepare("SELECT COUNT(*) FROM production_products WHERE product_code = ?");
    $stmt->execute([$_POST['productCode']]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception("Product code already exists");
    }

    // Insert new product
    $sql = "INSERT INTO production_products (
                product_code, 
                name, 
                category_id, 
                brand_id, 
                unit, 
                current_stock, 
                min_stock_level, 
                production_cost, 
                selling_price, 
                description, 
                status, 
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $connect->prepare($sql);
    $stmt->execute([
        $_POST['productCode'],
        $_POST['name'],
        $_POST['categoryId'],
        $_POST['brandId'],
        $_POST['unit'],
        $_POST['currentStock'],
        $_POST['minStockLevel'],
        $_POST['productionCost'],
        $_POST['sellingPrice'],
        $_POST['description'] ?? '',
        $_POST['status']
    ]);

    echo json_encode([
        'success' => true,
        'messages' => 'Product successfully created'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'messages' => $e->getMessage()
    ]);
}
?> 