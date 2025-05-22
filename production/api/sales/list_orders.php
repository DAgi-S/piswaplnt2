<?php
require_once "../config.php";
require_once "../utils/auth_check.php";

$token = $_GET['token'] ?? '';
if (!validate_token($token)) {
    die(json_encode(["status" => false, "message" => "Unauthorized"]));
}

$result = $conn->query("SELECT id, customer_id, total_amount, created_at FROM sales ORDER BY created_at DESC LIMIT 50");
$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode(["status" => true, "data" => $data]);
?>