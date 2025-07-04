<?php
require_once 'core.php';
require_once 'db_connect.php';
header('Content-Type: application/json');

$userId = $_SESSION['userId'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}
$stmt = $connect->prepare("
    SELECT p.permission_name
    FROM permissions p
    JOIN role_permissions rp ON p.permission_id = rp.permission_id
    JOIN users u ON rp.role_id = u.role_id
    WHERE u.user_id = ?
");
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$permissions = [];
while ($row = $result->fetch_assoc()) {
    $permissions[] = $row['permission_name'];
}
echo json_encode(['success' => true, 'permissions' => $permissions]); 