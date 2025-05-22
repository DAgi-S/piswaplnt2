<?php
require_once 'core.php';
require_once 'db_connect.php';

if($_POST) {
    $categoryId = $_POST['categoryId'];
    $sql = "SELECT * FROM digital_categories WHERE category_id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    echo json_encode($row);

    $stmt->close();
    $connect->close();
} 