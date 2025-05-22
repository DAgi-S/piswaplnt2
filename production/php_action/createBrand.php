<?php
require_once 'core.php';

$response = array('success' => false, 'messages' => '');

if (!hasPermission('settings.brands.manage')) {
    $response['success'] = false;
    $response['messages'] = 'Access denied. Permission to manage brands required.';
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;
    if ($name === '') {
        $response['messages'] = 'Brand name is required.';
    } else {
        $sql = "INSERT INTO brands (name, description, status, deleted) VALUES (?, ?, ?, 0)";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param('ssi', $name, $description, $status);
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['messages'] = 'Brand added successfully.';
        } else {
            $response['messages'] = 'Error adding brand: ' . $connect->error;
        }
        $stmt->close();
    }
}
$connect->close();
header('Content-Type: application/json');
echo json_encode($response);