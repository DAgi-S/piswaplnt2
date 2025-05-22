<?php
require_once 'core.php';

header('Content-Type: application/json');

$response = array('success' => false);

if($_POST) {
    if(!isset($_POST['clientId'])) {
        $response['messages'] = "Client ID is required";
        echo json_encode($response);
        exit();
    }

    try {
        $clientId = intval($_POST['clientId']);

        // Check if client exists
        $checkSql = "SELECT id FROM clients WHERE id = ?";
        $checkStmt = $connect->prepare($checkSql);
        $checkStmt->bind_param("i", $clientId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if($result->num_rows === 0) {
            throw new Exception("Client not found");
        }

        // Soft delete by setting status to 0
        $sql = "UPDATE clients SET status = 0, updated_at = NOW() WHERE id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("i", $clientId);
        
        if($stmt->execute()) {
            $response['success'] = true;
            $response['messages'] = "Client successfully removed";
        } else {
            throw new Exception($stmt->error);
        }
        
        $stmt->close();
        $checkStmt->close();
        
    } catch(Exception $e) {
        $response['messages'] = "Error removing client: " . $e->getMessage();
    }
} else {
    $response['messages'] = "Invalid request";
}

$connect->close();
echo json_encode($response); 