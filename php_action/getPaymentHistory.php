<?php
require_once 'core.php';

// Set header to return JSON
header('Content-Type: application/json');

if(!isset($_POST['purchaseId'])) {
    echo json_encode(array(
        'success' => false,
        'messages' => 'Purchase ID is required'
    ));
    exit();
}

$purchaseId = (int)$_POST['purchaseId'];

try {
    // Get purchase details and its payments
    $sql = "SELECT pp.*, p.grand_total, p.payment_status 
            FROM purchase_payments pp
            JOIN purchases p ON pp.purchase_id = p.id
            WHERE pp.purchase_id = ?
            ORDER BY pp.payment_date DESC, pp.created_at DESC";
            
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $purchaseId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $payments = array();
    $totalPaid = 0;
    
    while($row = $result->fetch_assoc()) {
        $payments[] = array(
            'payment_date' => date('Y-m-d', strtotime($row['payment_date'])),
            'amount' => $row['amount'],
            'payment_method' => $row['payment_method'],
            'reference_number' => $row['reference_number'],
            'notes' => $row['notes'],
            'created_at' => $row['created_at']
        );
        $totalPaid += $row['amount'];
    }
    
    echo json_encode(array(
        'success' => true,
        'payments' => $payments,
        'total_paid' => $totalPaid
    ));

} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'messages' => $e->getMessage()
    ));
}

$connect->close(); 