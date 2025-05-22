<?php 	

require_once 'core.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$valid['success'] = array('success' => false, 'messages' => array());

if(isset($_POST['brandId'])) { 
    $brandId = $_POST['brandId'];
    
    try {
        // Using prepared statement for security
        $sql = "UPDATE brands SET status = 2 WHERE brand_id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("i", $brandId);

        if($stmt->execute()) {
            $valid['success'] = true;
            $valid['messages'] = "Brand successfully removed";		
        } else {
            $valid['success'] = false;
            $valid['messages'] = "Error while removing the brand: " . $connect->error;
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
    $valid['messages'] = "Brand ID not provided";
    error_log("Brand ID not provided in POST data");
}

header('Content-Type: application/json');
echo json_encode($valid);