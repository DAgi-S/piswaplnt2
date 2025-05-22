<?php 	

require_once 'core.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$valid['success'] = array('success' => false, 'messages' => array());

if(isset($_POST['categoriesId'])) { 
    $categoryId = $_POST['categoriesId'];
    
    try {
        // Using prepared statement for security
        $sql = "UPDATE categories SET status = 2 WHERE category_id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("i", $categoryId);

        if($stmt->execute()) {
            $valid['success'] = true;
            $valid['messages'] = "Category successfully removed";		
        } else {
            $valid['success'] = false;
            $valid['messages'] = "Error while removing the category: " . $connect->error;
            error_log("MySQL Error: " . $connect->error);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        $valid['success'] = false;
        $valid['messages'] = "Exception: " . $e->getMessage();
        error_log("Exception: " . $e->getMessage());
    }
    
    $connect->close();
} else {
    $valid['success'] = false;
    $valid['messages'] = "Category ID not provided";
    error_log("Category ID not provided in POST data");
}

header('Content-Type: application/json');
echo json_encode($valid); 