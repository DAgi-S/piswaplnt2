<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'core.php';

// Set proper headers for JSON response
header('Content-Type: application/json');

$valid['success'] = array('success' => false, 'messages' => array());

if($_POST) {    
    try {
        // Basic product information
        $productCode = $_POST['productCode'];
        $productName = $_POST['productName']; 
        $description = $_POST['description'];
        $cost = floatval($_POST['cost']);
        $sellingPrice = floatval($_POST['sellingPrice']);
        $unit = $_POST['unit'];
        $currentStock = floatval($_POST['currentStock']);
        $minStockLevel = floatval($_POST['minStockLevel']);
        $productionCost = floatval($_POST['productionCost']);
        $brandId = intval($_POST['brandName']);
        $categoryId = intval($_POST['categoryName']);
        $productStatus = $_POST['productStatus'];

        // Start transaction
        $connect->begin_transaction();

        // First insert the product without the image
        $sql = "INSERT INTO products (
            product_code, name, description, cost, selling_price, 
            unit, current_stock, min_stock_level, production_cost, 
            brand_id, category_id, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $connect->prepare($sql);
        $stmt->bind_param("sssddsdddiis", 
            $productCode,
            $productName,
            $description,
            $cost,
            $sellingPrice,
            $unit,
            $currentStock,
            $minStockLevel,
            $productionCost,
            $brandId,
            $categoryId,
            $productStatus
        );

        if(!$stmt->execute()) {
            throw new Exception("Error inserting product: " . $stmt->error);
        }

        $productId = $connect->insert_id;

        // Handle image upload if provided
        if(isset($_FILES['productImage']) && $_FILES['productImage']['error'] === 0) {
            $file = $_FILES['productImage'];
            $fileName = $file['name'];
            $fileType = $file['type'];
            $fileTmpName = $file['tmp_name'];
            $fileSize = $file['size'];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            // Validate file type
            $allowedTypes = array('gif', 'jpg', 'jpeg', 'png', 'webp');
            if(!in_array($fileExt, $allowedTypes)) {
                throw new Exception("Invalid file type. Only JPG, JPEG, PNG, GIF & WEBP files are allowed.");
            }
            
            // Validate file size (5MB max)
            if($fileSize > 5000000) {
                throw new Exception("File size is too large. Maximum size is 5MB.");
            }
            
            // Create upload directory if it doesn't exist
            $uploadDir = '../assets/images/stock/';
            if(!file_exists($uploadDir)) {
                if(!mkdir($uploadDir, 0777, true)) {
                    throw new Exception("Failed to create upload directory");
                }
            }
            
            // Generate unique filename
            $newFileName = 'product_' . $productId . '_' . time() . '.' . $fileExt;
            $uploadPath = $uploadDir . $newFileName;
            $dbImagePath = 'assets/images/stock/' . $newFileName;
            
            // Move uploaded file
            if(move_uploaded_file($fileTmpName, $uploadPath)) {
                // Update product with image path
                $sql = "UPDATE products SET product_image = ? WHERE product_id = ?";
                $stmt = $connect->prepare($sql);
                $stmt->bind_param("si", $dbImagePath, $productId);
                
                if(!$stmt->execute()) {
                    // If image update fails, delete the uploaded file
                    @unlink($uploadPath);
                    throw new Exception("Error updating product image: " . $stmt->error);
                }
            } else {
                throw new Exception("Error uploading image file");
            }
        }

        // Commit transaction
        $connect->commit();
        
        $valid['success'] = true;
        $valid['messages'] = "Product Successfully Added";

    } catch(Exception $e) {
        // Rollback transaction on error
        $connect->rollback();
        
        $valid['success'] = false;
        $valid['messages'] = $e->getMessage();
        
        error_log("Error in createProduct.php: " . $e->getMessage());
    }

} else {
    $valid['success'] = false;
    $valid['messages'] = "Error occurred while adding the product";
}

$connect->close();

echo json_encode($valid); 