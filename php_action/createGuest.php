<?php
require_once 'core.php';

// Check if user has permission to create guests
if (!hasPermission('guest.create') && !hasPermission('guest_create') && 
    !hasPermission('guest.manage') && !hasPermission('manage_guests')) {
    echo json_encode([
        'success' => false,
        'messages' => 'Access denied: Insufficient permissions'
    ]);
    exit();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $fullName = $_POST['fullName'];
        $linkedAccounts = isset($_POST['linkedAccount']) ? $_POST['linkedAccount'] : array();
        // Store access levels as JSON array
        $accessLevel = isset($_POST['access']) ? json_encode($_POST['access']) : json_encode([]);
        $expiryDate = !empty($_POST['expiryDate']) ? $_POST['expiryDate'] : NULL;

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

        // Generate guest ID
        $guestId = 'GUEST' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);

        // Start transaction
        $connect->begin_transaction();

        try {
            // Insert guest user with linked accounts
            $sql = "INSERT INTO guest_users (
                guest_id, 
                username, 
                password, 
                full_name,
                account_name,
                currency,
                linked_account_id,
                access_level,
                status,
                expiry_date,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())";

            $stmt = $connect->prepare($sql);
            $stmt->bind_param("sssssssss", 
                $guestId,
                $username,
                $password,
                $fullName,
                $accountName,
                $currency,
                $linkedAccountsStr,
                $accessLevel,
                $expiryDate
            );

            $stmt->execute();
            
            if ($stmt->affected_rows <= 0) {
                throw new Exception("Failed to create guest user");
            }

            // Get the newly inserted guest user's ID
            $newGuestId = $connect->insert_id;

            // Insert into guest_account_links table for each linked account
            if (!empty($linkedAccounts)) {
                $linkSql = "INSERT INTO guest_account_links (guest_id, account_id) VALUES (?, ?)";
                $linkStmt = $connect->prepare($linkSql);

                foreach ($linkedAccounts as $accountId) {
                    $linkStmt->bind_param("ii", $newGuestId, $accountId);
                    $linkStmt->execute();
                    
                    if ($linkStmt->affected_rows <= 0) {
                        throw new Exception("Failed to link account ID: " . $accountId);
                    }
                }
            }

            // If everything is successful, commit the transaction
            $connect->commit();

            $response = array(
                'success' => true,
                'messages' => 'Guest user created successfully'
            );

        } catch (Exception $e) {
            // If there's an error, rollback the transaction
            $connect->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        $response = array(
            'success' => false,
            'messages' => 'Error creating guest user: ' . $e->getMessage()
        );
    }

    echo json_encode($response);
} else {
    echo json_encode(array(
        'success' => false,
        'messages' => 'Invalid request method'
    ));
} 