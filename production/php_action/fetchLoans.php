<?php
require_once 'core.php';

$sql = "SELECT 
            l.*, 
            CASE 
                WHEN l.borrower_type = 'client' THEN c.client_name
                WHEN l.borrower_type = 'employee' THEN e.first_name || ' ' || e.last_name
                ELSE 'Unknown'
            END as borrower_name,
            COALESCE(SUM(lp.amount), 0) as paid_amount
        FROM loans l
        LEFT JOIN clients c ON l.borrower_id = c.client_id AND l.borrower_type = 'client'
        LEFT JOIN employees e ON l.borrower_id = e.employee_id AND l.borrower_type = 'employee'
        LEFT JOIN loan_payments lp ON l.loan_id = lp.loan_id AND lp.status = 'confirmed'
        GROUP BY l.loan_id, c.client_name, e.first_name, e.last_name
        ORDER BY l.loan_date DESC";

$result = $connect->query($sql);

$output = array('data' => array());

if($result->num_rows > 0) {
    while($row = $result->fetch_array()) {
        $loan_id = $row['loan_id'];
        $borrower_name = $row['borrower_name'];
        $loan_type = ucfirst($row['borrower_type']);
        $loan_amount = $row['loan_amount'];
        $interest_rate = $row['interest_rate'];
        $loan_date = date('d M Y', strtotime($row['loan_date']));
        $due_date = date('d M Y', strtotime($row['due_date']));
        
        // Calculate total amount with interest
        $total_amount = $loan_amount * (1 + ($interest_rate / 100));
        $paid_amount = $row['paid_amount'];
        $remaining_amount = $total_amount - $paid_amount;
        
        // Determine loan status
        $status = '';
        if($remaining_amount <= 0) {
            $status = 'Completed';
        } else if(strtotime($row['due_date']) < strtotime('today')) {
            $status = 'Overdue';
        } else {
            $status = 'Active';
        }
        
        $output['data'][] = array(
            'loan_id' => $loan_id,
            'borrower_name' => $borrower_name,
            'loan_type' => $loan_type,
            'loan_amount' => $loan_amount,
            'interest_rate' => $interest_rate,
            'loan_date' => $loan_date,
            'due_date' => $due_date,
            'status' => $status,
            'total_amount' => $total_amount,
            'paid_amount' => $paid_amount,
            'remaining_amount' => $remaining_amount
        );
    }
}

$connect->close();
echo json_encode($output); 