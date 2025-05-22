<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once 'core.php';
require_once 'db_connect.php';

// Clear any previous output
while (ob_get_level()) ob_end_clean();

header('Content-Type: application/json');

$response = array('success' => false, 'messages' => array());

if(isset($_POST['movement_id'])) {
    $movementId = mysqli_real_escape_string($connect, $_POST['movement_id']);

    try {
        // Fetch movement details
        $sql = "SELECT sm.*, p.name as product_name 
                FROM stock_movements sm 
                LEFT JOIN products p ON sm.product_id = p.product_id 
                WHERE sm.movement_id = ?";
        
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("i", $movementId);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows === 0) {
            throw new Exception("Stock movement not found");
        }

        $data = $result->fetch_assoc();
        
        $response['success'] = true;
        $response['data'] = array(
            'movement_id' => $data['movement_id'],
            'product_id' => $data['product_id'],
            'product_name' => $data['product_name'],
            'reference_type' => $data['reference_type'],
            'reference_id' => $data['reference_id'],
            'quantity' => $data['quantity'],
            'movement_type' => $data['movement_type'],
            'notes' => $data['notes'],
            'created_at' => $data['created_at']
        );

    } catch (Exception $e) {
        $response['success'] = false;
        $response['messages'] = $e->getMessage();
    }

} else {
    $response['success'] = false;
    $response['messages'] = "Movement ID is required";
}

echo json_encode($response); 