<?php
require_once 'core.php';

header('Content-Type: application/json');

$response = array('success' => false);

if(isset($_POST['clientId'])) {
    $clientId = intval($_POST['clientId']);
    
    try {
        $sql = "SELECT id, company_name, tin_number, phone, email, address, status 
                FROM clients 
                WHERE id = ?";
        
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("i", $clientId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($row = $result->fetch_assoc()) {
            $response = array(
                'success' => true,
                'id' => $row['id'],
                'company_name' => $row['company_name'],
                'tin_number' => $row['tin_number'],
                'phone' => $row['phone'],
                'email' => $row['email'],
                'address' => $row['address'],
                'status' => $row['status']
            );
        }
        
        $stmt->close();
    } catch(Exception $e) {
        $response['messages'] = "Error fetching client details: " . $e->getMessage();
    }
} else {
    $response['messages'] = "Client ID not provided";
}

$connect->close();
echo json_encode($response); 