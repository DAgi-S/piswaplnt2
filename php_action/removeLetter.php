<?php
require_once 'core.php';

if($_POST) {
    $valid['success'] = array('success' => false, 'messages' => array());
    $letterId = $_POST['letterId'];
    
    // First delete vehicle records
    $vehicleSql = "DELETE FROM letter_vehicles WHERE letter_id = ?";
    $vehicleStmt = $connect->prepare($vehicleSql);
    $vehicleStmt->bind_param("i", $letterId);
    $vehicleStmt->execute();
    $vehicleStmt->close();
    
    // Then delete letter record
    $letterSql = "DELETE FROM generated_letters WHERE id = ?";
    $letterStmt = $connect->prepare($letterSql);
    $letterStmt->bind_param("i", $letterId);
    
    if($letterStmt->execute()) {
        $valid['success'] = true;
        $valid['messages'] = "Letter removed successfully";
    } else {
        $valid['success'] = false;
        $valid['messages'] = "Error removing letter";
    }
    
    $letterStmt->close();
    $connect->close();
    
    echo json_encode($valid);
} 