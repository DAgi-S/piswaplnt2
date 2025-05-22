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
    $categoryName = mysqli_real_escape_string($connect, $_POST['categoryName']);
    $description = mysqli_real_escape_string($connect, $_POST['description']);
    $status = mysqli_real_escape_string($connect, $_POST['status']);

    try {
        // Check if category name already exists
        $checkSql = "SELECT id FROM raw_material_categories WHERE name = ?";
        $stmt = $connect->prepare($checkSql);
        $stmt->bind_param("s", $categoryName);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            throw new Exception("Category name already exists");
        }

        // Insert new category
        $sql = "INSERT INTO raw_material_categories (name, description, status, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("sss", $categoryName, $description, $status);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['messages'] = 'Category created successfully';
        } else {
            throw new Exception("Error creating category: " . $stmt->error);
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