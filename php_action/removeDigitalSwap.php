<?php
require_once 'core.php';
require_once 'db_connect.php';
require_once 'middleware.php';

// Clear any previous output
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json');

if (!hasPermission('delete_digitalswap')) {
    echo json_encode([
        'success' => false,
        'messages' => 'Access denied'
    ]);
    exit();
}

if($_POST) {
    $response = array();
    
    try {
        $connect->begin_transaction();

        // Get the ID from POST data
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        if (!$id) {
            throw new Exception("Invalid digital swap ID");
        }

        // Get current account_id and image before deletion
        $stmt = $connect->prepare("SELECT account_id, image FROM digitalswap WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows === 0) {
            throw new Exception("Digital swap not found");
        }
        
        $row = $result->fetch_assoc();
        $accountId = $row['account_id'];
        $imagePath = $row['image'];

        // Delete digital swap record
        $stmt = $connect->prepare("DELETE FROM digitalswap WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if($stmt->execute()) {
            // Update account transaction count
            if($accountId) {
                $stmt = $connect->prepare("UPDATE accounts SET number_of_transactions = number_of_transactions - 1 WHERE id = ?");
                $stmt->bind_param("i", $accountId);
                $stmt->execute();
            }

            // Delete image file if exists
            if($imagePath && file_exists('../' . $imagePath)) {
                unlink('../' . $imagePath);
            }
            
            $connect->commit();
            $response['success'] = true;
            $response['messages'] = "Digital swap deleted successfully";
        } else {
            throw new Exception("Error deleting digital swap: " . $stmt->error);
        }

    } catch(Exception $e) {
        $connect->rollback();
        $response['success'] = false;
        $response['messages'] = $e->getMessage();
        $response['debug'] = [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ];
    }

    echo json_encode($response);
    exit();
} else {
    echo json_encode([
        'success' => false,
        'messages' => 'Invalid request method'
    ]);
    exit();
}