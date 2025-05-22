<?php
require_once 'core.php';
require_once 'db_connect.php';
require_once 'middleware.php';

header('Content-Type: application/json');

if (!hasPermission('manage_quotations')) {
    echo json_encode([
        'success' => false,
        'messages' => 'Access denied'
    ]);
    exit();
}

if($_POST) {
    $response = array();
    
    try {
        if(empty($_POST['companyName'])) {
            throw new Exception("Company name is required");
        }

        $companyName = mysqli_real_escape_string($connect, trim($_POST['companyName']));
        $tinNumber = mysqli_real_escape_string($connect, trim($_POST['tinNumber']));
        $address = mysqli_real_escape_string($connect, trim($_POST['address']));
        $phone = mysqli_real_escape_string($connect, trim($_POST['phone']));
        $email = mysqli_real_escape_string($connect, trim($_POST['email']));

        // Check if client already exists
        $stmt = $connect->prepare("SELECT id FROM clients WHERE company_name = ?");
        $stmt->bind_param("s", $companyName);
        $stmt->execute();
        if($stmt->get_result()->num_rows > 0) {
            throw new Exception("Client already exists");
        }

        // Create new client
        $stmt = $connect->prepare("INSERT INTO clients (company_name, tin_number, address, phone, email, status) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->bind_param("sssss", $companyName, $tinNumber, $address, $phone, $email);
        
        if($stmt->execute()) {
            $response['success'] = true;
            $response['messages'] = "Client added successfully";
            $response['clientId'] = $connect->insert_id;
        } else {
            throw new Exception("Error creating client: " . $stmt->error);
        }

    } catch(Exception $e) {
        $response['success'] = false;
        $response['messages'] = $e->getMessage();
    }

    echo json_encode($response);
} 