<?php
require_once '../includes/core.php';

// Check if user is logged in and has access
if (!isLoggedIn() || !isset($_GET['account_id'])) {
    setFlashMessage('Access denied', MESSAGE_ERROR);
    header('Location: ' . SITE_URL . '/dashboard.php');
    exit();
}

try {
    $account_id = (int)$_GET['account_id'];
    $return_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : SITE_URL . '/dashboard.php';

    // Get current user data
    $user = getCurrentUser();
    if (!$user) {
        throw new Exception("Guest user not found");
    }

    // Verify this account is linked to the guest user
    $sql = "SELECT gal.*, a.account_owner, a.account_platform, a.status 
            FROM guest_account_links gal 
            JOIN accounts a ON gal.account_id = a.id
            WHERE gal.guest_id = ? AND gal.account_id = ?
            AND a.status = 1"; // Only allow switching to active accounts
    
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("ii", $user['id'], $account_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $accountData = $result->fetch_assoc();
        
        // Update the active account
        $_SESSION['active_guest_account'] = $account_id;
        
        // Log the account switch
        logActivity('account_switch', sprintf(
            "Switched to account: %s (%s)",
            $accountData['account_platform'],
            $accountData['account_owner']
        ));
        
        setFlashMessage(sprintf(
            'Successfully switched to account: %s',
            $accountData['account_platform']
        ), MESSAGE_SUCCESS);
        
        // Clear any cached data that might be account-specific
        unset($_SESSION['account_balance']);
        unset($_SESSION['recent_transactions']);
    } else {
        setFlashMessage('You don\'t have access to this account or the account is inactive', MESSAGE_ERROR);
    }

} catch (Exception $e) {
    error_log("Error in switchAccount.php: " . $e->getMessage());
    setFlashMessage('Error switching account: ' . $e->getMessage(), MESSAGE_ERROR);
}

// Redirect back to the previous page or dashboard
header('Location: ' . $return_url);
exit();