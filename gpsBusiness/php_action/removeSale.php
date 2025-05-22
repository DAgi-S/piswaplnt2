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
        if(empty($_POST['id'])) {
            throw new Exception("Sale ID is required");
        }

        $sale_id = intval($_POST['id']);
        
        $sql = "DELETE FROM gps_sales WHERE id = ?";
        
        $stmt = $connect->prepare($sql);
        if (!$stmt) {
            throw new Exception("Database error: " . $connect->error);
        }

        $stmt->bind_param('i', $sale_id);
        
        if($stmt->execute()) {
            $response['success'] = true;
            $response['messages'] = "Sale deleted successfully";
        } else {
            throw new Exception("Error executing query: " . $stmt->error);
        }

    } catch (Exception $e) {
        $response['success'] = false;
        $response['messages'] = $e->getMessage();
    }
    
    echo json_encode($response);
} 