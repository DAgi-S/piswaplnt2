<?php
require_once 'core.php';

// Set proper headers for JSON response
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$response = array();

try {
    // Check database connection
    if ($connect->connect_error) {
        throw new Exception("Connection failed: " . $connect->connect_error);
    }

    $sql = "SELECT p.*, s.company_name as supplier_name,
            GROUP_CONCAT(pr.name SEPARATOR ', ') as product_names
            FROM purchases p
            LEFT JOIN suppliers s ON p.supplier_id = s.id
            LEFT JOIN purchase_items pi ON p.id = pi.purchase_id
            LEFT JOIN products pr ON pi.product_id = pr.product_id
            WHERE p.active = 1
            GROUP BY p.id
            ORDER BY p.created_at DESC";
            
    $result = $connect->query($sql);
    
    $data = array();
    while($row = $result->fetch_assoc()) {
        $data[] = array(
            'id' => $row['id'],
            'purchase_number' => $row['purchase_number'],
            'purchase_date' => date('d-m-Y', strtotime($row['purchase_date'])),
            'supplier_name' => $row['supplier_name'] ?: 'Default Supplier',
            'product_names' => $row['product_names'] ?: 'N/A',
            'sub_total' => $row['sub_total'],
            'vat' => $row['vat'],
            'withholding_tax_enabled' => $row['withholding_tax_enabled'],
            'withholding_tax_amount' => $row['withholding_tax_amount'],
            'grand_total' => $row['grand_total'],
            'payment_status' => $row['payment_status']
        );
    }
    
    $response['data'] = $data;
    
} catch(Exception $e) {
    $response['error'] = true;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);

if (isset($connect)) {
    $connect->close();
} 