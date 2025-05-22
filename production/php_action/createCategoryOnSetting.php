<?php
require_once 'core.php';

$response = array('success' => false, 'message' => '');

if (!isset($_POST['category_table'])) {
    $response['message'] = 'Category table is required.';
    echo json_encode($response);
    exit();
}

$table = $_POST['category_table'];
$now = date('Y-m-d H:i:s');

try {
    switch ($table) {
        case 'categories':
            $name = trim($_POST['name'] ?? '');
            $type = trim($_POST['type'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $status = intval($_POST['status'] ?? 1);
            if ($name === '' || $type === '') {
                throw new Exception('Name and type are required.');
            }
            $sql = "INSERT INTO categories (name, type, description, status, deleted, created_at, updated_at) VALUES (?, ?, ?, ?, 0, ?, ?)";
            $stmt = $connect->prepare($sql);
            $stmt->bind_param('sssiss', $name, $type, $description, $status, $now, $now);
            break;
        case 'digital_categories':
            $category_name = trim($_POST['category_name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            if ($category_name === '') {
                throw new Exception('Category name is required.');
            }
            $sql = "INSERT INTO digital_categories (category_name, description, created_at, updated_at) VALUES (?, ?, ?, ?)";
            $stmt = $connect->prepare($sql);
            $stmt->bind_param('ssss', $category_name, $description, $now, $now);
            break;
        case 'payment_categories':
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $type = trim($_POST['type'] ?? '');
            $status = intval($_POST['status'] ?? 1);
            if ($name === '' || $type === '') {
                throw new Exception('Name and type are required.');
            }
            $sql = "INSERT INTO payment_categories (name, description, type, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $connect->prepare($sql);
            $stmt->bind_param('sssiss', $name, $description, $type, $status, $now, $now);
            break;
        case 'production_categories':
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $status = intval($_POST['status'] ?? 1);
            $created_by = intval($_SESSION['userId'] ?? 0);
            if ($name === '') {
                throw new Exception('Name is required.');
            }
            $sql = "INSERT INTO production_categories (name, description, status, created_at, updated_at, created_by) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $connect->prepare($sql);
            $stmt->bind_param('ssisii', $name, $description, $status, $now, $now, $created_by);
            break;
        case 'raw_material_categories':
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $status = intval($_POST['status'] ?? 1);
            $created_by = intval($_SESSION['userId'] ?? 0);
            if ($name === '') {
                throw new Exception('Name is required.');
            }
            $sql = "INSERT INTO raw_material_categories (name, description, status, created_at, updated_at, created_by) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $connect->prepare($sql);
            $stmt->bind_param('ssisii', $name, $description, $status, $now, $now, $created_by);
            break;
        case 'system_config_categories':
            $category_name = trim($_POST['category_name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $display_order = intval($_POST['display_order'] ?? 1);
            if ($category_name === '') {
                throw new Exception('Category name is required.');
            }
            $sql = "INSERT INTO system_config_categories (category_name, description, display_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?)";
            $stmt = $connect->prepare($sql);
            $stmt->bind_param('ssiss', $category_name, $description, $display_order, $now, $now);
            break;
        default:
            throw new Exception('Invalid category table.');
    }
    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }
    $stmt->close();
    $response['success'] = true;
    $response['message'] = 'Category added successfully.';
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

$connect->close();
header('Content-Type: application/json');
echo json_encode($response);