<?php
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

try {
    // Get category ID
    $id = isset($_POST['id']) ? $_POST['id'] : null;
    
    if(empty($id)) {
        throw new Exception("Category ID is required");
    }
    
    // Fetch category details
    $query = "SELECT id, name, description, status 
              FROM raw_material_categories 
              WHERE id = ?";
    
    $stmt = $connect->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows === 0) {
        throw new Exception("Category not found");
    }
    
    $data = $result->fetch_assoc();
    
    $response = array(
        'success' => true,
        'data' => array(
            'id' => $data['id'],
            'name' => $data['name'],
            'description' => $data['description'],
            'status' => $data['status']
        )
    );
    
} catch(Exception $e) {
    $response = array(
        'success' => false,
        'messages' => $e->getMessage()
    );
}

// Close the database connection
$connect->close();

// Return the JSON response
echo json_encode($response); 