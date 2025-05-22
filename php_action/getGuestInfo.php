<?php
require_once 'core.php';

// Check if user has permission to view or edit guests
if (!hasPermission('guest.view') && !hasPermission('view_guests') && 
    !hasPermission('guest.edit') && !hasPermission('guest_edit') && 
    !hasPermission('guest.manage') && !hasPermission('manage_guests')) {
    echo json_encode([
        'success' => false,
        'messages' => 'Access denied: Insufficient permissions'
    ]);
    exit();
}

if (!isset($_POST['guestId'])) {
    echo json_encode([
        'success' => false,
        'messages' => 'Guest ID is required'
    ]);
    exit();
}

$guestId = $connect->real_escape_string($_POST['guestId']);

try {
    // Get guest information using the correct table structure
    $sql = "SELECT * FROM guest_users WHERE guest_id = ?";
            
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("s", $guestId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        // Ensure linked_account_id is never null in the response
        $linked_account_id = $row['linked_account_id'] ? $row['linked_account_id'] : '';
        
        echo json_encode([
            'success' => true,
            'data' => [
                'id' => $row['id'],
                'guest_id' => $row['guest_id'],
                'username' => $row['username'],
                'full_name' => $row['full_name'],
                'account_name' => $row['account_name'],
                'currency' => $row['currency'],
                'balance' => $row['balance'],
                'linked_account_id' => $linked_account_id,
                'access_level' => $row['access_level'] ? $row['access_level'] : '',
                'status' => $row['status'],
                'expiry_date' => $row['expiry_date']
            ]
        ]);
    } else {
        throw new Exception("Guest not found");
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'messages' => $e->getMessage()
    ]);
} 