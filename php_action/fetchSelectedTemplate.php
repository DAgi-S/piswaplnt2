<?php
require_once 'core.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if(isset($_POST['templateId'])) {
    $templateId = $_POST['templateId'];
    
    try {
        $sql = "SELECT * FROM letter_templates WHERE id = ?";
        $stmt = $connect->prepare($sql);
        
        if($stmt === false) {
            throw new Exception("Prepare failed: " . $connect->error);
        }
        
        $stmt->bind_param("i", $templateId);
        
        if(!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            echo json_encode($row);
        } else {
            echo json_encode(['error' => 'Template not found']);
        }
        
        $stmt->close();
    } catch(Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    
    $connect->close();
} else {
    echo json_encode(['error' => 'No template ID provided']);
} 