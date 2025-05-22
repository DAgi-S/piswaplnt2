<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'core.php';

// Set proper headers for JSON response
header('Content-Type: application/json');

if ($_POST) {
    try {
        $productId = isset($_POST['productId']) ? intval($_POST['productId']) : 0;
        $productName = isset($_POST['editProductName']) ? $_POST['editProductName'] : '';
        $currentStock = isset($_POST['editQuantity']) ? floatval($_POST['editQuantity']) : 0;
        $sellingPrice = isset($_POST['editPrice']) ? floatval($_POST['editPrice']) : 0;
        $brandId = isset($_POST['editBrandName']) ? intval($_POST['editBrandName']) : 0;
        $categoryId = isset($_POST['editCategoryName']) ? intval($_POST['editCategoryName']) : 0;
        
        // Handle status conversion
        $rawStatus = isset($_POST['editProductStatus']) ? $_POST['editProductStatus'] : '1';
        $productStatus = ($rawStatus === '1' || $rawStatus === 'active') ? 'active' : 'inactive';
        
        error_log("Raw status value: " . $rawStatus);
        error_log("Converted status: " . $productStatus);

        // Validate required fields
        if (!$productId || !$productName || $sellingPrice <= 0) {
            throw new Exception("Please fill in all required fields");
        }

        // Start transaction
        $connect->begin_transaction();

        // Update product information
        $sql = "UPDATE products SET 
                name = ?, 
                selling_price = ?, 
                current_stock = ?, 
                brand_id = ?, 
                category_id = ?, 
                status = ?
                WHERE product_id = ?";

        $stmt = $connect->prepare($sql);
        
        error_log("Final status value for binding: " . $productStatus);
        
        $stmt->bind_param("sddiisi", 
            $productName, 
            $sellingPrice, 
            $currentStock, 
            $brandId, 
            $categoryId, 
            $productStatus,
            $productId
        );

        if (!$stmt->execute()) {
            throw new Exception("Error updating product: " . $stmt->error);
        }

        // Handle image upload if provided
        if (isset($_FILES['editProductImage']) && $_FILES['editProductImage']['error'] === 0) {
            $file = $_FILES['editProductImage'];
            $fileName = $file['name'];
            $fileType = $file['type'];
            $fileTmpName = $file['tmp_name'];
            $fileError = $file['error'];
            $fileSize = $file['size'];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            // Validate file type
            $allowedTypes = array('jpg', 'jpeg', 'png', 'gif');
            if (!in_array($fileExt, $allowedTypes)) {
                throw new Exception("Invalid file type. Only JPG, JPEG, PNG & GIF files are allowed.");
            }
            
            // Validate file size (5MB max)
            if ($fileSize > 5000000) {
                throw new Exception("File size is too large. Maximum size is 5MB.");
            }
            
            // Create products directory if it doesn't exist
            $uploadDir = 'assets/images/stock/';
            if (!file_exists('../' . $uploadDir)) {
                if (!mkdir('../' . $uploadDir, 0777, true)) {
                    throw new Exception("Failed to create upload directory");
                }
            }
            
            // Generate unique filename
            $newFileName = 'product_' . $productId . '_' . time() . '.' . $fileExt;
            $uploadPath = '../' . $uploadDir . $newFileName;
            
            // Move uploaded file
            if (!move_uploaded_file($fileTmpName, $uploadPath)) {
                throw new Exception("Error uploading file");
            }
            
            // Update image path in database
            $imagePath = $uploadDir . $newFileName;
            $sql = "UPDATE products SET product_image = ? WHERE product_id = ?";
            $stmt = $connect->prepare($sql);
            $stmt->bind_param("si", $imagePath, $productId);
            
            if (!$stmt->execute()) {
                // If image update fails, delete the uploaded file
                @unlink($uploadPath);
                throw new Exception("Error updating product image: " . $stmt->error);
            }
        }

        // Commit transaction
        $connect->commit();

        $response = array(
            'success' => true,
            'messages' => 'Product successfully updated'
        );

    } catch (Exception $e) {
        // Rollback transaction on error
        if (isset($connect) && $connect->ping()) {
            $connect->rollback();
        }
        
        error_log("Error in editProduct.php: " . $e->getMessage());
        $response = array(
            'success' => false,
            'messages' => $e->getMessage()
        );
    }

    echo json_encode($response);
}

if (isset($connect)) {
    $connect->close();
} 