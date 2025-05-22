<?php
require_once '../../php_action/core.php';

$response = array(
    'success' => false,
    'messages' => '',
    'data' => null
);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    try {
        $payment_id = (int)$_POST['id'];
        
        // Enable error logging
        error_log("Fetching payment details for ID: " . $payment_id);
        
        $sql = "SELECT p.*, o.order_number 
                FROM gps_payments p 
                LEFT JOIN gps_orders o ON p.gps_order_id = o.id 
                WHERE p.id = ?";
                
        $stmt = $connect->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Error preparing statement: " . $connect->error);
        }
        
        $stmt->bind_param('i', $payment_id);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                // Format the data
                $row['paid_amount'] = floatval($row['paid_amount']);
                $row['rate'] = $row['rate'] ? floatval($row['rate']) : null;
                
                // Handle image path
                if ($row['image_location']) {
                    error_log("Original image location: " . $row['image_location']);
                    
                    // Make sure the image path is relative to the root
                    $row['image_location'] = str_replace('\\', '/', $row['image_location']);
                    
                    // Check if the file exists
                    $image_path = dirname(dirname(__FILE__)) . '/' . $row['image_location'];
                    error_log("Checking image path: " . $image_path);
                    
                    if (file_exists($image_path)) {
                        error_log("Image file exists");
                    } else {
                        error_log("Image file does not exist at: " . $image_path);
                    }
                } else {
                    error_log("No image location found in database");
                }
                
                $response['success'] = true;
                $response['data'] = $row;
                error_log("Payment data: " . json_encode($row));
            } else {
                throw new Exception("Payment not found");
            }
        } else {
            throw new Exception("Error executing query: " . $stmt->error);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        error_log("Error in fetchGpsPaymentDetails: " . $e->getMessage());
        $response['messages'] = $e->getMessage();
    }
} else {
    $response['messages'] = 'Invalid request';
}

$connect->close();

header('Content-Type: application/json');
echo json_encode($response); 