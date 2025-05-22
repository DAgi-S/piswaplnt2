<?php
require_once 'core.php';

$response = array('success' => false, 'message' => '');

$table = $_POST['category_table'] ?? $_GET['category_table'] ?? 'categories';
$id = $_POST['id'] ?? $_GET['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        switch ($table) {
            case 'categories':
                if (!hasPermission('settings.categories.manage')) throw new Exception('No permission.');
                $name = trim($_POST['name'] ?? '');
                $type = trim($_POST['type'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $status = intval($_POST['status'] ?? 1);
                if ($name === '' || $type === '' || !$id) throw new Exception('Name, type, and id required.');
                $sql = "UPDATE categories SET name=?, type=?, description=?, status=?, updated_at=NOW() WHERE category_id=?";
                $stmt = $connect->prepare($sql);
                $stmt->bind_param('sssii', $name, $type, $description, $status, $id);
                break;
            case 'digital_categories':
                $category_name = trim($_POST['category_name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                if ($category_name === '' || !$id) throw new Exception('Category name and id required.');
                $sql = "UPDATE digital_categories SET category_name=?, description=?, updated_at=NOW() WHERE category_id=?";
                $stmt = $connect->prepare($sql);
                $stmt->bind_param('ssi', $category_name, $description, $id);
                break;
            case 'payment_categories':
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $typeVal = trim($_POST['type'] ?? '');
                $status = intval($_POST['status'] ?? 1);
                if ($name === '' || $typeVal === '' || !$id) throw new Exception('Name, type, and id required.');
                $sql = "UPDATE payment_categories SET name=?, description=?, type=?, status=?, updated_at=NOW() WHERE id=?";
                $stmt = $connect->prepare($sql);
                $stmt->bind_param('sssii', $name, $description, $typeVal, $status, $id);
                break;
            case 'production_categories':
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $status = intval($_POST['status'] ?? 1);
                if ($name === '' || !$id) throw new Exception('Name and id required.');
                $sql = "UPDATE production_categories SET name=?, description=?, status=?, updated_at=NOW() WHERE id=?";
                $stmt = $connect->prepare($sql);
                $stmt->bind_param('ssii', $name, $description, $status, $id);
                break;
            case 'raw_material_categories':
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $status = intval($_POST['status'] ?? 1);
                if ($name === '' || !$id) throw new Exception('Name and id required.');
                $sql = "UPDATE raw_material_categories SET name=?, description=?, status=?, updated_at=NOW() WHERE id=?";
                $stmt = $connect->prepare($sql);
                $stmt->bind_param('ssii', $name, $description, $status, $id);
                break;
            case 'system_config_categories':
                $category_name = trim($_POST['category_name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $display_order = intval($_POST['display_order'] ?? 1);
                if ($category_name === '' || !$id) throw new Exception('Category name and id required.');
                $sql = "UPDATE system_config_categories SET category_name=?, description=?, display_order=?, updated_at=NOW() WHERE category_id=?";
                $stmt = $connect->prepare($sql);
                $stmt->bind_param('ssii', $category_name, $description, $display_order, $id);
                break;
            default:
                throw new Exception('Invalid category table.');
        }
        if (!$stmt->execute()) throw new Exception($stmt->error);
        $stmt->close();
        $response['success'] = true;
        $response['message'] = 'Category updated successfully.';
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET' && $id) {
    try {
        switch ($table) {
            case 'categories':
                $sql = "SELECT category_id as id, name, type, description, status FROM categories WHERE category_id=? AND (deleted=0 OR deleted IS NULL)";
                $stmt = $connect->prepare($sql);
                $stmt->bind_param('i', $id);
                break;
            case 'digital_categories':
                $sql = "SELECT category_id as id, category_name, description FROM digital_categories WHERE category_id=?";
                $stmt = $connect->prepare($sql);
                $stmt->bind_param('i', $id);
                break;
            case 'payment_categories':
                $sql = "SELECT id, name, description, type, status FROM payment_categories WHERE id=?";
                $stmt = $connect->prepare($sql);
                $stmt->bind_param('i', $id);
                break;
            case 'production_categories':
                $sql = "SELECT id, name, description, status FROM production_categories WHERE id=?";
                $stmt = $connect->prepare($sql);
                $stmt->bind_param('i', $id);
                break;
            case 'raw_material_categories':
                $sql = "SELECT id, name, description, status FROM raw_material_categories WHERE id=?";
                $stmt = $connect->prepare($sql);
                $stmt->bind_param('i', $id);
                break;
            case 'system_config_categories':
                $sql = "SELECT category_id as id, category_name, description, display_order FROM system_config_categories WHERE category_id=?";
                $stmt = $connect->prepare($sql);
                $stmt->bind_param('i', $id);
                break;
            default:
                throw new Exception('Invalid category table.');
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $response['success'] = true;
            $response['data'] = $result->fetch_assoc();
        } else {
            $response['message'] = 'Category not found.';
        }
        $stmt->close();
    } catch (Exception $e) {
        $response['message'] = 'Error occurred while fetching category: ' . $e->getMessage();
    }
}
$connect->close();
header('Content-Type: application/json');
echo json_encode($response); 