<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once 'core.php';
require_once 'db_connect.php';

// Clear any previous output
while (ob_get_level()) ob_end_clean();

header('Content-Type: application/json');

$response = array('success' => false, 'messages' => '');

if (isset($_POST['categoryId'])) {
    $categoryId = mysqli_real_escape_string($connect, $_POST['categoryId']);

    try {
        // Start transaction
        $connect->begin_transaction();

        // Check if category exists
        $checkSql = "SELECT id FROM raw_material_categories WHERE id = ?";
        $stmt = $connect->prepare($checkSql);
        $stmt->bind_param("i", $categoryId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows !== 1) {
            throw new Exception("Category not found");
        }

        // Check if category is being used in raw materials
        $checkUsageSql = "SELECT id FROM raw_materials WHERE category_id = ? LIMIT 1";
        $stmt = $connect->prepare($checkUsageSql);
        $stmt->bind_param("i", $categoryId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            throw new Exception("Cannot delete category as it is being used by raw materials");
        }

        // Delete category
        $sql = "DELETE FROM raw_material_categories WHERE id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("i", $categoryId);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $connect->commit();
                $response['success'] = true;
                $response['messages'] = 'Category deleted successfully';
            } else {
                throw new Exception("No category was deleted");
            }
        } else {
            throw new Exception("Error deleting category: " . $stmt->error);
        }

    } catch (Exception $e) {
        // Rollback transaction on error
        $connect->rollback();
        $response['success'] = false;
        $response['messages'] = $e->getMessage();
    }

} else {
    $response['success'] = false;
    $response['messages'] = 'Invalid request: Category ID is required';
}

echo json_encode($response); 