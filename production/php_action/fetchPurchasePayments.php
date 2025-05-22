<?php
header('Content-Type: application/json');
require_once 'core.php';

// Check if user has permission to view payments
if (!hasPermission('purchase.payment.view')) {
    echo json_encode(array('success' => false, 'messages' => 'Access Denied'));
    exit();
}

$valid['success'] = array('success' => false, 'messages' => array());

if(isset($_POST['purchaseNumber'])) {
    $purchaseNumber = $_POST['purchaseNumber'];
    
    $sql = "SELECT 
                pp.payment_date,
                pp.amount,
                pp.payment_method,
                pp.reference_number,
                pp.notes,
                COALESCE(pp.payment_proof, 'NO PROOF') as payment_proof,
                pp.created_at
            FROM purchase_payments pp
            JOIN purchases p ON pp.purchase_id = p.id
            WHERE p.purchase_number = ? 
            ORDER BY pp.payment_date DESC";

    $stmt = mysqli_prepare($connect, $sql);
    mysqli_stmt_bind_param($stmt, "s", $purchaseNumber);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $payments = array();
    while($row = mysqli_fetch_assoc($result)) {
        $amount = floatval($row['amount']);
        
        // Get the proof file path
        $proofPath = $row['payment_proof'];
        if($proofPath !== 'NO PROOF') {
            $proofPath = 'uploads/payment_proofs/' . $proofPath;
        }
        
        $payments[] = array(
            'payment_date' => date('Y-m-d', strtotime($row['payment_date'])),
            'amount' => number_format($amount, 2, '.', ''),
            'payment_method' => ucfirst(str_replace('_', ' ', $row['payment_method'])),
            'reference_number' => $row['reference_number'] ? $row['reference_number'] : 'N/A',
            'notes' => $row['notes'] ? $row['notes'] : 'No notes',
            'payment_proof' => $proofPath,
            'created_at' => date('Y-m-d H:i:s', strtotime($row['created_at']))
        );
    }

    if(count($payments) > 0) {
        $valid['success'] = true;
        $valid['payments'] = $payments;
    } else {
        $valid['success'] = false;
        $valid['messages'] = 'No payment records found';
    }

    mysqli_close($connect);

    echo json_encode($valid);
} else {
    $valid['success'] = false;
    $valid['messages'] = 'Invalid request';
    echo json_encode($valid);
} 