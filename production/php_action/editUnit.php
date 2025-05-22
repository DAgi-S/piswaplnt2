<?php
require_once 'core.php';

// Check if user has permission to manage units
if (!hasPermission('settings.units.manage')) {
    $response = array('success' => false, 'messages' => 'Access denied. Permission to manage units required.');
    echo json_encode($response);
    exit();
}

// If this is a GET request, fetch the unit data
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $unitId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($unitId === 0) {
        echo json_encode(array('success' => false, 'messages' => 'Unit ID is required.'));
        exit();
    }

    try {
        $sql = "SELECT id, name, abbreviation, description, status 
                FROM units 
                WHERE id = ? AND (deleted = 0 OR deleted IS NULL)";
        
        $stmt = $connect->prepare($sql);
        $stmt->bind_param('i', $unitId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $unit = $result->fetch_assoc();
            echo json_encode(array('success' => true, 'data' => $unit));
        } else {
            echo json_encode(array('success' => false, 'messages' => 'Unit not found.'));
        }
        exit();
    } catch (Exception $e) {
        echo json_encode(array('success' => false, 'messages' => 'Error fetching unit data.'));
        exit();
    }
}

// For POST requests (actual update)
$response = array('success' => false, 'messages' => '');

try {
    // Validate and sanitize input
    $unitId = isset($_POST['editUnitId']) ? intval($_POST['editUnitId']) : 0;
    $name = isset($_POST['editName']) ? trim($connect->real_escape_string($_POST['editName'])) : '';
    $abbreviation = isset($_POST['editAbbreviation']) ? trim($connect->real_escape_string($_POST['editAbbreviation'])) : '';
    $description = isset($_POST['editDescription']) ? trim($connect->real_escape_string($_POST['editDescription'])) : null;
    $status = isset($_POST['editStatus']) ? intval($_POST['editStatus']) : 1;

    // Validate required fields
    if(empty($unitId)) {
        throw new Exception('Unit ID is required.');
    }

    if(empty($name)) {
        throw new Exception('Unit name is required.');
    }

    if(empty($abbreviation)) {
        throw new Exception('Unit abbreviation is required.');
    }

    // Check if unit exists
    $checkSql = "SELECT * FROM units WHERE id = ? AND (deleted = 0 OR deleted IS NULL)";
    $checkStmt = $connect->prepare($checkSql);
    $checkStmt->bind_param('i', $unitId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if($checkResult->num_rows === 0) {
        throw new Exception('Unit not found.');
    }
    
    $currentUnit = $checkResult->fetch_assoc();
    $checkStmt->close();

    // Check if new name already exists (excluding current unit)
    $nameCheckSql = "SELECT id FROM units WHERE name = ? AND id != ? AND (deleted = 0 OR deleted IS NULL)";
    $nameCheckStmt = $connect->prepare($nameCheckSql);
    $nameCheckStmt->bind_param('si', $name, $unitId);
    $nameCheckStmt->execute();
    $nameCheckResult = $nameCheckStmt->get_result();
    
    if($nameCheckResult->num_rows > 0) {
        throw new Exception('Unit name already exists.');
    }
    $nameCheckStmt->close();

    // Update unit
    $sql = "UPDATE units 
            SET name = ?, 
                abbreviation = ?, 
                description = ?, 
                status = ?, 
                updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?";
    
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('sssii', $name, $abbreviation, $description, $status, $unitId);
    
    if($stmt->execute()) {
        $response['success'] = true;
        $response['messages'] = 'Unit updated successfully.';
        
        // Log the action
        $userId = isset($_SESSION['userId']) ? $_SESSION['userId'] : 0;
        $actionType = 'UPDATE';
        $tableName = 'units';
        $oldValues = json_encode(array(
            'name' => $currentUnit['name'],
            'abbreviation' => $currentUnit['abbreviation'],
            'description' => $currentUnit['description'],
            'status' => $currentUnit['status']
        ));
        $newValues = json_encode(array(
            'name' => $name,
            'abbreviation' => $abbreviation,
            'description' => $description,
            'status' => $status
        ));
        
       // logSystemAction($userId, $actionType, $tableName, $unitId, $oldValues, $newValues);
    } else {
        throw new Exception($connect->error);
    }
    
    $stmt->close();

} catch (Exception $e) {
    $response['success'] = false;
    $response['messages'] = 'Error occurred while updating unit: ' . $e->getMessage();
}

// Close database connection
$connect->close();

// Set content type header and output response
header('Content-Type: application/json');
echo json_encode($response); 