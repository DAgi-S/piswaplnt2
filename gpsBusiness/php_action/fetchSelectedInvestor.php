<?php
require_once 'core.php';

$investorId = $_POST['investorId'];

$sql = "SELECT * FROM gps_investors WHERE id = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param('i', $investorId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$stmt->close();
$connect->close();

echo json_encode($row); 