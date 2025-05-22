<?php
header('Content-Type: application/json');
require_once 'core.php';

// Check if user has permission to view payments
if (!hasPermission('purchase.payment.view')) {
    echo json_encode(array('data' => array()));
    exit();
}

$response = array('data' => array());

// Get date range filter if provided
$dateRange = isset($_POST['dateRange']) ? $_POST['dateRange'] : '';
$dateFilter = '';

if (!empty($dateRange)) {
    $dates = explode(' - ', $dateRange);
    if (count($dates) == 2) {
        $startDate = date('Y-m-d', strtotime($dates[0]));
        $endDate = date('Y-m-d', strtotime($dates[1]));
        $dateFilter = " AND pp.payment_date BETWEEN '$startDate' AND '$endDate'";
    }
}

$sql = "SELECT 
            pp.id,
            pp.payment_date,
            p.purchase_number,
            s.company_name as supplier_name,
            pp.amount,
            pp.payment_method,
            pp.reference_number,
            pp.notes,
            COALESCE(pp.payment_proof, 'NO PROOF') as payment_proof,
            pp.created_at
        FROM purchase_payments pp
        JOIN purchases p ON pp.purchase_id = p.id
        JOIN suppliers s ON p.supplier_id = s.id
        WHERE 1=1 $dateFilter
        ORDER BY pp.payment_date DESC";

$result = $connect->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Format payment proof path - fix the path to use correct directory
        if($row['payment_proof'] !== 'NO PROOF') {
            // Remove any duplicate path elements and set the correct base path
            $row['payment_proof'] = '../uploads/payment_proofs/' . basename($row['payment_proof']);
        }
        
        // Format payment method display
        $row['payment_method'] = ucfirst(str_replace('_', ' ', $row['payment_method']));
        
        // Format reference number
        $row['reference_number'] = $row['reference_number'] ? $row['reference_number'] : 'N/A';
        
        // Format notes
        $row['notes'] = $row['notes'] ? $row['notes'] : 'No notes';
        
        $response['data'][] = $row;
    }
}

mysqli_close($connect);
echo json_encode($response); 