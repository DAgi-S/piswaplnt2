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

$response = array('success' => false, 'messages' => '', 'data' => null);

if($_POST) {
    try {
        if(empty($_POST['id'])) {
            throw new Exception("Sale ID is required");
        }

        $sale_id = intval($_POST['id']);
        
        $sql = "SELECT * FROM gps_sales WHERE id = ?";
        
        $stmt = $connect->prepare($sql);
        if (!$stmt) {
            throw new Exception("Database error: " . $connect->error);
        }

        $stmt->bind_param('i', $sale_id);
        
        if($stmt->execute()) {
            $result = $stmt->get_result();
            if($sale = $result->fetch_assoc()) {
                $response['success'] = true;
                $response['data'] = array(
                    'id' => $sale['id'],
                    'sale_number' => 'SALE-' . str_pad($sale['id'], 5, '0', STR_PAD_LEFT),
                    'sale_date' => $sale['created_at'],
                    'buyer_name' => $sale['buyer_name'],
                    'contact' => $sale['contact'],
                    'sales_type' => $sale['sales_type'],
                    'quantity' => $sale['quantity'],
                    'unit_price' => $sale['unit_price'],
                    'total' => $sale['total']
                );
            } else {
                throw new Exception("Sale not found");
            }
        } else {
            throw new Exception("Error executing query: " . $stmt->error);
        }

    } catch (Exception $e) {
        $response['success'] = false;
        $response['messages'] = $e->getMessage();
    }
    
    echo json_encode($response);
} 