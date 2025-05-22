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
        if(empty($_POST['buyerName']) || empty($_POST['salesType']) || 
           empty($_POST['quantity']) || empty($_POST['unitPrice'])) {
            throw new Exception("Required fields cannot be empty");
        }

        $buyer_name = $_POST['buyerName'];
        $contact = $_POST['contact'];
        $sales_type = $_POST['salesType'];
        $quantity = intval($_POST['quantity']);
        $unit_price = floatval($_POST['unitPrice']);
        $total = $quantity * $unit_price;
        
        $sql = "INSERT INTO gps_sales (
            buyer_name,
            contact,
            sales_type,
            quantity,
            unit_price,
            total,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $connect->prepare($sql);
        if (!$stmt) {
            throw new Exception("Database error: " . $connect->error);
        }

        $stmt->bind_param(
            'sssids',
            $buyer_name,
            $contact,
            $sales_type,
            $quantity,
            $unit_price,
            $total
        );
        
        if($stmt->execute()) {
            $response['success'] = true;
            $response['messages'] = "Sale added successfully";
            $response['sale_id'] = $connect->insert_id;
        } else {
            throw new Exception("Error executing query: " . $stmt->error);
        }

    } catch (Exception $e) {
        $response['success'] = false;
        $response['messages'] = $e->getMessage();
    }
    
    echo json_encode($response);
} 