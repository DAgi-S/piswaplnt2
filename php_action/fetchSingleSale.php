<?php
require_once 'core.php';

if(isset($_POST['sale_id'])) {
    $saleId = $_POST['sale_id'];
    
    // Get sale details
    $query = "SELECT 
                so.id,
                so.order_number as sale_number,
                so.order_date as date,
                c.company_name as client_name,
                so.subtotal,
                so.vat_amount as vat,
                so.withholding_amount as withholding,
                so.discount_amount as discount,
                so.grand_total,
                so.paid_amount,
                (so.grand_total - so.paid_amount) as balance,
                so.payment_status,
                u.username as created_by
              FROM sales_orders so
              LEFT JOIN clients c ON so.client_id = c.id
              LEFT JOIN users u ON so.created_by = u.id
              WHERE so.id = ?";
              
    $stmt = $connect->prepare($query);
    $stmt->bind_param("i", $saleId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $sale = $result->fetch_assoc();
        
        // Get products
        $query = "SELECT 
                    p.name,
                    soi.quantity,
                    soi.unit_price,
                    (soi.quantity * soi.unit_price) as total
                  FROM sales_order_items soi
                  LEFT JOIN production_products p ON soi.product_id = p.id
                  WHERE soi.order_id = ?";
                  
        $stmt = $connect->prepare($query);
        $stmt->bind_param("i", $saleId);
        $stmt->execute();
        $productsResult = $stmt->get_result();
        
        $products = array();
        while($row = $productsResult->fetch_assoc()) {
            $products[] = $row;
        }
        
        // Get payments
        $query = "SELECT 
                    payment_date as date,
                    amount,
                    payment_method as method,
                    reference_number as reference,
                    notes
                  FROM sales_payments
                  WHERE order_id = ?
                  ORDER BY payment_date DESC";
                  
        $stmt = $connect->prepare($query);
        $stmt->bind_param("i", $saleId);
        $stmt->execute();
        $paymentsResult = $stmt->get_result();
        
        $payments = array();
        while($row = $paymentsResult->fetch_assoc()) {
            $payments[] = $row;
        }
        
        // Combine all data
        $response = array(
            'success' => true,
            'data' => array_merge($sale, array(
                'products' => $products,
                'payments' => $payments
            ))
        );
    } else {
        $response = array(
            'success' => false,
            'message' => 'Sale not found'
        );
    }
} else {
    $response = array(
        'success' => false,
        'message' => 'Invalid request'
    );
}

echo json_encode($response);
?> 