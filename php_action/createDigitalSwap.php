<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Clear any previous output and start fresh
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

try {
    require_once 'core.php';
    require_once 'db_connect.php';
    require_once 'middleware.php';
    require_once 'telegram_notification.php';

    // Set header for JSON response
    header('Content-Type: application/json');

    $response = array(
        'success' => false,
        'messages' => '',
        'debug' => array()
    );

    // Log incoming request
    error_log('createDigitalSwap.php - POST data: ' . print_r($_POST, true));
    error_log('createDigitalSwap.php - FILES data: ' . print_r($_FILES, true));

    // Validate required fields
    $required = array('accountId', 'transaction_date', 'type', 'name', 'amount');
    foreach ($required as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            throw new Exception(ucfirst(str_replace('_', ' ', $field)) . " is required");
        }
    }

    // Start transaction
    $connect->begin_transaction();

    try {
        // Sanitize and validate inputs
        $account_id = filter_var($_POST['accountId'], FILTER_VALIDATE_INT);
        if ($account_id === false) {
            throw new Exception("Invalid account ID");
        }

        $transaction_date = date('Y-m-d', strtotime($_POST['transaction_date']));
        if ($transaction_date === false) {
            throw new Exception("Invalid transaction date");
        }

        $type = mysqli_real_escape_string($connect, trim($_POST['type']));
        $name = mysqli_real_escape_string($connect, trim($_POST['name']));
        $amount = filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT);
        if ($amount === false) {
            throw new Exception("Invalid amount");
        }
        $comment = isset($_POST['comment']) ? mysqli_real_escape_string($connect, trim($_POST['comment'])) : '';

        // Check for duplicate entry within the last minute
        $checkDuplicateSql = "SELECT id FROM digitalswap 
            WHERE account_id = ? 
            AND transaction_date = ? 
            AND type = ? 
            AND name = ? 
            AND amount = ? 
            AND comment = ?
            AND created_at >= NOW() - INTERVAL 1 MINUTE";
        
        $stmt = $connect->prepare($checkDuplicateSql);
        $stmt->bind_param("isssds", 
            $account_id,
            $transaction_date,
            $type,
            $name,
            $amount,
            $comment
        );
        $stmt->execute();
        $duplicateResult = $stmt->get_result();

        if ($duplicateResult->num_rows > 0) {
            throw new Exception("Duplicate entry detected. Please wait a moment before trying again.");
        }
        
        // Get account details including platform
        $stmt = $connect->prepare("SELECT id, account_platform FROM accounts WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Failed to prepare account query: " . $connect->error);
        }
        $stmt->bind_param("i", $account_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute account query: " . $stmt->error);
        }
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

        $status = 1;
        $image = '';

        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $uploadDir = '../assets/images/digitalswap/';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    throw new Exception("Failed to create upload directory");
                }
            }

            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($_FILES['image']['type'], $allowedTypes)) {
                throw new Exception("Invalid file type. Only JPG, PNG and GIF are allowed.");
            }

            // Generate safe filename
            $fileName = 'swap_' . time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", $_FILES['image']['name']);
            $targetPath = $uploadDir . $fileName;

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                throw new Exception("Failed to upload image");
            }
            $image = 'assets/images/digitalswap/' . $fileName;
        }

        // Insert digital swap with type_id and platform_id
        $sql = "INSERT INTO digitalswap (
            account_id, transaction_date, type, type_id, name, platform, platform_id,
            amount, image, comment, status, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

        $stmt = $connect->prepare($sql);
        if (!$stmt) {
            throw new Exception("Failed to prepare insert query: " . $connect->error);
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
            $status
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to save digital swap: " . $stmt->error);
        }

        $insert_id = $stmt->insert_id;

        // Update account transaction count
        $updateSql = "UPDATE accounts SET 
            number_of_transactions = number_of_transactions + 1,
            updated_at = NOW() 
            WHERE id = ?";
            
        $stmt = $connect->prepare($updateSql);
        if (!$stmt) {
            throw new Exception("Failed to prepare update query: " . $connect->error);
        }
        
        $stmt->bind_param("i", $account_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update account: " . $stmt->error);
        }

        // If everything is successful, commit the transaction
        $connect->commit();
        
        // Create a more detailed success message
        $formattedAmount = number_format($amount, 2);
        $formattedDate = date('d M Y', strtotime($transaction_date));
        $successMessage = sprintf(
            "Successfully created new %s transaction for %s (%s) with amount %s on %s",
            ucfirst($type),
            $name,
            $platform,
            $formattedAmount,
            $formattedDate
        );
        
        // Send Telegram notification
        $telegramMessage = "🔄 <b>New Digital Swap</b>\n\n".
            "Type: " . ucfirst($type) . "\n".
            "Name: " . $name . "\n".
            "Platform: " . $platform . "\n".
            "Amount: " . $formattedAmount . "\n".
            "Date: " . $formattedDate;
        
        if (!empty($comment)) {
            $telegramMessage .= "\nComment: " . $comment;
        }
        
        sendTelegramNotification($telegramMessage);
        
        $response['success'] = true;
        $response['messages'] = $successMessage;
        $response['id'] = $insert_id;

    } catch (Exception $e) {
        // Rollback on error
        $connect->rollback();
        throw $e;
    }

    // Before sending response, make sure there's no other output
    $output = ob_get_clean();
    if (!empty($output)) {
        error_log("Unexpected output before JSON response: " . $output);
    }

} catch (Exception $e) {
    error_log("Digital Swap Creation Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    $response['success'] = false;
    $response['messages'] = $e->getMessage();
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        $response['debug'] = [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ];
    }
}

// Clean any remaining output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit();