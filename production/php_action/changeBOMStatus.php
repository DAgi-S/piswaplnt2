<?php
require_once 'core.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('success' => false, 'messages' => 'Invalid request method'));
    exit();
}

// Check if id and status are provided
if (!isset($_POST['id']) || !isset($_POST['status'])) {
    echo json_encode(array('success' => false, 'messages' => 'Missing required parameters'));
    exit();
}

$id = $_POST['id'];
$status = $_POST['status'];

// Validate status value
if (!in_array($status, ['active', 'inactive'])) {
    echo json_encode(array('success' => false, 'messages' => 'Invalid status value'));
    exit();
}

try {
    // Start transaction
    $connect->begin_transaction();

    // Update BOM status
    $sql = "UPDATE product_bom SET status = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('si', $status, $id);
    
    if ($stmt->execute()) {
        $connect->commit();
        echo json_encode(array(
            'success' => true,
            'messages' => 'BOM status updated successfully'
        ));
    } else {
        throw new Exception("Error updating BOM status");
    }

} catch (Exception $e) {
    $connect->rollback();
    echo json_encode(array(
        'success' => false,
        'messages' => $e->getMessage()
    ));
}

// Close connection
$connect->close(); 