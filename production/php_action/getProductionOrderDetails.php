<?php
require_once 'api_headers.php';
require_once 'core.php';
require_once 'db_connect.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Custom logging function
function debug_log($message) {
    $log_file = __DIR__ . '/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

// Function to clean output and send JSON response
function sendJsonResponse($data, $statusCode = 200) {
    // Clean any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set headers
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    http_response_code($statusCode);
    
    // Send JSON response
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    // Get order number and sanitize
    $orderNumber = isset($_POST['order_number']) ? $_POST['order_number'] : '';
    debug_log("Received order number: " . $orderNumber);

    if (empty($orderNumber)) {
        throw new Exception('Invalid order number');
    }

    // Get order details with product information
    $sql = "SELECT 
                po.*,
                pp.product_code,
                pp.name as product_name,
                pp.unit,
                pp.production_cost,
                COALESCE(u.username, 'System') as created_by
            FROM production_orders po
            LEFT JOIN production_products pp ON po.product_id = pp.id
            LEFT JOIN users u ON po.created_by = u.user_id
            WHERE po.order_number = ?";
    
    debug_log("Executing main query: " . $sql);
    
    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $connect->error);
    }

    $stmt->bind_param("s", $orderNumber);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception("Result failed: " . $stmt->error);
    }

    if ($result->num_rows === 0) {
        throw new Exception("Order not found: " . $orderNumber);
    }

    $order = $result->fetch_assoc();
    debug_log("Order data: " . json_encode($order));

    // Get materials information
    $sql = "SELECT 
                pom.*,
                rm.material_code,
                rm.name as material_name,
                rm.unit,
                rm.cost_per_unit as unit_cost
            FROM production_order_materials pom
            LEFT JOIN raw_materials rm ON pom.material_id = rm.id
            WHERE pom.production_order_id = ?";
    
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $order['id']);
    $stmt->execute();
    $materialsResult = $stmt->get_result();
    $materials = [];
    while ($row = $materialsResult->fetch_assoc()) {
        $materials[] = [
            'code' => $row['material_code'],
            'name' => $row['material_name'],
            'required_qty' => $row['required_quantity'],
            'used_qty' => $row['consumed_quantity'],
            'unit' => $row['unit'],
            'wastage' => max(0, floatval($row['consumed_quantity']) - floatval($row['required_quantity'])),
            'cost' => floatval($row['consumed_quantity']) * floatval($row['cost_per_unit']),
            'status' => $row['status']
        ];
    }

    // Get progress records
    $sql = "SELECT pp.*,
            COALESCE(u.username, (SELECT username FROM users WHERE user_id = po.created_by), 'System') as created_by_name
            FROM production_progress pp
            LEFT JOIN users u ON pp.created_by = u.user_id
            JOIN production_orders po ON pp.production_order_id = po.id
            WHERE pp.production_order_id = ?
            ORDER BY pp.created_at DESC";
    
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $order['id']);
    $stmt->execute();
    $progressResult = $stmt->get_result();
    $progress = [];
    while ($row = $progressResult->fetch_assoc()) {
        // Log progress data to debug
        error_log("Progress record data: " . json_encode($row));
        
        $progress[] = [
            'date' => date('Y-m-d', strtotime($row['created_at'])),
            'quantity' => $row['quantity'],
            'created_by' => $row['created_by_name'],
            'notes' => $row['notes'] ?: '-'
        ];
    }
    
    // Add default record if no progress records found
    if (empty($progress)) {
        $progress[] = [
            'date' => date('Y-m-d'),
            'quantity' => 0,
            'created_by' => $order['created_by'],
            'notes' => 'No progress records found'
        ];
    }

    // Get quality control records
    $sql = "SELECT qc.*,
            COALESCE(u.username, 'System') as created_by_name
            FROM quality_control qc
            LEFT JOIN users u ON qc.created_by = u.user_id
            WHERE qc.production_order_id = ?
            ORDER BY qc.inspection_date DESC";
    
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $order['id']);
    $stmt->execute();
    $qualityResult = $stmt->get_result();
    $quality = [];
    while ($row = $qualityResult->fetch_assoc()) {
        $quality[] = [
            'date' => $row['inspection_date'],
            'quantity' => $row['quantity_checked'],
            'quality_check' => $row['status'],
            'created_by' => $row['created_by_name'],
            'notes' => $row['notes'] ?: '-'
        ];
    }

    // Prepare response data
    $response = [
        'order_number' => $order['order_number'],
        'product_name' => $order['product_name'],
        'product_code' => $order['product_code'],
        'status' => $order['status'],
        'target_quantity' => $order['target_quantity'],
        'completed_quantity' => $order['completed_quantity'],
        'start_date' => $order['start_date'],
        'expected_completion' => $order['expected_completion_date'],
        'actual_completion' => $order['actual_completion_date'],
        'unit' => $order['unit'],
        'notes' => $order['notes'],
        'created_by' => $order['created_by'],
        'materials' => $materials,
        'progress' => $progress,
        'quality' => $quality
    ];

    debug_log("Final response: " . json_encode($response));

    // Clean output buffer and send response
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);

} catch (Exception $e) {
    debug_log("Error: " . $e->getMessage());
    
    // Clean output buffer and send error response
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

// Close database connection
if (isset($stmt)) {
    $stmt->close();
}
if (isset($connect)) {
    $connect->close();
}
exit; 