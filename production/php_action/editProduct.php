<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

require_once 'core.php';
require_once 'db_connect.php';

// Set header type to JSON
header('Content-Type: application/json');

try {
    // Check if we have a valid database connection
    if (!isset($connect) || !$connect) {
        throw new Exception("Database connection failed");
    }

    // Validate required fields
    $required_fields = ['productId', 'editProductCode', 'editProductName', 'editCategoryId', 'editBrandId', 'editUnit', 'editMinStockLevel', 'editProductionCost', 'editSellingPrice', 'editStatus'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            throw new Exception("$field is required");
        }
    }

    // Validate numeric fields
    $numeric_fields = ['productId', 'editMinStockLevel', 'editProductionCost', 'editSellingPrice'];
    foreach ($numeric_fields as $field) {
        if (!is_numeric($_POST[$field])) {
            throw new Exception("$field must be a number");
        }
    }

    // Start transaction
    $connect->begin_transaction();

    try {
        // Check if product exists
        $productId = intval($_POST['productId']);
        $checkStmt = $connect->prepare("SELECT id FROM production_products WHERE id = ?");
        if (!$checkStmt) {
            throw new Exception("Failed to prepare check statement: " . $connect->error);
        }
        
        $checkStmt->bind_param("i", $productId);
        if (!$checkStmt->execute()) {
            throw new Exception("Failed to execute check statement: " . $checkStmt->error);
        }
        
        $checkResult = $checkStmt->get_result();
        if ($checkResult->num_rows === 0) {
            throw new Exception("Product not found");
        }
        $checkStmt->close();

        // Check if product code exists for other products
        $productCode = $connect->real_escape_string($_POST['editProductCode']);
        $codeStmt = $connect->prepare("SELECT id FROM production_products WHERE product_code = ? AND id != ?");
        if (!$codeStmt) {
            throw new Exception("Failed to prepare code check statement: " . $connect->error);
        }
        
        $codeStmt->bind_param("si", $productCode, $productId);
        if (!$codeStmt->execute()) {
            throw new Exception("Failed to execute code check statement: " . $codeStmt->error);
        }
        
        $codeResult = $codeStmt->get_result();
        if ($codeResult->num_rows > 0) {
            throw new Exception("Product code already exists");
        }
        $codeStmt->close();

        // Update product
        $updateStmt = $connect->prepare("
            UPDATE production_products SET 
                product_code = ?,
                name = ?,
                category_id = ?,
                brand_id = ?,
                unit = ?,
                min_stock_level = ?,
                production_cost = ?,
                selling_price = ?,
                description = ?,
                status = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        if (!$updateStmt) {
            throw new Exception("Failed to prepare update statement: " . $connect->error);
        }

        $name = $connect->real_escape_string($_POST['editProductName']);
        $categoryId = intval($_POST['editCategoryId']);
        $brandId = intval($_POST['editBrandId']);
        $unit = $connect->real_escape_string($_POST['editUnit']);
        $minStock = floatval($_POST['editMinStockLevel']);
        $productionCost = floatval($_POST['editProductionCost']);
        $sellingPrice = floatval($_POST['editSellingPrice']);
        $description = isset($_POST['editDescription']) ? $connect->real_escape_string($_POST['editDescription']) : '';
        $status = $connect->real_escape_string($_POST['editStatus']);

        $updateStmt->bind_param(
            "ssiisdddssi",
            $productCode,
            $name,
            $categoryId,
            $brandId,
            $unit,
            $minStock,
            $productionCost,
            $sellingPrice,
            $description,
            $status,
            $productId
        );

        if (!$updateStmt->execute()) {
            throw new Exception("Failed to update product: " . $updateStmt->error);
        }

        if ($updateStmt->affected_rows === 0) {
            throw new Exception("No changes were made to the product");
        }

        $updateStmt->close();

        // Commit the transaction
        $connect->commit();

        echo json_encode([
            'success' => true,
            'messages' => 'Product successfully updated'
        ]);

    } catch (Exception $e) {
        // Rollback the transaction on error
        $connect->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error in editProduct.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'messages' => $e->getMessage()
    ]);
}

$connect->close(); 