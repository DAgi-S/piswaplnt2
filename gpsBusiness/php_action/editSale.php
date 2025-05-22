<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode([
        'success' => false,
        'messages' => 'Session expired. Please log in again.'
    ]);
    exit();
}

require_once '../../php_action/core.php';

$response = array('success' => false, 'messages' => '');

if($_POST) {
    try {
        // Validate required fields
        if(empty($_POST['saleId']) || empty($_POST['buyerName']) || 
           empty($_POST['salesType']) || empty($_POST['quantity']) || 
           empty($_POST['unitPrice'])) {
            throw new Exception("Required fields cannot be empty");
        }

        $sale_id = $_POST['saleId'];
        $buyer_name = $_POST['buyerName'];
        $contact = $_POST['contact'];
        $sales_type = $_POST['salesType'];
        $quantity = intval($_POST['quantity']);
        $unit_price = floatval($_POST['unitPrice']);
        $total = $quantity * $unit_price;
        
        $sql = "UPDATE gps_sales SET 
                buyer_name = ?,
                contact = ?,
                sales_type = ?,
                quantity = ?,
                unit_price = ?,
                total = ?
                WHERE id = ?";
        
        $stmt = $connect->prepare($sql);
        if (!$stmt) {
            throw new Exception("Database error: " . $connect->error);
        }

        $stmt->bind_param(
            'sssiddi',
            $buyer_name,
            $contact,
            $sales_type,
            $quantity,
            $unit_price,
            $total,
            $sale_id
        );
        
        if($stmt->execute()) {
            $response['success'] = true;
            $response['messages'] = "Sale updated successfully";
        } else {
            throw new Exception("Error executing query: " . $stmt->error);
        }

    } catch (Exception $e) {
        $response['success'] = false;
        $response['messages'] = $e->getMessage();
    }
    
    echo json_encode($response);
} 