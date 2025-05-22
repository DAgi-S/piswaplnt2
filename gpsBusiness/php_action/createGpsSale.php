<?php
// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$response = array('success' => false, 'messages' => '');

if($_POST) {
    try {
        // Validate required fields
        if(empty($_POST['buyerName']) || empty($_POST['contact']) || 
           empty($_POST['salesType']) || empty($_POST['quantity']) || 
           empty($_POST['unitPrice']) || empty($_POST['total'])) {
            throw new Exception("All fields are required");
        }

        $buyer_name = $_POST['buyerName'];
        $contact = $_POST['contact'];
        $sales_type = $_POST['salesType'];
        $quantity = intval($_POST['quantity']);
        $unit_price = floatval($_POST['unitPrice']);
        $total = floatval($_POST['total']);
        $currency = 'ETB'; // Default currency
        $rate = 1; // Default rate
        $sale_date = date('Y-m-d'); // Current date
        
        $sql = "INSERT INTO gps_sales (
            buyer_name,
            contact,
            sales_type,
            quantity,
            unit_price,
            total,
            currency,
            rate,
            sale_date,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $connect->prepare($sql);
        if (!$stmt) {
            throw new Exception("Database error: " . $connect->error);
        }

        $stmt->bind_param(
            'sssiddsss',
            $buyer_name,
            $contact,
            $sales_type,
            $quantity,
            $unit_price,
            $total,
            $currency,
            $rate,
            $sale_date
        );
        
        if($stmt->execute()) {
            $response['success'] = true;
            $response['messages'] = "Sale added successfully";
        } else {
            throw new Exception("Error while adding the sale: " . $stmt->error);
        }
        
        $stmt->close();

    } catch (Exception $e) {
        $response['success'] = false;
        $response['messages'] = $e->getMessage();
    }
    
    $connect->close();
    
    // Set proper headers
    header('Content-Type: application/json');
    echo json_encode($response);
} 