<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'core.php';
require_once 'db_connect.php';

// Set proper header for JSON response
header('Content-Type: application/json');

$output = array('data' => array());

try {
    // Fetch from categories
    $sql = "SELECT category_id as id, name, type, description, status, created_at, 'categories' as table_name FROM categories WHERE deleted = 0";
    $result = $connect->query($sql);
    while ($row = $result->fetch_assoc()) {
        $statusLabel = isset($row['status']) ? ($row['status'] == 1 || strtolower($row['status']) == 'active' ? '<span class="label label-success">Active</span>' : '<span class="label label-danger">Inactive</span>') : '<span class="label label-success">Active</span>';
        $output['data'][] = array(
            $row['name'],
            $row['type'],
            $row['description'],
            $statusLabel,
            $row['created_at'],
            $row['table_name'],
            '<div class="btn-group">'
                .'<button class="btn btn-warning btn-sm" onclick="editCategory('.$row['id'].',\''.$row['table_name'].'\')"><i class="fa fa-edit"></i></button> '
                .'<button class="btn btn-danger btn-sm" onclick="deleteCategory('.$row['id'].',\''.$row['table_name'].'\')"><i class="fa fa-trash"></i></button>'
            .'</div>'
        );
    }
    // digital_categories
    $sql = "SELECT category_id as id, category_name as name, '' as type, description, NULL as status, created_at, 'digital_categories' as table_name FROM digital_categories";
    $result = $connect->query($sql);
    while ($row = $result->fetch_assoc()) {
        $statusLabel = '<span class="label label-success">Active</span>';
        $output['data'][] = array(
            $row['name'],
            $row['type'],
            $row['description'],
            $statusLabel,
            $row['created_at'],
            $row['table_name'],
            '<div class="btn-group">'
                .'<button class="btn btn-warning btn-sm" onclick="editCategory('.$row['id'].',\''.$row['table_name'].'\')"><i class="fa fa-edit"></i></button> '
                .'<button class="btn btn-danger btn-sm" onclick="deleteCategory('.$row['id'].',\''.$row['table_name'].'\')"><i class="fa fa-trash"></i></button>'
            .'</div>'
        );
    }
    // payment_categories
    $sql = "SELECT id, name, type, description, status, created_at, 'payment_categories' as table_name FROM payment_categories";
    $result = $connect->query($sql);
    while ($row = $result->fetch_assoc()) {
        $statusLabel = isset($row['status']) ? ($row['status'] == 1 || strtolower($row['status']) == 'active' ? '<span class="label label-success">Active</span>' : '<span class="label label-danger">Inactive</span>') : '<span class="label label-success">Active</span>';
        $output['data'][] = array(
            $row['name'],
            $row['type'],
            $row['description'],
            $statusLabel,
            $row['created_at'],
            $row['table_name'],
            '<div class="btn-group">'
                .'<button class="btn btn-warning btn-sm" onclick="editCategory('.$row['id'].',\''.$row['table_name'].'\')"><i class="fa fa-edit"></i></button> '
                .'<button class="btn btn-danger btn-sm" onclick="deleteCategory('.$row['id'].',\''.$row['table_name'].'\')"><i class="fa fa-trash"></i></button>'
            .'</div>'
        );
    }
    // production_categories
    $sql = "SELECT id, name, '' as type, description, status, created_at, 'production_categories' as table_name FROM production_categories";
    $result = $connect->query($sql);
    while ($row = $result->fetch_assoc()) {
        $statusLabel = isset($row['status']) ? ($row['status'] == 1 || strtolower($row['status']) == 'active' ? '<span class="label label-success">Active</span>' : '<span class="label label-danger">Inactive</span>') : '<span class="label label-success">Active</span>';
        $output['data'][] = array(
            $row['name'],
            $row['type'],
            $row['description'],
            $statusLabel,
            $row['created_at'],
            $row['table_name'],
            '<div class="btn-group">'
                .'<button class="btn btn-warning btn-sm" onclick="editCategory('.$row['id'].',\''.$row['table_name'].'\')"><i class="fa fa-edit"></i></button> '
                .'<button class="btn btn-danger btn-sm" onclick="deleteCategory('.$row['id'].',\''.$row['table_name'].'\')"><i class="fa fa-trash"></i></button>'
            .'</div>'
        );
    }
    // raw_material_categories
    $sql = "SELECT id, name, '' as type, description, status, created_at, 'raw_material_categories' as table_name FROM raw_material_categories";
    $result = $connect->query($sql);
    while ($row = $result->fetch_assoc()) {
        $statusLabel = isset($row['status']) ? ($row['status'] == 1 || strtolower($row['status']) == 'active' ? '<span class=\'label label-success\'>Active</span>' : '<span class=\'label label-danger\'>Inactive</span>') : '<span class=\'label label-success\'>Active</span>';
        $output['data'][] = array(
            $row['name'],
            $row['type'],
            $row['description'],
            $statusLabel,
            $row['created_at'],
            $row['table_name'],
            '<div class="btn-group">'
                .'<button class="btn btn-warning btn-sm" onclick="editCategory('.$row['id'].',\''.$row['table_name'].'\')"><i class="fa fa-edit"></i></button> '
                .'<button class="btn btn-danger btn-sm" onclick="deleteCategory('.$row['id'].',\''.$row['table_name'].'\')"><i class="fa fa-trash"></i></button>'
            .'</div>'
        );
    }
    // system_config_categories
    $sql = "SELECT category_id as id, category_name as name, '' as type, description, NULL as status, created_at, 'system_config_categories' as table_name FROM system_config_categories";
    $result = $connect->query($sql);
    while ($row = $result->fetch_assoc()) {
        $statusLabel = '<span class="label label-success">Active</span>';
        $output['data'][] = array(
            $row['name'],
            $row['type'],
            $row['description'],
            $statusLabel,
            $row['created_at'],
            $row['table_name'],
            '<div class="btn-group">'
                .'<button class="btn btn-warning btn-sm" onclick="editCategory('.$row['id'].',\''.$row['table_name'].'\')"><i class="fa fa-edit"></i></button> '
                .'<button class="btn btn-danger btn-sm" onclick="deleteCategory('.$row['id'].',\''.$row['table_name'].'\')"><i class="fa fa-trash"></i></button>'
            .'</div>'
        );
    }
} catch (Exception $e) {
    error_log("Error in fetchCategories.php: " . $e->getMessage());
    $output['error'] = true;
    $output['message'] = "Error fetching categories: " . $e->getMessage();
}

if ($connect) {
    $connect->close();
}
if (ob_get_length()) ob_clean();
echo json_encode($output); 