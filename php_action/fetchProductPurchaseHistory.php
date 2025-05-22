<?php
require_once 'core.php';

// Set proper headers for JSON response
header('Content-Type: application/json');

$response = array();

if(isset($_POST['productId'])) {
    try {
        $productId = intval($_POST['productId']);
        
        $sql = "SELECT 
                    pi.*, 
                    p.purchase_number,
                    p.purchase_date,
                    s.company_name as supplier_name,
                    (pi.quantity * pi.rate) as total
                FROM purchase_items pi
                JOIN purchases p ON pi.purchase_id = p.id
                LEFT JOIN suppliers s ON p.supplier_id = s.id
                WHERE pi.product_id = ?
                ORDER BY p.purchase_date DESC";
        
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = array();
        while($row = $result->fetch_assoc()) {
            $data[] = array(
                'purchase_date' => date('Y-m-d', strtotime($row['purchase_date'])),
                'purchase_number' => $row['purchase_number'],
                'supplier_name' => $row['supplier_name'] ?: 'N/A',
                'quantity' => $row['quantity'],
                'rate' => number_format($row['rate'], 2),
                'total' => number_format($row['total'], 2)
            );
        }
        
        $response['data'] = $data;
        
    } catch(Exception $e) {
        $response['error'] = true;
        $response['message'] = $e->getMessage();
    }
} else {
    $response['error'] = true;
    $response['message'] = "Product ID is required";
}

echo json_encode($response);

if (isset($connect)) {
    $connect->close();
} 