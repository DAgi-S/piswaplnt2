<?php
require_once 'core.php';

header('Content-Type: application/json');

if($_POST && isset($_POST['purchaseNumber'])) {
    $purchaseNumber = $_POST['purchaseNumber'];
    $response = array();
    
    try {
        // First, get the purchase header information
        $sql = "SELECT p.*, s.company_name as supplier_name 
                FROM purchases p 
                LEFT JOIN suppliers s ON p.supplier_id = s.id 
                WHERE p.purchase_number = ?";
        
        $stmt = $connect->prepare($sql);
        if(!$stmt) {
            throw new Exception("Error preparing query: " . $connect->error);
        }
        
        $stmt->bind_param("s", $purchaseNumber);
        if(!$stmt->execute()) {
            throw new Exception("Error executing query: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            $purchaseData = $result->fetch_assoc();
            
            // Now get the purchase items with location information
            $sql = "SELECT pi.*, rm.name as material_name, 
                    sl.location_code, wz.name as zone_name 
                    FROM purchase_items pi 
                    LEFT JOIN raw_materials rm ON pi.raw_material_id = rm.id 
                    LEFT JOIN storage_locations sl ON pi.location_id = sl.id 
                    LEFT JOIN warehouse_zones wz ON sl.zone_id = wz.id 
                    WHERE pi.purchase_id = ?";
            
            $stmt = $connect->prepare($sql);
            if(!$stmt) {
                throw new Exception("Error preparing items query: " . $connect->error);
            }
            
            $stmt->bind_param("i", $purchaseData['id']);
            if(!$stmt->execute()) {
                throw new Exception("Error executing items query: " . $stmt->error);
            }
            
            $itemsResult = $stmt->get_result();
            
            $items = array();
            while($row = $itemsResult->fetch_assoc()) {
                // Format the location name
                $locationName = 'N/A';
                if ($row['location_code'] && $row['zone_name']) {
                    $locationName = $row['location_code'] . ' (' . $row['zone_name'] . ')';
                }
                
                // Format the item data
                $items[] = array(
                    'material_name' => $row['material_name'] ?: 'N/A',
                    'quantity' => $row['quantity'],
                    'rate' => number_format($row['rate'], 2),
                    'total' => number_format($row['quantity'] * $row['rate'], 2),
                    'location_name' => $locationName
                );
            }
            
            // Calculate VAT amount
            $vatAmount = ($purchaseData['sub_total'] * $purchaseData['vat']) / 100;
            
            $response['success'] = true;
            $response['data'] = array(
                'purchase_number' => $purchaseData['purchase_number'],
                'supplier_name' => $purchaseData['supplier_name'],
                'purchase_date' => $purchaseData['purchase_date'] && $purchaseData['purchase_date'] != '0000-00-00' ? 
                    date('Y-m-d', strtotime($purchaseData['purchase_date'])) : 
                    date('Y-m-d'),
                'warehouse_name' => 'Main Warehouse', // Default for now
                'sub_total' => number_format($purchaseData['sub_total'], 2),
                'vat_amount' => number_format($vatAmount, 2),
                'vat_percentage' => $purchaseData['vat'],
                'withholding_tax_amount' => $purchaseData['withholding_tax_amount'] ? number_format($purchaseData['withholding_tax_amount'], 2) : '0.00',
                'grand_total' => number_format($purchaseData['grand_total'], 2),
                'note' => $purchaseData['note'],
                'items' => $items
            );
        } else {
            throw new Exception("Purchase not found");
        }
        
    } catch(Exception $e) {
        $response['success'] = false;
        $response['messages'] = $e->getMessage();
        error_log("Error in fetchPurchaseDetails.php: " . $e->getMessage());
    }
    
    echo json_encode($response);
    exit();
}

echo json_encode(array('success' => false, 'messages' => 'Invalid request'));
exit(); 