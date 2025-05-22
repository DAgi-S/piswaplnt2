<?php
require_once 'core.php';
require_once 'db_connect.php';

header('Content-Type: application/json');

// Add debugging
error_log('POST data received: ' . print_r($_POST, true));

$response = array(
    'success' => false,
    'messages' => '',
    'data' => null
);

// Check for both id and orderId parameters
$orderId = null;
if(isset($_POST['id'])) {
    $orderId = $_POST['id'];
} elseif(isset($_POST['orderId'])) {
    $orderId = $_POST['orderId'];
} elseif(isset($_GET['id'])) {
    $orderId = $_GET['id'];
} elseif(isset($_GET['orderId'])) {
    $orderId = $_GET['orderId'];
}

if(!$orderId) {
    $response['messages'] = 'Order ID not provided';
    error_log('Order ID not provided in request. POST data: ' . print_r($_POST, true));
    echo json_encode($response);
    exit();
}

// Clean the ID - remove any quotes or special characters
$orderId = filter_var(trim($orderId), FILTER_VALIDATE_INT);

if(!$orderId) {
    $response['messages'] = 'Invalid order ID format';
    error_log('Invalid order ID format. Original value: ' . print_r($orderId, true));
    echo json_encode($response);
    exit();
}

try {
    // Get order details with product information
    $orderSql = "SELECT po.*, pp.name as product_name,
                COALESCE(u.username, 'System') as created_by,
                COALESCE((SELECT SUM(quantity) FROM production_progress 
                         WHERE production_order_id = po.id), 0) as total_progress
                FROM production_orders po
                JOIN production_products pp ON po.product_id = pp.id
                LEFT JOIN users u ON po.created_by = u.user_id
                WHERE po.id = ?";
    
    $orderStmt = $connect->prepare($orderSql);
    $orderStmt->bind_param('i', $orderId);
    $orderStmt->execute();
    $result = $orderStmt->get_result();
    
    if($result->num_rows === 0) {
        throw new Exception("Production order not found");
    }
    
    $orderData = $result->fetch_assoc();
    
    // Get materials data
    $materialsSql = "SELECT 
                    m.id as material_id,
                    m.name,
                    m.current_stock,
                    pom.required_quantity,
                    COALESCE(pom.consumed_quantity, 0) as consumed_quantity
                    FROM production_order_materials pom
                    JOIN raw_materials m ON m.id = pom.material_id 
                    WHERE pom.production_order_id = ?";
    
    $materialsStmt = $connect->prepare($materialsSql);
    $materialsStmt->bind_param('i', $orderId);
    $materialsStmt->execute();
    $materialsResult = $materialsStmt->get_result();
    
    $materials = array();
    while($material = $materialsResult->fetch_assoc()) {
        $materials[] = $material;
    }
    
    // Add materials to order data
    $orderData['materials'] = $materials;
    
    // Format dates - only if they're not empty
    $orderData['start_date'] = !empty($orderData['start_date']) ? date('Y-m-d', strtotime($orderData['start_date'])) : '';
    $orderData['expected_completion_date'] = !empty($orderData['expected_completion_date']) ? date('Y-m-d', strtotime($orderData['expected_completion_date'])) : '';
    $orderData['actual_completion_date'] = !empty($orderData['actual_completion_date']) ? date('Y-m-d', strtotime($orderData['actual_completion_date'])) : '';
    
    // Normalize status
    $orderData['status'] = strtolower(trim($orderData['status']));
    if($orderData['status'] === 'in progress') {
        $orderData['status'] = 'in_progress';
    }
    
    // Get status class for display
    $statusClasses = array(
        'draft' => 'default',
        'confirmed' => 'primary',
        'in_progress' => 'info',
        'completed' => 'success',
        'cancelled' => 'danger'
    );
    
    // Format status for display
    $statusDisplay = ucwords(str_replace('_', ' ', $orderData['status']));
    $statusClass = isset($statusClasses[$orderData['status']]) ? $statusClasses[$orderData['status']] : 'default';
    
    // Add formatted status fields
    $orderData['status_display'] = $statusDisplay;
    $orderData['status_class'] = $statusClass;
    $orderData['status_html'] = '<span class="label label-' . $statusClass . '">' . $statusDisplay . '</span>';
    
    // Convert quantities to float for proper comparison
    $orderData['target_quantity'] = floatval($orderData['target_quantity']);
    $orderData['completed_quantity'] = floatval($orderData['completed_quantity']);
    $orderData['total_progress'] = floatval($orderData['total_progress']);
    
    // Get progress history
    $progressSql = "SELECT 
                   pp.id,
                   pp.quantity,
                   COALESCE(pp.notes, '-') as notes,
                   pp.created_at,
                   pp.created_by,
                   COALESCE(u.username, 'System') as created_by_name
                   FROM production_progress pp
                   LEFT JOIN users u ON pp.created_by = u.user_id
                   WHERE pp.production_order_id = ?
                   ORDER BY pp.created_at DESC";
    
    $progressStmt = $connect->prepare($progressSql);
    $progressStmt->bind_param('i', $orderId);
    $progressStmt->execute();
    $progressResult = $progressStmt->get_result();
    
    $progress = array();
    while($prog = $progressResult->fetch_assoc()) {
        // Debug what's in the response
        error_log("Progress record for ID " . $prog['id'] . ", created_by_name: " . $prog['created_by_name']);
        
        $progress[] = array(
            'id' => $prog['id'],
            'quantity' => number_format($prog['quantity'], 2),
            'notes' => $prog['notes'],
            'created_at' => date('Y-m-d H:i:s', strtotime($prog['created_at'])),
            'created_by' => $prog['created_by_name']
        );
    }
    
    // If no progress records found, add a default message
    if (empty($progress)) {
        $progress[] = array(
            'id' => 0,
            'quantity' => '0.00',
            'notes' => 'No progress records found',
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $orderData['created_by']
        );
    }
    
    // Debug progress data
    error_log("Progress data: " . json_encode($progress));
    
    $orderData['progress'] = $progress;
    $orderData['completed_quantity'] = number_format($orderData['completed_quantity'], 2);
    $orderData['target_quantity'] = number_format($orderData['target_quantity'], 2);
    
    // Ensure all required fields have default values
    $orderData['product_name'] = !empty($orderData['product_name']) ? $orderData['product_name'] : 'N/A';
    $orderData['status'] = ucfirst(strtolower($orderData['status'] ?? 'draft'));
    $orderData['notes'] = !empty($orderData['notes']) ? $orderData['notes'] : '-';
    
    $response['success'] = true;
    $response['data'] = $orderData;
    
} catch(Exception $e) {
    $response['messages'] = $e->getMessage();
    error_log('Error in fetchSingleProductionOrder.php: ' . $e->getMessage());
}

echo json_encode($response);
$connect->close(); 