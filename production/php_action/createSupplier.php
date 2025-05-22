<?php
require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());

if($_POST) {
    $companyName = $_POST['companyName'];
    $contactPerson = $_POST['contactPerson'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    
    $sql = "INSERT INTO suppliers (company_name, contact_person, phone, address, active) 
            VALUES ('$companyName', '$contactPerson', '$phone', '$address', 1)";

    if($connect->query($sql) === TRUE) {
        $valid['success'] = true;
        $valid['messages'] = "Supplier added successfully";
    } else {
        $valid['success'] = false;
        $valid['messages'] = "Error while adding supplier";
    }
    
    $connect->close();

    echo json_encode($valid);
} 