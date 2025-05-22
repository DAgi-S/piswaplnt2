<?php
require_once 'core.php';

// Check if user has admin role
if(!isset($_SESSION['roleId']) || $_SESSION['roleId'] !== 1) {
    $response = array('success' => false, 'message' => 'Access denied. Admin privileges required.');
    echo json_encode($response);
    exit();
}

$response = array('success' => false, 'message' => '');

if($_POST) {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $table = isset($_POST['category_table']) ? $_POST['category_table'] : 'categories';

    if($id <= 0) {
        $response['message'] = 'Invalid category ID.';
        echo json_encode($response);
        exit();
    }

    try {
        switch ($table) {
            case 'categories':
                // Soft delete
                $sql = "UPDATE categories SET deleted = 1 WHERE category_id = ?";
                $stmt = $connect->prepare($sql);
                $stmt->bind_param('i', $id);
                break;
            case 'digital_categories':
                $sql = "DELETE FROM digital_categories WHERE category_id = ?";
                $stmt = $connect->prepare($sql);
                $stmt->bind_param('i', $id);
                break;
            case 'payment_categories':
                $sql = "DELETE FROM payment_categories WHERE id = ?";
                $stmt = $connect->prepare($sql);
                $stmt->bind_param('i', $id);
                break;
            case 'production_categories':
                $sql = "DELETE FROM production_categories WHERE id = ?";
                $stmt = $connect->prepare($sql);
                $stmt->bind_param('i', $id);
                break;
            case 'raw_material_categories':
                $sql = "DELETE FROM raw_material_categories WHERE id = ?";
                $stmt = $connect->prepare($sql);
                $stmt->bind_param('i', $id);
                break;
            case 'system_config_categories':
                $sql = "DELETE FROM system_config_categories WHERE category_id = ?";
                $stmt = $connect->prepare($sql);
                $stmt->bind_param('i', $id);
                break;
            default:
                throw new Exception('Invalid category table.');
        }
        if($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Category deleted successfully.';
        } else {
            throw new Exception($connect->error);
        }
        $stmt->close();
    } catch (Exception $e) {
        $response['message'] = 'Error occurred while deleting category: ' . $e->getMessage();
    }
}

// Close database connection
$connect->close();

header('Content-Type: application/json');
echo json_encode($response); 