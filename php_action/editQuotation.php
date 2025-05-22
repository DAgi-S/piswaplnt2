<?php
require_once 'core.php';
require_once 'db_connect.php';
require_once 'middleware.php';

// Set header to accept JSON
header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['quotationId']) || !isset($input['items']) || empty($input['items'])) {
    echo json_encode([
        'success' => false,
        'messages' => 'Invalid input data'
    ]);
    exit();
}

if (!hasPermission('edit_quotations')) {
    echo json_encode([
        'success' => false,
        'messages' => 'You do not have permission to edit quotations'
    ]);
    exit();
}

// Start transaction
mysqli_begin_transaction($connect);

try {
    $quotationId = $input['quotationId'];
    $clientId = $input['clientId'];
    $notes = $input['notes'];
    $withholding = $input['withholding'];
    $subTotal = $input['subTotal'];
    $vatAmount = $input['vatAmount'];
    $withholdingAmount = $input['withholdingAmount'];
    $grandTotal = $input['grandTotal'];

    // Update main quotation
    $updateQuotation = "UPDATE quotations SET 
        client_id = ?, 
        notes = ?, 
        withholding = ?, 
        sub_total = ?, 
        vat_amount = ?, 
        withholding_amount = ?, 
        grand_total = ?, 
        updated_at = NOW() 
        WHERE id = ?";

    $stmt = mysqli_prepare($connect, $updateQuotation);
    mysqli_stmt_bind_param($stmt, 'isiddddi', 
        $clientId, 
        $notes, 
        $withholding, 
        $subTotal, 
        $vatAmount, 
        $withholdingAmount, 
        $grandTotal, 
        $quotationId
    );
    
    $updateQuotationResult = mysqli_stmt_execute($stmt);
    
    if (!$updateQuotationResult) {
        throw new Exception("Error updating quotation: " . mysqli_error($connect));
    }

    // Delete existing items
    $deleteItems = "DELETE FROM quotation_items WHERE quotation_id = ?";
    $stmt = mysqli_prepare($connect, $deleteItems);
    mysqli_stmt_bind_param($stmt, 'i', $quotationId);
    $deleteResult = mysqli_stmt_execute($stmt);
    
    if (!$deleteResult) {
        throw new Exception("Error deleting existing items: " . mysqli_error($connect));
    }

    // Insert new items
    $insertItem = "INSERT INTO quotation_items (
        quotation_id, 
        product_id, 
        description, 
        quantity, 
        unit_price, 
        total
    ) VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($connect, $insertItem);
    
    foreach ($input['items'] as $item) {
        mysqli_stmt_bind_param($stmt, 'iisddd',
            $quotationId,
            $item['productId'],
            $item['description'],
            $item['quantity'],
            $item['price'],
            $item['total']
        );
        
        $insertResult = mysqli_stmt_execute($stmt);
        
        if (!$insertResult) {
            throw new Exception("Error inserting item: " . mysqli_error($connect));
        }
    }

    // If everything is successful, commit the transaction
    mysqli_commit($connect);
    
    echo json_encode([
        'success' => true,
        'messages' => 'Quotation updated successfully'
    ]);

} catch (Exception $e) {
    // If there is an error, rollback the transaction
    mysqli_rollback($connect);
    
    echo json_encode([
        'success' => false,
        'messages' => $e->getMessage()
    ]);
}

// Close the database connection
mysqli_close($connect); 