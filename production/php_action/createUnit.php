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
    $name = isset($_POST['name']) ? trim($connect->real_escape_string($_POST['name'])) : '';
    $abbreviation = isset($_POST['abbreviation']) ? trim($connect->real_escape_string($_POST['abbreviation'])) : '';
    $description = isset($_POST['description']) ? trim($connect->real_escape_string($_POST['description'])) : null;
    $status = isset($_POST['status']) ? intval($_POST['status']) : 1;

    // Validate required fields
    if(empty($name)) {
        throw new Exception('Unit name is required.');
    }

    if(empty($abbreviation)) {
        throw new Exception('Unit abbreviation is required.');
    }

    // Check if unit name already exists
    $checkSql = "SELECT id FROM units WHERE name = ? AND (deleted = 0 OR deleted IS NULL)";
    $checkStmt = $connect->prepare($checkSql);
    $checkStmt->bind_param('s', $name);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if($checkResult->num_rows > 0) {
        throw new Exception('Unit name already exists.');
    }
    $checkStmt->close();

    // Insert unit
    $sql = "INSERT INTO units (name, abbreviation, description, status, created_at, updated_at) 
            VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('sssi', $name, $abbreviation, $description, $status);
    
    if($stmt->execute()) {
        $response['success'] = true;
        $response['messages'] = 'Unit added successfully.';
        
        // Log the action
        $userId = isset($_SESSION['userId']) ? $_SESSION['userId'] : 0;
        $actionType = 'CREATE';
        $tableName = 'units';
        $recordId = $stmt->insert_id;
        $newValues = json_encode(array(
            'name' => $name,
            'abbreviation' => $abbreviation,
            'description' => $description,
            'status' => $status
        ));
        
       // logSystemAction($userId, $actionType, $tableName, $recordId, null, $newValues);
    } else {
        throw new Exception($connect->error);
    }
    
    $stmt->close();

} catch (Exception $e) {
    $response['success'] = false;
    $response['messages'] = 'Error occurred while adding unit: ' . $e->getMessage();
}

// Close database connection
$connect->close();

// Set content type header and output response
header('Content-Type: application/json');
echo json_encode($response); 