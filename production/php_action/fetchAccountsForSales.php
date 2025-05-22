<?php
// Prevent any unwanted output
ob_start();

require_once 'core.php';
require_once 'db_connect.php';

// Clear any previous output
ob_clean();

// Set proper content type
header('Content-Type: application/json');

// Default response
$response = array(
    'success' => false,
    'data' => array(),
    'messages' => array()
);

try {
    // Check if user session exists
    if (!isset($_SESSION['userId'])) {
        throw new Exception("User session not found.");
    }
    
    // Query to fetch active accounts
    $sql = "SELECT 
                id, 
                account_owner,
                account_platform,
                currency,
                status
            FROM accounts 
            WHERE status = 1 
            ORDER BY account_owner, account_platform";
    
    $result = $connect->query($sql);
    
    if (!$result) {
        throw new Exception("Error fetching accounts: " . $connect->error);
    }

    $accounts = array();
    while ($row = $result->fetch_assoc()) {
        // Format display name: Owner - Platform (Currency)
        $displayName = sprintf(
            '%s - %s (%s)',
            $row['account_owner'],
            $row['account_platform'],
            $row['currency']
        );

        $accounts[] = array(
            'id' => $row['id'],
            'account_owner' => $row['account_owner'],
            'account_platform' => $row['account_platform'],
            'currency' => $row['currency'],
            'display_name' => $displayName
        );
    }
    
    // Set success response
    $response['success'] = true;
    $response['data'] = $accounts;
    $response['count'] = count($accounts);

} catch (Exception $e) {
    // Log the error
    error_log("Error in fetchAccountsForSales.php: " . $e->getMessage());
    
    // Set error response
    $response['messages'][] = $e->getMessage();
}

// Close database connection
$connect->close();

// Send response
echo json_encode($response);
exit();
?> 