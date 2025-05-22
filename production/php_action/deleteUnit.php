<?php
require_once 'core.php';


// Check if user has permission to manage units
if (!hasPermission('settings.units.manage')) {
    $response = array('success' => false, 'messages' => 'Access denied. Permission to manage units required.');
    echo json_encode($response);
    exit();
}

$response = array('success' => false, 'messages' => '');

try {
    // Validate and sanitize input
    $unitId = isset($_POST['id']) ? intval($_POST['id']) : 0;

    // Validate required fields
    if(empty($unitId)) {
        throw new Exception('Unit ID is required.');
    }

    // Check if unit exists and get current values for logging
    $checkSql = "SELECT * FROM units WHERE id = ? AND (deleted = 0 OR deleted IS NULL)";
    $checkStmt = $connect->prepare($checkSql);
    $checkStmt->bind_param('i', $unitId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if($checkResult->num_rows === 0) {
        throw new Exception('Unit not found or already deleted.');
    }
    
    $currentUnit = $checkResult->fetch_assoc();
    $checkStmt->close();

    // Check if unit is being used in any related tables
    // Add your checks here if needed
    // Example: Check if unit is used in products, stock, etc.

    // Soft delete the unit
    $sql = "UPDATE units 
            SET deleted = 1, 
                updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?";
            
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('i', $unitId);
    
    if($stmt->execute()) {
        $response['success'] = true;
        $response['messages'] = 'Unit deleted successfully.';
        
        // Log the action
        $userId = isset($_SESSION['userId']) ? $_SESSION['userId'] : 0;
        $actionType = 'DELETE';
        $tableName = 'units';
        $oldValues = json_encode(array(
            'id' => $currentUnit['id'],
            'name' => $currentUnit['name'],
            'abbreviation' => $currentUnit['abbreviation'],
            'description' => $currentUnit['description'],
            'status' => $currentUnit['status']
        ));
        
       // logSystemAction($userId, $actionType, $tableName, $unitId, $oldValues, null);
    } else {
        throw new Exception($connect->error);
    }
    
    $stmt->close();

} catch (Exception $e) {
    $response['success'] = false;
    $response['messages'] = 'Error occurred while deleting unit: ' . $e->getMessage();
}

// Close database connection
$connect->close();

// Set content type header and output response
header('Content-Type: application/json');
echo json_encode($response); 