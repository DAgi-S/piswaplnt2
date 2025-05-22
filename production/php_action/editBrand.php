<?php
require_once 'core.php';

$response = array('success' => false, 'messages' => '');

// Permission check
if (!hasPermission('settings.brands.manage')) {
    $response['success'] = false;
    $response['messages'] = 'Access denied. Permission to manage brands required.';
    echo json_encode($response);
    exit();
}

$table = $_POST['table_name'] ?? $_GET['table_name'] ?? 'brands';
$id = $_POST['id'] ?? $_GET['id'] ?? null;

// Handle GET request to fetch brand data
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $id) {
    if ($table === 'brands') {
        $sql = "SELECT brand_id as id, name, description, status, created_at, 'brands' as table_name FROM brands WHERE brand_id = ? AND deleted = 0";
    } else if ($table === 'production_brands') {
        $sql = "SELECT id, name, description, status, created_at, 'production_brands' as table_name FROM production_brands WHERE id = ?";
    } else {
        $response['messages'] = 'Invalid table name';
        echo json_encode($response);
        exit();
    }
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $response['success'] = true;
        $response['data'] = $result->fetch_assoc();
    } else {
        $response['messages'] = 'Brand not found';
    }
    $stmt->close();
}

// Handle POST request to update brand
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
    $name = trim($_POST['name']);
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;
    
    if (empty($name)) {
        $response['messages'] = 'Brand name is required';
    } else {
        if ($table === 'brands') {
            $sql = "UPDATE brands SET name = ?, description = ?, status = ?, updated_at = NOW() WHERE brand_id = ? AND deleted = 0";
        } else if ($table === 'production_brands') {
            $sql = "UPDATE production_brands SET name = ?, description = ?, status = ?, updated_at = NOW() WHERE id = ?";
        } else {
            $response['messages'] = 'Invalid table name';
            echo json_encode($response);
            exit();
        }
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("ssii", $name, $description, $status, $id);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['messages'] = 'Brand updated successfully';
        } else {
            $response['messages'] = 'Error updating brand: ' . $connect->error;
        }
        
        $stmt->close();
    }
}

// Close database connection
$connect->close();

// Set content type header and output response
header('Content-Type: application/json');
echo json_encode($response); 