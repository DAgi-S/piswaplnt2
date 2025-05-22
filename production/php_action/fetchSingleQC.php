<?php
// Clean output buffer to ensure no BOM or whitespace
ob_start();

require_once '../includes/db_connect.php';

// Add error logging
error_log('Starting fetchSingleQC.php');

// Ensure clean output - no whitespace or extra characters
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

try {
    // Get QC ID
    $id = isset($_POST['id']) ? $_POST['id'] : null;
    
    if(empty($id)) {
        throw new Exception("Quality control ID is required");
    }
    
    // Fetch QC details
    $query = "SELECT 
        qc.id,
        qc.production_order_id,
        qc.inspection_date,
        qc.quantity_checked,
        qc.quantity_passed,
        qc.quantity_failed,
        qc.defect_type,
        qc.notes,
        qc.status,
        po.order_number,
        pp.name as product_name
    FROM quality_control qc
    JOIN production_orders po ON qc.production_order_id = po.id
    JOIN production_products pp ON po.product_id = pp.id
    WHERE qc.id = ?";
    
    $stmt = $connect->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows === 0) {
        throw new Exception("Quality control entry not found");
    }
    
    $data = $result->fetch_assoc();
    
    $response = array(
        'success' => true,
        'data' => array(
            'id' => $data['id'],
            'production_order_id' => $data['production_order_id'],
            'inspection_date' => $data['inspection_date'],
            'quantity_checked' => $data['quantity_checked'],
            'quantity_passed' => $data['quantity_passed'],
            'quantity_failed' => $data['quantity_failed'],
            'defect_type' => $data['defect_type'] ? $data['defect_type'] : '',
            'notes' => $data['notes'] ? $data['notes'] : '',
            'status' => $data['status'],
            'order_number' => $data['order_number'],
            'product_name' => $data['product_name']
        )
    );
    
} catch(Exception $e) {
    error_log('Error in fetchSingleQC.php: ' . $e->getMessage());
    $response = array(
        'success' => false,
        'messages' => $e->getMessage()
    );
}

// Close the database connection
$connect->close();

// Clean output buffer to ensure no extra content
if (ob_get_length()) ob_clean();

// Return the JSON response
echo json_encode($response);

// Exit to prevent any further output
exit(); 