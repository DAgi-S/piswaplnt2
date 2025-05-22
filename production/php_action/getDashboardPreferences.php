<?php
require_once 'core.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    echo json_encode(array('success' => false, 'message' => 'Not logged in'));
    exit();
}

$userId = $_SESSION['userId'];

// Get the user's dashboard preferences from the database
$query = "SELECT dashboard_preferences FROM users WHERE user_id = ?";
$stmt = $connect->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row && $row['dashboard_preferences']) {
    echo json_encode(array(
        'success' => true,
        'preferences' => json_decode($row['dashboard_preferences'])
    ));
} else {
    // Return default preferences if none are set
    $defaultPreferences = array(
        'buttons' => ['products', 'orders', 'stock'],
        'cards' => ['total_sales', 'inventory', 'pending_orders'],
        'analytics' => ['sales_chart', 'inventory_status']
    );
    
    echo json_encode(array(
        'success' => true,
        'preferences' => $defaultPreferences
    ));
}

$stmt->close();
?> 