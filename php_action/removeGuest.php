<?php
require_once 'core.php';

// Check if user has permission
if (!hasPermission('manage_guests')) {
    echo json_encode(array('success' => false, 'messages' => 'Access Denied'));
    exit();
}

$valid['success'] = array('success' => false, 'messages' => array());

if(isset($_POST['id'])) {
    $guestId = $_POST['id'];
    
    // First check if the guest exists
    $sql = "SELECT id FROM guests WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('i', $guestId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        // Delete the guest
        $sql = "DELETE FROM guests WHERE id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param('i', $guestId);
        
        if($stmt->execute()) {
            $valid['success'] = true;
            $valid['messages'] = "Guest user successfully deleted";
        } else {
            $valid['success'] = false;
            $valid['messages'] = "Error while deleting guest user";
        }
    } else {
        $valid['success'] = false;
        $valid['messages'] = "Guest user not found";
    }
    
    $stmt->close();
    
} else {
    $valid['success'] = false;
    $valid['messages'] = "Invalid request";
}

echo json_encode($valid); 