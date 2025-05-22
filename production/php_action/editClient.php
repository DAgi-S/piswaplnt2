<?php
require_once 'core.php';

header('Content-Type: application/json');

$response = array('success' => false);

if($_POST) {
    // Validate required fields
    if(empty($_POST['editCompanyName']) || empty($_POST['clientId'])) {
        $response['messages'] = "Company name and client ID are required";
        echo json_encode($response);
        exit();
    }

    try {
        $clientId = intval($_POST['clientId']);
        $companyName = $_POST['editCompanyName'];
        $tinNumber = isset($_POST['editTinNumber']) ? $_POST['editTinNumber'] : null;
        $phone = isset($_POST['editPhone']) ? $_POST['editPhone'] : null;
        $email = isset($_POST['editEmail']) ? $_POST['editEmail'] : null;
        $address = isset($_POST['editAddress']) ? $_POST['editAddress'] : null;
        $status = isset($_POST['editStatus']) ? intval($_POST['editStatus']) : 1;

        $sql = "UPDATE clients 
                SET company_name = ?,
                    tin_number = ?,
                    phone = ?,
                    email = ?,
                    address = ?,
                    status = ?
                WHERE id = ?";
        
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("sssssii", 
            $companyName,
            $tinNumber,
            $phone,
            $email,
            $address,
            $status,
            $clientId
        );
        
        if($stmt->execute()) {
            $response['success'] = true;
            $response['messages'] = "Client successfully updated";
        } else {
            throw new Exception($stmt->error);
        }
        
        $stmt->close();
        
    } catch(Exception $e) {
        $response['messages'] = "Error updating client: " . $e->getMessage();
    }
} else {
    $response['messages'] = "Invalid request";
}

$connect->close();
echo json_encode($response); 