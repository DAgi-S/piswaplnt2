<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'core.php';
require_once 'telegram_notification.php';

// Set proper headers for JSON response
header('Content-Type: application/json');

$response = array(
    'success' => false,
    'messages' => '',
    'purchase_id' => null
);

if($_POST) {
    try {
        $connect->begin_transaction();

        // Get form data
        $supplier_id = isset($_POST['supplier']) ? intval($_POST['supplier']) : 0;
        
        // Validate and format the date
        $purchase_date = isset($_POST['purchaseDate']) ? trim($_POST['purchaseDate']) : date('Y-m-d');
        
        // Remove any potential extra characters
        $purchase_date = preg_replace('/[^0-9-]/', '', $purchase_date);
        
        // Check if date matches YYYY-MM-DD format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $purchase_date)) {
            throw new Exception("Invalid date format. Please use YYYY-MM-DD format.");
        }
        
        // Validate the date is real
        $date_parts = explode('-', $purchase_date);
        if (!checkdate($date_parts[1], $date_parts[2], $date_parts[0])) {
            throw new Exception("Invalid date. Please enter a valid date.");
        }

        $sub_total = isset($_POST['subTotal']) ? floatval($_POST['subTotal']) : 0;
        $vat = isset($_POST['vat']) ? floatval($_POST['vat']) : 0;
        $grand_total = isset($_POST['grandTotal']) ? floatval($_POST['grandTotal']) : 0;
        $note = isset($_POST['note']) ? $_POST['note'] : '';
        $withholding_tax_enabled = isset($_POST['withholdingTaxEnabled']) ? 1 : 0;
        $withholding_tax_amount = isset($_POST['withholdingAmount']) ? floatval($_POST['withholdingAmount']) : 0;
        $created_by = $_SESSION['userId'];
        $warehouse_id = isset($_POST['warehouse']) ? intval($_POST['warehouse']) : 1; // Default to warehouse 1 if not specified

        // Generate purchase number (format: PO-YYYYMMDD-XXX)
        $date_part = date('Ymd');
        $sql = "SELECT COUNT(*) as count FROM purchases WHERE purchase_number LIKE 'PO-$date_part-%'";
        $result = $connect->query($sql);
        $row = $result->fetch_assoc();
        $count = $row['count'] + 1;
        $purchase_number = sprintf("PO-%s-%03d", $date_part, $count);

        // Insert purchase header
        $sql = "INSERT INTO purchases (
            purchase_number, supplier_id, purchase_date, sub_total, vat, grand_total, 
            note, withholding_tax_enabled, withholding_tax_amount, created_by, warehouse_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $connect->prepare($sql);
        $stmt->bind_param("sissddsisdi",
            $purchase_number,
            $supplier_id,
            $purchase_date,
            $sub_total,
            $vat,
            $grand_total,
            $note,
            $withholding_tax_enabled,
            $withholding_tax_amount,
            $created_by,
            $warehouse_id
        );

        if(!$stmt->execute()) {
            throw new Exception("Error creating purchase: " . $stmt->error);
        }

        $purchase_id = $connect->insert_id;

        // Insert purchase items and update stock
        if(isset($_POST['productId']) && is_array($_POST['productId'])) {
            $itemSql = "INSERT INTO purchase_items (
                purchase_id, product_id, quantity, rate, total
            ) VALUES (?, ?, ?, ?, ?)";
            
            $itemStmt = $connect->prepare($itemSql);

            // Prepare statements for stock updates
            $updateProductSql = "UPDATE products SET 
                current_stock = current_stock + ? 
                WHERE product_id = ?";
            $updateProductStmt = $connect->prepare($updateProductSql);

            // Check if warehouse stock entry exists
            $checkWarehouseSql = "SELECT id FROM warehouse_stock 
                WHERE warehouse_id = ? AND item_type = 'product' AND item_id = ?";
            $checkWarehouseStmt = $connect->prepare($checkWarehouseSql);

            // Insert new warehouse stock entry
            $insertWarehouseSql = "INSERT INTO warehouse_stock 
                (warehouse_id, item_type, item_id, quantity, status) 
                VALUES (?, 'product', ?, ?, 'active')";
            $insertWarehouseStmt = $connect->prepare($insertWarehouseSql);

            // Update existing warehouse stock
            $updateWarehouseSql = "UPDATE warehouse_stock 
                SET quantity = quantity + ? 
                WHERE warehouse_id = ? AND item_type = 'product' AND item_id = ?";
            $updateWarehouseStmt = $connect->prepare($updateWarehouseSql);

            // Insert stock movement record
            $movementSql = "INSERT INTO warehouse_stock_movements 
                (warehouse_id, item_type, item_id, movement_type, quantity, reference_type, reference_id) 
                VALUES (?, 'product', ?, 'in', ?, 'purchase', ?)";
            $movementStmt = $connect->prepare($movementSql);

            // Insert purchase items and collect product details for notification
            $productDetails = array();
            
            foreach($_POST['productId'] as $index => $productId) {
                $quantity = floatval($_POST['quantity'][$index]);
                $rate = floatval($_POST['rate'][$index]);
                $total = floatval($_POST['amount'][$index]);

                // Insert purchase item
                $itemStmt->bind_param("iiddd",
                    $purchase_id,
                    $productId,
                    $quantity,
                    $rate,
                    $total
                );

                if(!$itemStmt->execute()) {
                    throw new Exception("Error adding purchase item: " . $itemStmt->error);
                }

                // Update product stock
                $updateProductStmt->bind_param("di", $quantity, $productId);
                if(!$updateProductStmt->execute()) {
                    throw new Exception("Error updating product stock: " . $updateProductStmt->error);
                }

                // Check if warehouse stock entry exists
                $checkWarehouseStmt->bind_param("ii", $warehouse_id, $productId);
                $checkWarehouseStmt->execute();
                $result = $checkWarehouseStmt->get_result();

                if($result->num_rows > 0) {
                    // Update existing warehouse stock
                    $updateWarehouseStmt->bind_param("dii", $quantity, $warehouse_id, $productId);
                    if(!$updateWarehouseStmt->execute()) {
                        throw new Exception("Error updating warehouse stock: " . $updateWarehouseStmt->error);
                    }
                } else {
                    // Insert new warehouse stock entry
                    $insertWarehouseStmt->bind_param("iid", $warehouse_id, $productId, $quantity);
                    if(!$insertWarehouseStmt->execute()) {
                        throw new Exception("Error inserting warehouse stock: " . $insertWarehouseStmt->error);
                    }
                }

                // Record stock movement
                $movementStmt->bind_param("iidi", $warehouse_id, $productId, $quantity, $purchase_id);
                if(!$movementStmt->execute()) {
                    throw new Exception("Error recording stock movement: " . $movementStmt->error);
                }

                // Get product name
                $productQuery = "SELECT name FROM products WHERE product_id = ?";
                $productStmt = $connect->prepare($productQuery);
                $productStmt->bind_param("i", $productId);
                $productStmt->execute();
                $productResult = $productStmt->get_result();
                $productData = $productResult->fetch_assoc();

                // Add to product details array for notification
                $productDetails[] = sprintf(
                    "%s x%s @ %s = %s",
                    $productData['name'],
                    number_format($quantity, 2),
                    number_format($rate, 2),
                    number_format($total, 2)
                );
            }
        }

        // Get supplier details before creating notification
        $supplierQuery = "SELECT company_name, contact_person, phone FROM suppliers WHERE id = ?";
        $supplierStmt = $connect->prepare($supplierQuery);
        $supplierStmt->bind_param("i", $supplier_id);
        $supplierStmt->execute();
        $supplierResult = $supplierStmt->get_result();
        $supplierData = $supplierResult->fetch_assoc();

        // Get warehouse details before creating notification
        $warehouseQuery = "SELECT name FROM warehouses WHERE id = ?";
        $warehouseStmt = $connect->prepare($warehouseQuery);
        $warehouseStmt->bind_param("i", $warehouse_id);
        $warehouseStmt->execute();
        $warehouseResult = $warehouseStmt->get_result();
        $warehouseData = $warehouseResult->fetch_assoc();

        // Create Telegram notification message
        $telegramMessage = "🛒 <b>New Purchase Created</b>\n\n".
            "Purchase #: " . $purchase_number . "\n".
            "Date: " . date('d M Y', strtotime($purchase_date)) . "\n".
            "Supplier: " . ($supplierData ? $supplierData['company_name'] : 'N/A') . "\n".
            "Contact: " . ($supplierData ? ($supplierData['contact_person'] . " (" . $supplierData['phone'] . ")") : 'N/A') . "\n".
            "Warehouse: " . ($warehouseData ? $warehouseData['name'] : 'N/A') . "\n\n".
            "<b>Products:</b>\n" . implode("\n", $productDetails) . "\n\n".
            "Sub Total: " . number_format($sub_total, 2) . "\n".
            "VAT (15%): " . number_format($vat, 2) . "\n";

        if($withholding_tax_enabled) {
            $telegramMessage .= "Withholding (2%): " . number_format($withholding_tax_amount, 2) . "\n";
        }

        $telegramMessage .= "Grand Total: " . number_format($grand_total, 2);

        if(!empty($note)) {
            $telegramMessage .= "\n\nNote: " . $note;
        }

        // Send Telegram notification
        sendTelegramNotification($telegramMessage);

        $connect->commit();
        
        $response['success'] = true;
        $response['messages'] = "Purchase successfully created and stock updated";
        $response['purchase_id'] = $purchase_id;

    } catch(Exception $e) {
        $connect->rollback();
        
        $response['success'] = false;
        $response['messages'] = $e->getMessage();
        
        error_log("Error in createPurchase.php: " . $e->getMessage());
    }
} else {
    $response['success'] = false;
    $response['messages'] = "No data received";
}

$connect->close();

echo json_encode($response); 