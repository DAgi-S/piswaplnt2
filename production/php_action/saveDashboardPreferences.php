<?php
require_once 'core.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    echo json_encode(array('success' => false, 'message' => 'Not logged in'));
    exit();
}

// Get the preferences from POST data
$preferences = isset($_POST['preferences']) ? json_decode($_POST['preferences'], true) : null;

if (!$preferences) {
    echo json_encode(array('success' => false, 'message' => 'No preferences provided'));
    exit();
}

$userId = $_SESSION['userId'];

try {
    // Start transaction
    $connect->begin_transaction();

    // First, deactivate all current preferences for this user
    $deactivateQuery = "UPDATE user_dashboard_preferences SET is_active = 0 WHERE user_id = ?";
    $stmt = $connect->prepare($deactivateQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    // Prepare insert statement
    $insertQuery = "INSERT INTO user_dashboard_preferences (user_id, section_type, component_key, is_active, position) 
                   VALUES (?, ?, ?, 1, ?) 
                   ON DUPLICATE KEY UPDATE is_active = 1, position = VALUES(position)";
    $stmt = $connect->prepare($insertQuery);

    // Insert new preferences
    $position = 0;
    foreach ($preferences as $section => $components) {
        foreach ($components as $index => $component) {
            $stmt->bind_param("issi", $userId, $section, $component, $position);
            $stmt->execute();
            $position++;
        }
    }

    // Commit transaction
    $connect->commit();
    
    echo json_encode(array('success' => true));
} catch (Exception $e) {
    // Rollback on error
    $connect->rollback();
    echo json_encode(array('success' => false, 'message' => $e->getMessage()));
}

$connect->close();
?> 