<?php
require_once 'core.php';
require_once 'telegram_notification.php';

// Check if user has permission to view guests
if (!hasPermission('guest.view') && !hasPermission('view_guests') && !hasPermission('guest.manage') && !hasPermission('manage_guests')) {
    echo json_encode([
        'success' => false,
        'messages' => 'Access denied: Insufficient permissions'
    ]);
    exit();
}

$response = array(
    'data' => array()
);

try {
    $sql = "SELECT * FROM guest_users ORDER BY created_at DESC";
    $result = $connect->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Ensure access_level is valid JSON
            if($row['access_level'] === null || $row['access_level'] === '') {
                $row['access_level'] = '[]';
            } else if(!json_decode($row['access_level'])) {
                // If it's not valid JSON, try to convert from old format
                $oldAccess = explode(',', $row['access_level']);
                $row['access_level'] = json_encode(array_filter($oldAccess));
            }

            $response['data'][] = array(
                'guest_id' => $row['guest_id'],
                'username' => $row['username'],
                'full_name' => $row['full_name'],
                'account_name' => $row['account_name'],
                'currency' => $row['currency'],
                'access_level' => $row['access_level'],
                'status' => $row['status'],
                'expiry_date' => $row['expiry_date'],
                'last_login' => $row['last_login']
            );
        }
    } else {
        throw new Exception("Error fetching guests: " . $connect->error);
    }

} catch (Exception $e) {
    // Log the error
    error_log("Error in fetchGuests.php: " . $e->getMessage());
    
    // Send notification about the error
    $errorMsg = "<b>🔴 Error Fetching Guest Data</b>\n\n";
    $errorMsg .= "Error: " . $e->getMessage() . "\n";
    $errorMsg .= "Time: " . date('Y-m-d H:i:s') . "\n";
    sendTelegramNotification($errorMsg);
    
    // Return empty data to prevent DataTables error
    $response['data'] = array();
}

header('Content-Type: application/json');
echo json_encode($response); 