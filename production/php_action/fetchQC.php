<?php
// Clean output buffer to ensure no BOM or whitespace
if (ob_get_level()) ob_end_clean();
ob_start();

// Debug mode
$debug = true;
$debug_output = array();

// Add to debug log
function debug_log($message) {
    global $debug, $debug_output;
    if ($debug) {
        $debug_output[] = $message;
        error_log($message);
    }
}

debug_log('Starting fetchQC.php with clean buffer');

require_once '../includes/db_connect.php';

// Make sure no whitespace or extra output before headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

debug_log('Headers set');

try {
    // Check if database connection is successful
    if (!$connect) {
        throw new Exception("Database connection failed");
    }
    
    debug_log('Database connection successful');
    
    // Check for quality_control table
    $check_table = $connect->query("SHOW TABLES LIKE 'quality_control'");
    if ($check_table->rowCount() == 0) {
        throw new Exception("quality_control table does not exist");
    }
    
    debug_log('quality_control table exists');
    
    // Modified query to match the actual table structure - simplified for reliability
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
    LEFT JOIN production_orders po ON qc.production_order_id = po.id
    LEFT JOIN production_products pp ON po.product_id = pp.id
    ORDER BY qc.inspection_date DESC, qc.id DESC";
    
    debug_log('Executing query: ' . $query);
    
    $stmt = $connect->query($query);
    
    if (!$stmt) {
        throw new Exception("Query failed: " . $connect->errorInfo()[2]);
    }
    
    $data = array();
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Check if all necessary fields are present
        $required_fields = array('id', 'production_order_id', 'inspection_date', 'quantity_checked', 
                                'quantity_passed', 'quantity_failed', 'status');
        
        foreach ($required_fields as $field) {
            if (!isset($row[$field])) {
                debug_log("Warning: Missing required field '$field' in row " . $row['id']);
            }
        }
        
        $data[] = array(
            'id' => $row['id'],
            'production_order_id' => $row['production_order_id'],
            'inspection_date' => $row['inspection_date'],
            'quantity_checked' => $row['quantity_checked'],
            'quantity_passed' => $row['quantity_passed'],
            'quantity_failed' => $row['quantity_failed'],
            'defect_type' => isset($row['defect_type']) && $row['defect_type'] ? $row['defect_type'] : '',
            'notes' => isset($row['notes']) && $row['notes'] ? $row['notes'] : '',
            'status' => $row['status'],
            'order_number' => isset($row['order_number']) ? $row['order_number'] : '',
            'product_name' => isset($row['product_name']) ? $row['product_name'] : ''
        );
    }
    
    debug_log('Processed ' . count($data) . ' rows, finishing fetchQC.php');
    
    // Clean output buffer and ensure no whitespace before JSON
    if (ob_get_length()) ob_end_clean();
    
    // Create response object
    $response = array('data' => $data);
    
    // Add debug info if in debug mode
    if ($debug) {
        $response['debug'] = $debug_output;
    }
    
    // Output clean JSON without any extra whitespace
    echo json_encode($response);
    
} catch(Exception $e) {
    debug_log('Error in fetchQC.php: ' . $e->getMessage());
    
    // Clean output buffer to ensure no extra content
    if (ob_get_length()) ob_end_clean();
    
    // Create error response
    $response = array(
        'data' => array(),
        'error' => 'Error fetching quality control entries: ' . $e->getMessage()
    );
    
    // Add debug info if in debug mode
    if ($debug) {
        $response['debug'] = $debug_output;
    }
    
    echo json_encode($response);
}

// Close database connection
$connect = null;
debug_log('Database connection closed');

// Exit to prevent any further output
exit(); 