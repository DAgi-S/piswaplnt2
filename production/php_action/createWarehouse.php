<?php
require_once 'core.php';

header('Content-Type: application/json');

$response = array(
    'success' => false,
    'messages' => ''
);

// Validate input
if(empty($_POST['code']) || empty($_POST['name']) || empty($_POST['type'])) {
    $response['messages'] = "Please fill in all required fields";
    echo json_encode($response);
    exit();
}

try {
    // Start transaction
    $connect->begin_transaction();
    
    // Check if warehouse code already exists
    $sql = "SELECT id FROM warehouses WHERE code = ? AND status != 'deleted'";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("s", $_POST['code']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $response['messages'] = "Warehouse code already exists";
        echo json_encode($response);
        $stmt->close();
        exit();
    }
    $stmt->close();
    
    // Insert new warehouse
    $sql = "INSERT INTO warehouses (
                code,
                name,
                type,
                location,
                description,
                status,
                created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?)";
            
    $stmt = $connect->prepare($sql);
    $stmt->bind_param(
        "ssssssi", 
        $_POST['code'],
        $_POST['name'],
        $_POST['type'],
        $_POST['location'],
        $_POST['description'],
        $_POST['status'],
        $_SESSION['userId']
    );
    
    if($stmt->execute()) {
        $response['success'] = true;
        $response['messages'] = "Warehouse created successfully";
        
        // Commit transaction
        $connect->commit();
    } else {
        throw new Exception("Error creating warehouse");
    }
    
    $stmt->close();
    
} catch(Exception $e) {
    // Rollback transaction on error
    $connect->rollback();
    
    $response['messages'] = $e->getMessage();
}

$connect->close();
echo json_encode($response); 