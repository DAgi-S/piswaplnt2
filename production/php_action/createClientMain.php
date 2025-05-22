<?php
require_once 'core.php';

header('Content-Type: application/json');

$response = array('success' => false);

if($_POST) {
    // Validate required fields
    if(empty($_POST['companyName'])) {
        $response['messages'] = "Company name is required";
        echo json_encode($response);
        exit();
    }

    try {
        $companyName = $_POST['companyName'];
        $tinNumber = isset($_POST['tinNumber']) ? $_POST['tinNumber'] : null;
        $phone = isset($_POST['phone']) ? $_POST['phone'] : null;
        $email = isset($_POST['email']) ? $_POST['email'] : null;
        $address = isset($_POST['address']) ? $_POST['address'] : null;
        $status = isset($_POST['status']) ? intval($_POST['status']) : 1;

        $sql = "INSERT INTO clients (
                    company_name, 
                    tin_number, 
                    phone, 
                    email, 
                    address, 
                    status
                ) VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("sssssi", 
            $companyName,
            $tinNumber,
            $phone,
            $email,
            $address,
            $status
        );
        
        if($stmt->execute()) {
            $response['success'] = true;
            $response['messages'] = "Client successfully added";
        } else {
            throw new Exception($stmt->error);
        }
        
        $stmt->close();
        
    } catch(Exception $e) {
        $response['messages'] = "Error adding client: " . $e->getMessage();
    }
} else {
    $response['messages'] = "Invalid request";
}

$connect->close();
echo json_encode($response); 