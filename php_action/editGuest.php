<?php
require_once 'core.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user has permission to edit guests
if (!hasPermission('guest.edit') && !hasPermission('guest_edit') && 
    !hasPermission('guest.manage') && !hasPermission('manage_guests')) {
    echo json_encode([
        'success' => false,
        'messages' => 'Access denied: Insufficient permissions'
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
        $guestId = $_POST['guestId'];
        $fullName = $_POST['editFullName'];
        $linkedAccounts = isset($_POST['editLinkedAccount']) ? $_POST['editLinkedAccount'] : array();
        // Store access levels as JSON array
        $accessLevel = isset($_POST['editAccess']) ? json_encode($_POST['editAccess']) : json_encode([]);
        $expiryDate = !empty($_POST['editExpiryDate']) ? $_POST['editExpiryDate'] : NULL;
        $status = $_POST['editStatus'];
        $password = !empty($_POST['editPassword']) ? password_hash($_POST['editPassword'], PASSWORD_DEFAULT) : '';

        // Get account details and names for all selected accounts
        $accountNames = array();
        $currencies = array();
        
        if (!empty($linkedAccounts)) {
            $accountIds = implode(',', array_fill(0, count($linkedAccounts), '?'));
            $accountSql = "SELECT account_owner, account_platform, Currency FROM accounts WHERE id IN ($accountIds)";
            $accountStmt = $connect->prepare($accountSql);
            
            // Create the type string for bind_param
            $types = str_repeat('i', count($linkedAccounts));
            $bindParams = array($types);
            foreach ($linkedAccounts as $key => $value) {
                $bindParams[] = &$linkedAccounts[$key];
            }
            call_user_func_array(array($accountStmt, 'bind_param'), $bindParams);
            
            $accountStmt->execute();
            $accountResult = $accountStmt->get_result();
            
            while ($accountData = $accountResult->fetch_assoc()) {
                $accountNames[] = $accountData['account_owner'] . ' - ' . $accountData['account_platform'];
                if (!in_array($accountData['Currency'], $currencies)) {
                    $currencies[] = $accountData['Currency'];
                }
            }
        }

        // Set default values if no accounts selected
        $accountName = !empty($accountNames) ? implode(', ', $accountNames) : 'No account assigned';
        $currency = !empty($currencies) ? implode(', ', $currencies) : 'USD';
        $linkedAccountsStr = !empty($linkedAccounts) ? implode(',', $linkedAccounts) : '';

        // Start transaction
        $connect->begin_transaction();

        try {
            // Update guest user
            if (!empty($password)) {
                $sql = "UPDATE guest_users SET 
                    full_name = ?,
                    password = ?,
                    account_name = ?,
                    currency = ?,
                    linked_account_id = ?,
                    access_level = ?,
                    status = ?,
                    expiry_date = ?
                    WHERE guest_id = ?";
                
                $stmt = $connect->prepare($sql);
                $stmt->bind_param("sssssssss", 
                    $fullName,
                    $password,
                    $accountName,
                    $currency,
                    $linkedAccountsStr,
                    $accessLevel,
                    $status,
                    $expiryDate,
                    $guestId
                );
            } else {
                $sql = "UPDATE guest_users SET 
                    full_name = ?,
                    account_name = ?,
                    currency = ?,
                    linked_account_id = ?,
                    access_level = ?,
                    status = ?,
                    expiry_date = ?
                    WHERE guest_id = ?";
                
                $stmt = $connect->prepare($sql);
                $stmt->bind_param("ssssssss", 
                    $fullName,
                    $accountName,
                    $currency,
                    $linkedAccountsStr,
                    $accessLevel,
                    $status,
                    $expiryDate,
                    $guestId
                );
            }

            $stmt->execute();
            
            // Get the guest user's ID
            $userSql = "SELECT id FROM guest_users WHERE guest_id = ?";
            $userStmt = $connect->prepare($userSql);
            $userStmt->bind_param("s", $guestId);
            $userStmt->execute();
            $userResult = $userStmt->get_result();
            $userData = $userResult->fetch_assoc();

            if (!$userData) {
                throw new Exception("Guest user not found");
            }

            // Delete existing account links
            $deleteSql = "DELETE FROM guest_account_links WHERE guest_id = ?";
            $deleteStmt = $connect->prepare($deleteSql);
            $deleteStmt->bind_param("i", $userData['id']);
            $deleteStmt->execute();

            // Insert new account links
            if (!empty($linkedAccounts)) {
                $linkSql = "INSERT INTO guest_account_links (guest_id, account_id) VALUES (?, ?)";
                $linkStmt = $connect->prepare($linkSql);

                foreach ($linkedAccounts as $accountId) {
                    $linkStmt->bind_param("ii", $userData['id'], $accountId);
                    $linkStmt->execute();
                    
                    if ($linkStmt->affected_rows <= 0) {
                        throw new Exception("Failed to link account ID: " . $accountId);
                    }
                }
            }

            // If everything is successful, commit the transaction
            $connect->commit();
            
            if ($stmt->affected_rows >= 0) {
                $response = array(
                    'success' => true,
                    'messages' => 'Guest user updated successfully'
                );
            } else {
                throw new Exception("Failed to update guest user");
            }

        } catch (Exception $e) {
            // If there's an error, rollback the transaction
            $connect->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        $response = array(
            'success' => false,
            'messages' => 'Error updating guest user: ' . $e->getMessage()
        );
    }

    echo json_encode($response);
} else {
    echo json_encode(array(
        'success' => false,
        'messages' => 'Invalid request method'
    ));
} 