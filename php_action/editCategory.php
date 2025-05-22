<?php
require_once 'core.php';
require_once 'db_connect.php';

$valid['success'] = array('success' => false, 'messages' => array());

if($_POST) {
    $categoryId = $_POST['categoryId'];
    $categoryName = $_POST['editCategoryName'];
    $description = $_POST['editDescription'];

    $sql = "UPDATE digital_categories SET category_name = ?, description = ? WHERE category_id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("ssi", $categoryName, $description, $categoryId);

    if($stmt->execute()) {
        $valid['success'] = true;
        $valid['messages'] = "Category successfully updated";
    } else {
        $valid['success'] = false;
        $valid['messages'] = "Error while updating category";
    }

    $stmt->close();
    $connect->close();

    echo json_encode($valid);
} 