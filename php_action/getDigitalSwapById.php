<?php
require_once 'core.php';
require_once 'db_connect.php';

// Clear any previous output
while (ob_get_level()) {
    ob_end_clean();
}

$response = array(
    'success' => false,
    'data' => null,
    'messages' => ''
);

try {
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        throw new Exception("Digital Swap ID is required");
    }

    $id = (int)$_POST['id'];

    $sql = "SELECT d.*, 
            a.account_owner,
            tt.name as type_name,
            p.name as platform_name
        FROM digitalswap d
        LEFT JOIN accounts a ON d.account_id = a.id
        LEFT JOIN transaction_types tt ON d.type_id = tt.id
        LEFT JOIN platforms p ON d.platform_id = p.id
        WHERE d.id = ?";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Digital Swap not found");
    }

    $data = $result->fetch_assoc();
    
    // Format data
    $data['transaction_date'] = date('Y-m-d', strtotime($data['transaction_date']));
    $data['amount'] = number_format($data['amount'], 2, '.', '');

    $response['success'] = true;
    $response['data'] = $data;

} catch (Exception $e) {
    $response['messages'] = $e->getMessage();
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit(); 