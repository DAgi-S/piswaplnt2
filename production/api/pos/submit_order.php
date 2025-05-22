<?php
require_once "../config.php";
require_once "../utils/auth_check.php";

$token = $_POST['token'] ?? '';
if (!validate_token($token)) {
    die(json_encode(["status" => false, "message" => "Unauthorized"]));
}

$customer_id = $_POST['customer_id'];
$total = $_POST['total'];

$stmt = $conn->prepare("INSERT INTO pos_orders (customer_id, total, created_at) VALUES (?, ?, NOW())");
$stmt->bind_param("id", $customer_id, $total);

if ($stmt->execute()) {
    echo json_encode(["status" => true, "message" => "Order submitted successfully"]);
} else {
    echo json_encode(["status" => false, "message" => "Failed to submit order"]);
}
?>