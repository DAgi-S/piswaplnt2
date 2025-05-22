<?php
require_once 'core.php';

// Prevent any output before our JSON response
ob_start();

try {
    if(isset($_POST['sale_id'])) {
        $saleId = $_POST['sale_id'];
        
        // Get sale details
        $query = "SELECT 
                    so.id,
                    so.order_number as sale_number,
                    so.order_date as date,
                    c.company_name as client_name,
                    so.subtotal,
                    so.tax_amount as vat,
                    so.withholding_amount as withholding,
                    so.discount_amount as discount,
                    so.total_amount as grand_total,
                    so.paid_amount,
                    (so.total_amount - so.paid_amount) as balance,
                    so.payment_status,
                    u.username as created_by
                  FROM sales_orders so
                  LEFT JOIN clients c ON so.client_id = c.id
                  LEFT JOIN users u ON so.created_by = u.user_id
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
                      WHERE soi.sales_order_id = ?";
                      
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
                        sp.payment_date as date,
                        sp.amount,
                        sp.payment_method as method,
                        sp.reference_number as reference,
                        sp.notes,
                        sp.payment_proof,
                        sp.status,
                        sp.account_id,
                        a.account_platform as account_name,
                        a.account_owner,
                        u.username as created_by,
                        sp.created_at
                      FROM sales_payments sp
                      LEFT JOIN accounts a ON sp.account_id = a.id
                      LEFT JOIN users u ON sp.created_by = u.user_id
                      WHERE sp.sales_order_id = ?
                      ORDER BY sp.payment_date DESC";
                      
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

    // Clear any previous output
    ob_clean();
    
    // Set proper JSON header
    header('Content-Type: application/json');
    
    // Output the JSON response
    echo json_encode($response);
    exit;
    
} catch (Exception $e) {
    // Clear any previous output
    ob_clean();
    
    // Set proper JSON header
    header('Content-Type: application/json');
    
    // Return error response
    echo json_encode(array(
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ));
    exit;
}
?> 