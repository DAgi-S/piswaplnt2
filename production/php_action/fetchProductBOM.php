<?php
require_once 'core.php';
require_once 'db_connect.php';

header('Content-Type: application/json');

$response = array(
    'success' => false,
    'messages' => '',
    'data' => array()
);

if(isset($_POST['product_id'])) {
    $productId = intval($_POST['product_id']);
    
    try {
        // Get product BOM with current stock levels
        $sql = "SELECT 
                    pb.id,
                    pb.material_id,
                    rm.material_code,
                    rm.name,
                    pb.quantity_required,
                    pb.wastage_percent,
                    rm.current_stock,
                    rm.min_stock_level,
                    COALESCE(rm.reserved_quantity, 0) as reserved_quantity,
                    (rm.current_stock - COALESCE(rm.reserved_quantity, 0)) as available_stock
                FROM product_bom pb
                JOIN raw_materials rm ON rm.id = pb.material_id
                WHERE pb.product_id = ?
                AND pb.status = 'active'
                AND rm.status = 'active'
                ORDER BY rm.material_code";
        
        $stmt = $connect->prepare($sql);
        
        if($stmt) {
            $stmt->bind_param('i', $productId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $response['data'][] = array(
                        'id' => $row['material_id'],
                        'material_code' => $row['material_code'],
                        'name' => $row['name'],
                        'quantity_required' => $row['quantity_required'],
                        'wastage_percent' => $row['wastage_percent'],
                        'current_stock' => $row['current_stock'],
                        'reserved_quantity' => $row['reserved_quantity'],
                        'available_stock' => $row['available_stock'],
                        'min_stock_level' => $row['min_stock_level'],
                        'stock_status' => $row['current_stock'] <= $row['min_stock_level'] ? 'low' : 'ok'
                    );
                }
                $response['success'] = true;
            } else {
                $response['messages'] = 'No materials found for this product';
            }
            
            $stmt->close();
        } else {
            throw new Exception("Error preparing query: " . $connect->error);
        }
        
    } catch(Exception $e) {
        $response['messages'] = $e->getMessage();
        error_log('Error in fetchProductBOM.php: ' . $e->getMessage());
    }
} else {
    $response['messages'] = 'Product ID not provided';
}

echo json_encode($response);
$connect->close(); 