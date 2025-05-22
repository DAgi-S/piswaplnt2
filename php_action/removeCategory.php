<?php
require_once 'core.php';
require_once 'db_connect.php';

$valid['success'] = array('success' => false, 'messages' => array());

if($_POST) {
    $categoryId = $_POST['categoryId'];

    $sql = "DELETE FROM digital_categories WHERE category_id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $categoryId);

    if($stmt->execute()) {
        // Also remove category mappings
        $sql = "DELETE FROM digital_transaction_categories WHERE category_id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("i", $categoryId);
        $stmt->execute();

        $valid['success'] = true;
        $valid['messages'] = "Category successfully removed";
    } else {
        $valid['success'] = false;
        $valid['messages'] = "Error while removing category";
    }

    $stmt->close();
    $connect->close();

    echo json_encode($valid);
} 