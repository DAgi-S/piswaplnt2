<?php
require_once 'core.php';

$categoryId = $_POST['categoryId'];

$sql = "SELECT * FROM gps_expense_categories WHERE id = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param('i', $categoryId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$stmt->close();
$connect->close();

echo json_encode($row); 