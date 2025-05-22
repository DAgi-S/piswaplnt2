<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'core.php';

// Set proper headers for JSON response
header('Content-Type: application/json');

$response = array(
    'success' => false,
    'messages' => ''
);

if ($_POST) {
    try {
        $connect->begin_transaction();

        // Validate required fields first
        if (!isset($_POST['purchaseId']) || empty($_POST['purchaseId'])) {
            throw new Exception("Purchase ID is missing");
        }
        if (!isset($_POST['supplier']) || empty($_POST['supplier'])) {
            throw new Exception("Please select a supplier");
        }
        if (!isset($_POST['purchaseDate']) || empty($_POST['purchaseDate'])) {
            throw new Exception("Please select a purchase date");
        }
        if (!isset($_POST['productId']) || !is_array($_POST['productId']) || empty($_POST['productId'])) {
            throw new Exception("Please add at least one product");
        }

        // Get form data
        $purchaseId = intval($_POST['purchaseId']);
        $supplierId = intval($_POST['supplier']);
        $purchaseDate = $_POST['purchaseDate'];
        $subTotal = isset($_POST['subTotal']) ? floatval($_POST['subTotal']) : 0;
        $vat = isset($_POST['vat']) ? floatval($_POST['vat']) : 0;
        $withholdingTaxEnabled = isset($_POST['withholdingTaxEnabled']) ? 1 : 0;
        $withholdingAmount = isset($_POST['withholdingAmount']) ? floatval($_POST['withholdingAmount']) : 0;
        $grandTotal = isset($_POST['grandTotal']) ? floatval($_POST['grandTotal']) : 0;
        $note = isset($_POST['note']) ? $_POST['note'] : '';

        // Update purchase header
        $sql = "UPDATE purchases SET 
                supplier_id = ?,
                purchase_date = ?,
                sub_total = ?,
                vat = ?,
                withholding_tax_enabled = ?,
                withholding_tax_amount = ?,
                grand_total = ?,
                note = ?
                WHERE id = ?";

        $stmt = $connect->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparing purchase update: " . $connect->error);
        }

        $stmt->bind_param("isddiddsi", 
            $supplierId,
            $purchaseDate,
            $subTotal,
            $vat,
            $withholdingTaxEnabled,
            $withholdingAmount,
            $grandTotal,
            $note,
            $purchaseId
        );

        if (!$stmt->execute()) {
            throw new Exception("Error updating purchase: " . $stmt->error);
        }

        // Delete existing purchase items
        $sql = "DELETE FROM purchase_items WHERE purchase_id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("i", $purchaseId);
        
        if (!$stmt->execute()) {
            throw new Exception("Error removing old purchase items: " . $stmt->error);
        }

        // Insert updated purchase items
        if (isset($_POST['productId']) && is_array($_POST['productId'])) {
            $sql = "INSERT INTO purchase_items (purchase_id, product_id, quantity, rate, total) VALUES (?, ?, ?, ?, ?)";
            $stmt = $connect->prepare($sql);

            foreach ($_POST['productId'] as $index => $productId) {
                if (empty($productId)) continue;

                $quantity = floatval($_POST['quantity'][$index]);
                $rate = floatval($_POST['rate'][$index]);
                $total = floatval($_POST['amount'][$index]);

                $stmt->bind_param("iiddd",
                    $purchaseId,
                    $productId,
                    $quantity,
                    $rate,
                    $total
                );

                if (!$stmt->execute()) {
                    throw new Exception("Error adding purchase item: " . $stmt->error);
                }

                // Update product stock
                $updateSql = "UPDATE products SET 
                    current_stock = current_stock + ? 
                    WHERE product_id = ?";
                
                $updateStmt = $connect->prepare($updateSql);
                $updateStmt->bind_param("di", $quantity, $productId);
                
                if (!$updateStmt->execute()) {
                    throw new Exception("Error updating product stock: " . $updateStmt->error);
                }
            }
        }

        $connect->commit();
        
        $response['success'] = true;
        $response['messages'] = "Purchase successfully updated";

    } catch (Exception $e) {
        $connect->rollback();
        $response['success'] = false;
        $response['messages'] = $e->getMessage();
        error_log("Error in editPurchase.php: " . $e->getMessage());
    }
}

echo json_encode($response);

if (isset($connect)) {
    $connect->close();
} 