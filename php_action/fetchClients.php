<?php
require_once 'core.php';
require_once 'db_connect.php';
require_once 'middleware.php';

header('Content-Type: application/json');

if (!hasPermission('view_quotations')) {
    echo json_encode([
        'success' => false,
        'messages' => 'Access denied'
    ]);
    exit();
}

$response = array();

try {
    // Fetch active clients
    $sql = "SELECT id, company_name, tin_number, phone, email 
            FROM clients 
            WHERE status = 1 
            ORDER BY company_name ASC";
            
    $result = $connect->query($sql);
    
    if($result) {
        $data = array();
        while($row = $result->fetch_assoc()) {
            $data[] = array(
                'id' => $row['id'],
                'company_name' => $row['company_name'],
                'tin_number' => $row['tin_number'],
                'phone' => $row['phone'],
                'email' => $row['email']
            );
        }
        
        $response['success'] = true;
        $response['data'] = $data;
    } else {
        throw new Exception("Error fetching clients: " . $connect->error);
    }
    
} catch(Exception $e) {
    $response['success'] = false;
    $response['messages'] = $e->getMessage();
}

echo json_encode($response); 