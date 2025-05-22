<?php
require_once 'core.php';
require_once 'db_connect.php';

// Set header as JSON
header('Content-Type: application/json');

$response = array();

try {
    // Prepare the SQL query with joins to get all necessary information
    $sql = "SELECT 
                lp.payment_id,
                lp.loan_id,
                lp.payment_amount,
                lp.payment_date,
                lp.reference_number,
                lp.notes,
                c.client_name,
                pm.payment_method_name as payment_method,
                a.account_name
            FROM loan_payments lp
            LEFT JOIN loans l ON lp.loan_id = l.loan_id
            LEFT JOIN clients c ON l.client_id = c.client_id
            LEFT JOIN payment_methods pm ON lp.payment_method_id = pm.payment_method_id
            LEFT JOIN accounts a ON lp.account_id = a.account_id
            ORDER BY lp.payment_date DESC, lp.payment_id DESC";

    $stmt = $connect->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = array();
    while($row = $result->fetch_assoc()) {
        $data[] = array(
            'payment_id' => $row['payment_id'],
            'loan_id' => $row['loan_id'],
            'client_name' => $row['client_name'],
            'payment_amount' => $row['payment_amount'],
            'payment_date' => date('Y-m-d', strtotime($row['payment_date'])),
            'payment_method' => $row['payment_method'],
            'account_name' => $row['account_name'],
            'reference_number' => $row['reference_number'],
            'notes' => $row['notes']
        );
    }
    
    $response = array(
        "draw" => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
        "recordsTotal" => count($data),
        "recordsFiltered" => count($data),
        "data" => $data
    );

} catch(Exception $e) {
    $response = array(
        "draw" => 0,
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => array(),
        "error" => $e->getMessage()
    );
}

// Add to changelog
$userId = $_SESSION['userId'];
$log_action = "Fetched loan payments list";
$log_data = json_encode(array('user_id' => $userId));

$log_sql = "INSERT INTO changelog (user_id, action, action_data, created_at) VALUES (?, ?, ?, NOW())";
$log_stmt = $connect->prepare($log_sql);
$log_stmt->bind_param("iss", $userId, $log_action, $log_data);
$log_stmt->execute();

echo json_encode($response);
$connect->close(); 