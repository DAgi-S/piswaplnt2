<?php
require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());

if($_POST) {
    $companyName = $_POST['companyName'];
    $contactPerson = $_POST['contactPerson'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    $sql = "INSERT INTO suppliers (company_name, contact_person, email, phone, address) 
            VALUES ('$companyName', '$contactPerson', '$email', '$phone', '$address')";

    if($connect->query($sql) === TRUE) {
        $valid['success'] = true;
        $valid['messages'] = "Successfully added";
        
        // Get the new supplier data
        $newSupplierId = $connect->insert_id;
        $sql = "SELECT * FROM suppliers WHERE id = $newSupplierId";
        $result = $connect->query($sql);
        $valid['supplier'] = $result->fetch_assoc();
    } else {
        $valid['success'] = false;
        $valid['messages'] = "Error while adding supplier";
    }

    $connect->close();

    echo json_encode($valid);
} 