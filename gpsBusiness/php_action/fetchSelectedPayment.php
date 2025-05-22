<?php
require_once 'core.php';

$paymentId = $_POST['paymentId'];

$sql = "SELECT * FROM gps_payments WHERE id = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param('i', $paymentId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$stmt->close();
$connect->close();

echo json_encode($row); 