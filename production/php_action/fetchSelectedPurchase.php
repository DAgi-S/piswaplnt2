<?php
require_once 'core.php';

// Set proper headers
header('Content-Type: application/json');

$response = array(
    'success' => false,
    'messages' => '',
    'data' => null
);

if(isset($_POST['purchaseNumber'])) {
    try {
        $purchaseNumber = $_POST['purchaseNumber'];
        
        // Fetch purchase details with warehouse info from first purchase item
        $sql = "SELECT p.*, 
                pi.warehouse_id,
                w.name as warehouse_name,
                s.company_name as supplier_name
                FROM purchases p 
                LEFT JOIN purchase_items pi ON p.id = pi.purchase_id AND pi.id = (
                    SELECT MIN(id) FROM purchase_items WHERE purchase_id = p.id
                )
                LEFT JOIN warehouses w ON pi.warehouse_id = w.id 
                LEFT JOIN suppliers s ON p.supplier_id = s.id
                WHERE p.purchase_number = ? AND p.active = 1";
        
        $stmt = $connect->prepare($sql);
        if(!$stmt) {
            throw new Exception("Error preparing purchase query: " . $connect->error);
        }
        
        $stmt->bind_param("s", $purchaseNumber);
        if(!$stmt->execute()) {
            throw new Exception("Error executing purchase query: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        if($result->num_rows === 0) {
            throw new Exception("Purchase not found");
        }
        
        $purchase = $result->fetch_assoc();
        
        // Fetch purchase items
        $sql = "SELECT pi.*, rm.name as material_name, l.id as location_id, l.location_code 
                FROM purchase_items pi 
                LEFT JOIN raw_materials rm ON pi.raw_material_id = rm.id 
                LEFT JOIN storage_locations l ON pi.location_id = l.id 
                WHERE pi.purchase_id = ?";
        
        $stmt = $connect->prepare($sql);
        if(!$stmt) {
            throw new Exception("Error preparing items query: " . $connect->error);
        }
        
        $stmt->bind_param("i", $purchase['id']);
        if(!$stmt->execute()) {
            throw new Exception("Error executing items query: " . $stmt->error);
        }
        
        $itemsResult = $stmt->get_result();
        $items = array();
        while($item = $itemsResult->fetch_assoc()) {
            $items[] = array(
                'raw_material_id' => $item['raw_material_id'],
                'material_name' => $item['material_name'],
                'quantity' => $item['quantity'],
                'rate' => $item['rate'],
                'total' => $item['total'],
                'location_id' => $item['location_id'],
                'location_code' => $item['location_code']
            );
        }
        
        // Prepare response data
        $response['data'] = array(
            'id' => $purchase['id'],
            'purchase_number' => $purchase['purchase_number'],
            'supplier_id' => $purchase['supplier_id'],
            'supplier_name' => $purchase['supplier_name'],
            'purchase_date' => $purchase['purchase_date'],
            'warehouse_id' => $purchase['warehouse_id'],
            'warehouse_name' => $purchase['warehouse_name'],
            'sub_total' => $purchase['sub_total'],
            'vat' => $purchase['vat'],
            'withholding_tax_enabled' => $purchase['withholding_tax_enabled'],
            'withholding_tax_amount' => $purchase['withholding_tax_amount'],
            'grand_total' => $purchase['grand_total'],
            'payment_status' => $purchase['payment_status'],
            'paid_amount' => $purchase['paid_amount'],
            'note' => $purchase['note'],
            'items' => $items
        );
        
        $response['success'] = true;
        
    } catch(Exception $e) {
        $response['messages'] = $e->getMessage();
        error_log("Error in fetchSelectedPurchase.php: " . $e->getMessage());
    }
} else {
    $response['messages'] = "Purchase number not provided";
}

echo json_encode($response);

// Close connection
if(isset($connect)) {
    $connect->close();
} 