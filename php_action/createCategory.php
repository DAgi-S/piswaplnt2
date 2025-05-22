<?php
require_once 'core.php';
require_once 'db_connect.php';

$valid['success'] = array('success' => false, 'messages' => array());

if($_POST) {
    $categoryName = $_POST['categoryName'];
    $description = $_POST['description'];

    $sql = "INSERT INTO digital_categories (category_name, description) VALUES (?, ?)";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("ss", $categoryName, $description);

    if($stmt->execute()) {
        $valid['success'] = true;
        $valid['messages'] = "Category successfully created";
    } else {
        $valid['success'] = false;
        $valid['messages'] = "Error while creating category";
    }

    $stmt->close();
    $connect->close();

    echo json_encode($valid);
} 