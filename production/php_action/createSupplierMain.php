<?php
require_once 'core.php';

header('Content-Type: application/json');

$valid['success'] = false;
$valid['messages'] = '';

if($_POST) {
    $companyName = $_POST['companyName'];
    $contactPerson = isset($_POST['contactPerson']) ? $_POST['contactPerson'] : null;
    $phone = isset($_POST['phone']) ? $_POST['phone'] : null;
    $email = isset($_POST['email']) ? $_POST['email'] : null;
    $address = isset($_POST['address']) ? $_POST['address'] : null;
    $tin = isset($_POST['tin']) ? $_POST['tin'] : null;
    $status = isset($_POST['status']) ? ($_POST['status'] === 'active' ? 1 : 0) : 1;
    
    try {
        $sql = "INSERT INTO suppliers (
            company_name, 
            contact_person, 
            phone, 
            email, 
            address, 
            tin,
            active
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $connect->prepare($sql);
        
        if(!$stmt) {
            throw new Exception("Error preparing statement: " . $connect->error);
        }

        $stmt->bind_param("sssssii", 
            $companyName,
            $contactPerson,
            $phone,
            $email,
            $address,
            $tin,
            $status
        );

        if($stmt->execute()) {
            $valid['success'] = true;
            $valid['messages'] = "Supplier added successfully";
        } else {
            throw new Exception("Error executing statement: " . $stmt->error);
        }

        $stmt->close();
        
    } catch(Exception $e) {
        $valid['success'] = false;
        $valid['messages'] = $e->getMessage();
        error_log("Error in createSupplierMain.php: " . $e->getMessage());
    }

    $connect->close();
}

// Ensure clean output
ob_clean();
echo json_encode($valid); 