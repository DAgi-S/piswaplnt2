<?php
require_once 'core.php';
require_once 'db_connect.php';
require_once 'middleware.php';

if (!hasPermission('create_quotations')) {
    echo json_encode([
        'success' => false,
        'messages' => 'Access denied'
    ]);
    exit();
}

$response = array();

if ($_POST) {
    try {
        // Begin transaction
        $connect->begin_transaction();

        // Generate a unique quotation number using current timestamp and a random number
        $timestamp = date('ymd');
        $random = str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        $quotationNumber = 'PI-' . $timestamp . '-' . $random;

        // Check if this number already exists
        $checkSql = "SELECT COUNT(*) as count FROM quotations WHERE quotation_number = ?";
        $checkStmt = $connect->prepare($checkSql);
        $checkStmt->bind_param("s", $quotationNumber);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $count = $checkResult->fetch_assoc()['count'];

        // If exists, try again with a different random number
        while ($count > 0) {
            $random = str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
            $quotationNumber = 'PI-' . $timestamp . '-' . $random;
            $checkStmt->bind_param("s", $quotationNumber);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $count = $checkResult->fetch_assoc()['count'];
        }

        // Insert quotation
        $clientId = $_POST['clientId'];
        $subTotal = $_POST['subTotal'];
        $vatAmount = $_POST['vatAmount'];
        $withholdingAmount = isset($_POST['withholdingEnabled']) ? $_POST['withholdingAmount'] : 0;
        $grandTotal = $_POST['grandTotal'];
        $note = isset($_POST['note']) ? $_POST['note'] : '';
        $status = 1;
        $createdBy = $_SESSION['userId'];

        $sql = "INSERT INTO quotations (quotation_number, client_id, sub_total, vat_amount, withholding_amount, grand_total, note, status, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("siddddsii", $quotationNumber, $clientId, $subTotal, $vatAmount, $withholdingAmount, $grandTotal, $note, $status, $createdBy);
        $stmt->execute();

        $quotationId = $connect->insert_id;

        // Insert quotation items
        $productIds = $_POST['productId'];
        $prices = $_POST['price'];
        $quantities = $_POST['quantity'];
        $totals = $_POST['total'];

        $sql = "INSERT INTO quotation_items (quotation_id, product_id, unit_price, quantity, total_price) VALUES (?, ?, ?, ?, ?)";
        $stmt = $connect->prepare($sql);

        for($i = 0; $i < count($productIds); $i++) {
            if(empty($productIds[$i])) continue;

            $stmt->bind_param("iiddd", $quotationId, $productIds[$i], $prices[$i], $quantities[$i], $totals[$i]);
            $stmt->execute();
        }

        // Commit transaction
        $connect->commit();

        $response['success'] = true;
        $response['messages'] = 'Quotation created successfully';

    } catch(Exception $e) {
        // Rollback transaction on error
        $connect->rollback();

        $response['success'] = false;
        $response['messages'] = 'Error while creating quotation: ' . $e->getMessage();
    }

    echo json_encode($response);
} 