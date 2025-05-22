<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Clear any previous output
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

try {
    require_once 'core.php';
    require_once 'db_connect.php';
    require_once 'middleware.php';

    header('Content-Type: application/json');

    $response = array(
        'success' => false,
        'messages' => '',
        'debug' => array()
    );

    // Validate required fields
    $required = array('id', 'accountId', 'transaction_date', 'type', 'name', 'amount');
    foreach ($required as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            throw new Exception(ucfirst(str_replace('_', ' ', $field)) . " is required");
        }
    }

    // Start transaction
    $connect->begin_transaction();

    try {
        $id = (int)$_POST['id'];
        $account_id = (int)$_POST['accountId'];
        $transaction_date = date('Y-m-d', strtotime($_POST['transaction_date']));
        $type = mysqli_real_escape_string($connect, $_POST['type']);
        $name = mysqli_real_escape_string($connect, $_POST['name']);
        $amount = (float)$_POST['amount'];
        $comment = isset($_POST['comment']) ? mysqli_real_escape_string($connect, $_POST['comment']) : '';

        // Get old record for comparison
        $stmt = $connect->prepare("SELECT account_id, image FROM digitalswap WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $old_result = $stmt->get_result();
        
        if ($old_result->num_rows === 0) {
            throw new Exception("Digital swap not found");
        }
        
        $old_data = $old_result->fetch_object();
        $old_account_id = $old_data->account_id;
        $old_image = $old_data->image;

        // Get account details including platform
        $stmt = $connect->prepare("SELECT id, account_platform FROM accounts WHERE id = ?");
        $stmt->bind_param("i", $account_id);
        $stmt->execute();
        $account_result = $stmt->get_result();
        
        if ($account_result->num_rows === 0) {
            throw new Exception("Invalid account selected");
        }
        
        $account_data = $account_result->fetch_object();
        $platform = $account_data->account_platform;

        // Get or create platform_id
        $stmt = $connect->prepare("SELECT id FROM platforms WHERE name = ?");
        $stmt->bind_param("s", $platform);
        $stmt->execute();
        $platform_result = $stmt->get_result();
        
        if ($platform_result->num_rows === 0) {
            $stmt = $connect->prepare("INSERT INTO platforms (name, status) VALUES (?, 1)");
            $stmt->bind_param("s", $platform);
            $stmt->execute();
            $platform_id = $connect->insert_id;
        } else {
            $platform_data = $platform_result->fetch_object();
            $platform_id = $platform_data->id;
        }

        // Get type_id from transaction_types
        $stmt = $connect->prepare("SELECT id FROM transaction_types WHERE name = ?");
        $stmt->bind_param("s", $type);
        $stmt->execute();
        $type_result = $stmt->get_result();
        
        if ($type_result->num_rows === 0) {
            $stmt = $connect->prepare("INSERT INTO transaction_types (name, status) VALUES (?, 1)");
            $stmt->bind_param("s", $type);
            $stmt->execute();
            $type_id = $connect->insert_id;
        } else {
            $type_data = $type_result->fetch_object();
            $type_id = $type_data->id;
        }

        $image = $old_image; // Keep old image by default

        // Handle image upload or removal
        if (isset($_POST['removeImage']) && $_POST['removeImage'] == 'on') {
            if ($old_image && file_exists('../' . $old_image)) {
                unlink('../' . $old_image);
            }
            $image = '';
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $uploadDir = '../assets/images/digitalswap/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = 'swap_' . time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", $_FILES['image']['name']);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName)) {
                // Remove old image if exists
                if ($old_image && file_exists('../' . $old_image)) {
                    unlink('../' . $old_image);
                }
                $image = 'assets/images/digitalswap/' . $fileName;
            }
        }

        // Update digital swap
        $sql = "UPDATE digitalswap SET 
            account_id = ?, 
            transaction_date = ?, 
            type = ?, 
            type_id = ?, 
            name = ?, 
            platform = ?, 
            platform_id = ?,
            amount = ?, 
            image = ?, 
            comment = ?, 
            updated_at = NOW()
            WHERE id = ?";

        $stmt = $connect->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $connect->error);
        }

        $stmt->bind_param("ississidssi", 
            $account_id,
            $transaction_date,
            $type,
            $type_id,
            $name,
            $platform,
            $platform_id,
            $amount,
            $image,
            $comment,
            $id
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to update digital swap: " . $stmt->error);
        }

        // Update transaction counts if account changed
        if ($old_account_id != $account_id) {
            // Decrease count for old account
            $stmt = $connect->prepare("UPDATE accounts SET 
                number_of_transactions = number_of_transactions - 1,
                updated_at = NOW() 
                WHERE id = ?");
            $stmt->bind_param("i", $old_account_id);
            $stmt->execute();

            // Increase count for new account
            $stmt = $connect->prepare("UPDATE accounts SET 
                number_of_transactions = number_of_transactions + 1,
                updated_at = NOW() 
                WHERE id = ?");
            $stmt->bind_param("i", $account_id);
            $stmt->execute();
        }

        // If everything is successful, commit the transaction
        $connect->commit();
        
        // Create a more detailed success message
        $formattedAmount = number_format($amount, 2);
        $formattedDate = date('d M Y', strtotime($transaction_date));
        $response['success'] = true;
        $response['messages'] = sprintf(
            "Successfully updated %s transaction for %s (%s) with amount %s on %s",
            ucfirst($type),
            $name,
            $platform,
            $formattedAmount,
            $formattedDate
        );

    } catch (Exception $e) {
        // Rollback on error
        $connect->rollback();
        throw $e;
    }

    // Clear any buffered output
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'messages' => $e->getMessage(),
        'debug' => [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
exit(); 