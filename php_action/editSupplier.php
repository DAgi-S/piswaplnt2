<?php
require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());

if($_POST) {
    $supplierId = $_POST['supplierId'];
    $companyName = $_POST['editCompanyName'];
    $contactPerson = $_POST['editContactPerson'];
    $email = $_POST['editEmail'];
    $phone = $_POST['editPhone'];
    $address = $_POST['editAddress'];
    $active = $_POST['editActive'];

    $sql = "UPDATE suppliers SET company_name = '$companyName', contact_person = '$contactPerson', 
            email = '$email', phone = '$phone', address = '$address', active = '$active' 
            WHERE id = $supplierId";

    if($connect->query($sql) === TRUE) {
        $valid['success'] = true;
        $valid['messages'] = "Successfully updated";    
    } else {
        $valid['success'] = false;
        $valid['messages'] = "Error while updating supplier";
    }

    $connect->close();

    echo json_encode($valid);
} 