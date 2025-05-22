<?php
require_once 'core.php';
require_once 'includes/LogManager.php';

// Set proper content type
header('Content-Type: application/json');

// Initialize response array
$response = array(
    'success' => false,
    'messages' => array()
);

try {
    // Check if user is logged in
    if (!isset($_SESSION['userId'])) {
        throw new Exception("User session not found");
    }

    // Validate required parameters
    if (!isset($_POST['action']) || !isset($_POST['module'])) {
        throw new Exception("Missing required parameters");
    }

    // Initialize LogManager
    $logManager = new LogManager($connect, $_SESSION['userId']);

    // Get parameters
    $action = $_POST['action'];
    $module = $_POST['module'];
    $referenceId = isset($_POST['reference_id']) ? intval($_POST['reference_id']) : 0;
    $additionalData = isset($_POST['additional_data']) ? json_decode($_POST['additional_data'], true) : null;

    // Log the activity
    if ($additionalData) {
        // If additional data is provided, log as audit
        $logManager->logAudit(
            $module . "_action",
            $action,
            null,
            $additionalData,
            $referenceId
        );
    } else {
        // Otherwise log as simple activity
        $logManager->logActivity(
            $action,
            $module,
            $referenceId
        );
    }

    $response['success'] = true;
    $response['messages'][] = "Activity logged successfully";

} catch (Exception $e) {
    $response['messages'][] = $e->getMessage();
    error_log("Error in logActivity.php: " . $e->getMessage());
}

// Send JSON response
echo json_encode($response);
exit(); 