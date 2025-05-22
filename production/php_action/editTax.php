<?php
require_once 'core.php';

// Check if user has permission to manage tax settings
if (!hasPermission('settings.tax.manage')) {
    $response['success'] = false;
    $response['messages'] = 'Access denied. Permission to manage tax settings required.';
    echo json_encode($response);
    exit();
}

// Initialize response array
$response = array();
$response['success'] = false;
$response['messages'] = '';

// Handle GET request to fetch tax data
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id']) && is_numeric($_GET['id']) && $_GET['id'] > 0) {
        $taxId = $_GET['id'];
        
        $sql = "SELECT id, name, rate, type, status FROM tax_rates WHERE id = ? AND deleted = 0";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("i", $taxId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $response['success'] = true;
            $response['data'] = $result->fetch_assoc();
        } else {
            $response['messages'] = 'Tax rate not found';
        }
        
        $stmt->close();
    } else {
        $response['messages'] = 'Invalid tax rate ID';
    }
}

// Handle POST request to update tax
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id']) && is_numeric($_POST['id']) && $_POST['id'] > 0) {
        $taxId = $_POST['id'];
        $name = trim($_POST['name']);
        $rate = floatval($_POST['rate']);
        $type = trim($_POST['type']);
        $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;
        
        if (empty($name)) {
            $response['messages'] = 'Tax name is required';
        } elseif ($rate < 0 || $rate > 100) {
            $response['messages'] = 'Tax rate must be between 0 and 100';
        } elseif (!in_array($type, ['fixed', 'percentage'])) {
            $response['messages'] = 'Invalid tax type';
        } else {
            $sql = "UPDATE tax_rates SET name = ?, rate = ?, type = ?, status = ? WHERE id = ? AND deleted = 0";
            $stmt = $connect->prepare($sql);
            $stmt->bind_param("sdsii", $name, $rate, $type, $status, $taxId);
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['messages'] = 'Tax rate updated successfully';
            } else {
                $response['messages'] = 'Error updating tax rate: ' . $connect->error;
            }
            
            $stmt->close();
        }
    } else {
        $response['messages'] = 'Invalid tax rate ID';
    }
}

// Close database connection
$connect->close();

// Set content type header and output response
header('Content-Type: application/json');
echo json_encode($response); 