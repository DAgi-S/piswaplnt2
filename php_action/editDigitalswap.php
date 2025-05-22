<?php
require_once 'core.php';
require_once 'db_connect.php';
require_once 'middleware.php';

header('Content-Type: application/json');

if (!hasPermission('edit_digitalswap')) {
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

        // Validate required fields
        if(!isset($_POST['digitalswapId']) || empty($_POST['digitalswapId'])) {
            throw new Exception("Digital swap ID is required");
        }

        $digitalswapId = (int)$_POST['digitalswapId'];
        $accountId = (int)$_POST['accountId'];
        $type = mysqli_real_escape_string($connect, $_POST['type']);
        $name = mysqli_real_escape_string($connect, $_POST['name']);
        $platform = mysqli_real_escape_string($connect, $_POST['platform']);
        $amount = (float)$_POST['amount'];
        $comment = isset($_POST['comment']) ? mysqli_real_escape_string($connect, $_POST['comment']) : '';
        $status = isset($_POST['status']) ? mysqli_real_escape_string($connect, $_POST['status']) : 'active';

        // Get current account_id
        $stmt = $connect->prepare("SELECT account_id FROM digitalswap WHERE id = ?");
        $stmt->bind_param("i", $digitalswapId);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows === 0) {
            throw new Exception("Digital swap not found");
        }
        $currentAccountId = $result->fetch_assoc()['account_id'];

        // Handle image upload if present
        $imagePath = null;
        if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $uploadDir = '../assets/images/digitalswap/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileName = 'swap_' . time() . '_' . $_FILES['image']['name'];
            if(move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName)) {
                $imagePath = 'assets/images/digitalswap/' . $fileName;
            }
        }

        // Update digital swap record
        $sql = "UPDATE digitalswap SET 
                account_id = ?, 
                type = ?, 
                name = ?, 
                platform = ?, 
                amount = ?, 
                comment = ?, 
                status = ?";
        
        if($imagePath) {
            $sql .= ", image = ?";
        }
        
        $sql .= " WHERE id = ?";
        
        $stmt = $connect->prepare($sql);
        
        if($imagePath) {
            $stmt->bind_param("isssdsssi", $accountId, $type, $name, $platform, $amount, $comment, $status, $imagePath, $digitalswapId);
        } else {
            $stmt->bind_param("isssdsssi", $accountId, $type, $name, $platform, $amount, $comment, $status, $digitalswapId);
        }

        if($stmt->execute()) {
            // Update transaction counts if account changed
            if($currentAccountId != $accountId) {
                // Decrease count for old account
                if($currentAccountId) {
                    $stmt = $connect->prepare("UPDATE accounts SET number_of_transactions = number_of_transactions - 1 WHERE id = ?");
                    $stmt->bind_param("i", $currentAccountId);
                    $stmt->execute();
                }
                
                // Increase count for new account
                if($accountId) {
                    $stmt = $connect->prepare("UPDATE accounts SET number_of_transactions = number_of_transactions + 1 WHERE id = ?");
                    $stmt->bind_param("i", $accountId);
                    $stmt->execute();
                }
            }
            
            $connect->commit();
            $response['success'] = true;
            $response['messages'] = "Digital swap updated successfully";
        } else {
            throw new Exception("Error updating digital swap: " . $stmt->error);
        }

    } catch(Exception $e) {
        $connect->rollback();
        $response['success'] = false;
        $response['messages'] = $e->getMessage();
    }

    echo json_encode($response);
} 