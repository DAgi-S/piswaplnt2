<?php
require_once "../config.php";
require_once "../utils/auth_check.php";

$token = $_GET['token'] ?? '';
if (!validate_token($token)) {
    die(json_encode(["status" => false, "message" => "Unauthorized"]));
}

$data = [
    "sales_today" => 0,
    "orders_today" => 0,
    "revenue" => 0
];

$res = $conn->query("SELECT SUM(total_amount) as sales_today FROM sales WHERE DATE(created_at) = CURDATE()");
if ($row = $res->fetch_assoc()) {
    $data['sales_today'] = $row['sales_today'] ?? 0;
}

echo json_encode(["status" => true, "data" => $data]);
?>