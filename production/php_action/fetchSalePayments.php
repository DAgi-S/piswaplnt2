<?php
require_once 'core.php';

header('Content-Type: application/json');

$output = array('success' => false, 'messages' => array());

if(isset($_POST['invoiceNumber'])) {
    try {
        $orderNumber = $_POST['invoiceNumber'];
        
        $sql = "SELECT sp.payment_date, sp.amount, sp.payment_method, sp.reference_number, 
                       sp.notes, sp.created_at
                FROM sales_payments sp
                JOIN sales_orders so ON sp.sales_order_id = so.id
                WHERE so.order_number = ?
                ORDER BY sp.payment_date DESC";
        
        $stmt = $connect->prepare($sql);
        if(!$stmt) {
            throw new Exception("Error preparing query: " . $connect->error);
        }
        
        $stmt->bind_param("s", $orderNumber);
        if(!$stmt->execute()) {
            throw new Exception("Error executing query: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        $payments = array();
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $payments[] = array(
                    'date' => date('Y-m-d', strtotime($row['payment_date'])),
                    'amount' => number_format($row['amount'], 2),
                    'payment_method' => ucfirst($row['payment_method']),
                    'reference_number' => $row['reference_number'] ?: 'N/A',
                    'notes' => $row['notes'] ?: 'No notes',
                    'created_at' => date('Y-m-d H:i:s', strtotime($row['created_at']))
                );
            }
            $output['success'] = true;
            $output['payments'] = $payments;
        } else {
            $output['messages'] = 'No payment records found';
        }
        
    } catch(Exception $e) {
        $output['messages'] = $e->getMessage();
        error_log("Error in fetchSalePayments.php: " . $e->getMessage());
    }
}

echo json_encode($output); 