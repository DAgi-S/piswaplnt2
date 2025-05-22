<?php
require_once 'core.php';

// Prepare response array
$response = array(
    'success' => false,
    'messages' => '',
    'data' => array()
);

if(isset($_POST['production_order_id'])) {
    $productionOrderId = filter_var($_POST['production_order_id'], FILTER_VALIDATE_INT);
    
    if(!$productionOrderId) {
        $response['messages'] = 'Invalid production order ID';
        echo json_encode($response);
        exit();
    }
    
    try {
        // Get progress history with user information
        $sql = "SELECT 
                    pp.*,
                    u.username as created_by_name,
                    po.target_quantity,
                    po.status as order_status,
                    (SELECT SUM(quantity) FROM production_progress 
                     WHERE production_order_id = pp.production_order_id 
                     AND created_at <= pp.created_at) as cumulative_progress
                FROM production_progress pp
                LEFT JOIN users u ON pp.created_by = u.user_id
                LEFT JOIN production_orders po ON pp.production_order_id = po.id
                WHERE pp.production_order_id = ?
                ORDER BY pp.created_at DESC";
        
        $stmt = $connect->prepare($sql);
        $stmt->bind_param('i', $productionOrderId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while($row = $result->fetch_assoc()) {
            // Calculate progress percentage
            $progressPercentage = ($row['cumulative_progress'] / $row['target_quantity']) * 100;
            
            $response['data'][] = array(
                'id' => $row['id'],
                'quantity' => number_format($row['quantity'], 2),
                'cumulative_quantity' => number_format($row['cumulative_progress'], 2),
                'progress_percentage' => number_format($progressPercentage, 1),
                'notes' => $row['notes'],
                'created_at' => date('Y-m-d H:i:s', strtotime($row['created_at'])),
                'created_by' => $row['created_by_name'],
                'order_status' => $row['order_status']
            );
        }
        
        $response['success'] = true;
        
    } catch(Exception $e) {
        $response['messages'] = 'Error fetching progress history: ' . $e->getMessage();
    }
}

echo json_encode($response); 