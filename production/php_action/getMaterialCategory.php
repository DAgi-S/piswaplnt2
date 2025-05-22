<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once 'core.php';
require_once 'db_connect.php';

// Clear any previous output
while (ob_get_level()) ob_end_clean();

header('Content-Type: application/json');

$response = array('success' => false, 'messages' => '', 'data' => null);

if ($_POST) {
    $categoryId = mysqli_real_escape_string($connect, $_POST['categoryId']);

    try {
        // Get category details
        $sql = "SELECT id, name, description, status FROM raw_material_categories WHERE id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("i", $categoryId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $response['success'] = true;
            $response['data'] = $result->fetch_assoc();
        } else {
            throw new Exception("Category not found");
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