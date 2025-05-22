<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once 'core.php';
require_once 'db_connect.php';

// Clear any previous output
while (ob_get_level()) ob_end_clean();

header('Content-Type: application/json');

$response = array('success' => false, 'messages' => '');

if ($_POST) {
    $categoryId = mysqli_real_escape_string($connect, $_POST['categoryId']);
    $categoryName = mysqli_real_escape_string($connect, $_POST['editCategoryName']);
    $description = mysqli_real_escape_string($connect, $_POST['editDescription']);
    $status = mysqli_real_escape_string($connect, $_POST['editStatus']);

    try {
        // Check if category exists
        $checkSql = "SELECT id FROM raw_material_categories WHERE id = ?";
        $stmt = $connect->prepare($checkSql);
        $stmt->bind_param("i", $categoryId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows !== 1) {
            throw new Exception("Category not found");
        }

        // Check if name already exists for other categories
        $checkNameSql = "SELECT id FROM raw_material_categories WHERE name = ? AND id != ?";
        $stmt = $connect->prepare($checkNameSql);
        $stmt->bind_param("si", $categoryName, $categoryId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            throw new Exception("Category name already exists");
        }

        // Update category
        $sql = "UPDATE raw_material_categories SET name = ?, description = ?, status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("sssi", $categoryName, $description, $status, $categoryId);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['messages'] = 'Category updated successfully';
        } else {
            throw new Exception("Error updating category: " . $stmt->error);
        }

    } catch (Exception $e) {
        $response['success'] = false;
        $response['messages'] = $e->getMessage();
    }

} else {
    $response['success'] = false;
    $response['messages'] = 'Invalid request';
}

echo json_encode($response); 