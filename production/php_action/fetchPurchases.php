<?php
require_once 'core.php';

// Set proper headers
header('Content-Type: application/json');

try {
    $sql = "SELECT 
                p.id,
                p.purchase_number,
                CASE 
                    WHEN p.purchase_date = '0000-00-00' THEN NULL 
                    ELSE DATE_FORMAT(p.purchase_date, '%Y-%m-%d') 
                END as purchase_date,
                p.sub_total,
                p.vat,
                p.grand_total,
                p.payment_status,
                COALESCE(p.paid_amount, 0) as paid_amount,
                s.company_name 
            FROM purchases p 
            INNER JOIN suppliers s ON p.supplier_id = s.id 
            WHERE p.active = 1
            ORDER BY p.purchase_date DESC";

    $result = $connect->query($sql);

    if (!$result) {
        throw new Exception("Error in purchase query: " . $connect->error);
    }

    $output = array('data' => array());

    while($row = $result->fetch_array()) {
        $purchaseId = $row['id'];
        
        // Format numbers
        $subTotal = number_format($row['sub_total'], 2);
        $vat = number_format($row['vat'], 2);
        $grandTotal = number_format($row['grand_total'], 2);
        $paidAmount = number_format($row['paid_amount'], 2);
        
        // Format date
        $purchaseDate = $row['purchase_date'] ? $row['purchase_date'] : '';
        
        // Payment status
        $paidAmountValue = floatval($row['paid_amount']);
        $totalAmount = floatval($row['grand_total']);
        
        if($paidAmountValue >= $totalAmount) {
            $paymentStatus = "paid";
        } else if($paidAmountValue > 0) {
            $paymentStatus = "partially_paid";
        } else {
            $paymentStatus = "unpaid";
        }

        $output['data'][] = array(
            'purchase_number' => $row['purchase_number'],
            'company_name' => $row['company_name'],
            'purchase_date' => $purchaseDate,
            'sub_total' => $row['sub_total'],
            'vat' => $row['vat'],
            'grand_total' => $row['grand_total'],
            'payment_status' => $paymentStatus,
            'paid_amount' => $row['paid_amount']
        );
    }

    echo json_encode($output);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'error' => true,
        'message' => $e->getMessage()
    ));
} finally {
    if (isset($result) && $result instanceof mysqli_result) {
        $result->free();
    }
    if (isset($connect)) {
        $connect->close();
    }
} 