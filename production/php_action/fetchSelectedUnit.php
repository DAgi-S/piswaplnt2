<?php
require_once 'core.php';

// Check if user has permission to view settings
if (!hasPermission('settings.units.manage') && !hasPermission('settings_access') && !hasPermission('system.settings.view')) {
    echo json_encode(array(
        'success' => false,
        'messages' => 'Access denied. Permission to view settings required.'
    ));
    exit();
}

$unitId = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($unitId === 0) {
    echo json_encode(array(
        'success' => false,
        'messages' => 'Unit ID is required.'
    ));
    exit();
}

try {
    // Prepare and execute query
    $sql = "SELECT id, name, abbreviation, description, status 
            FROM units 
            WHERE id = ? AND (deleted = 0 OR deleted IS NULL)";
            
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('i', $unitId);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result && $result->num_rows > 0) {
        $unit = $result->fetch_assoc();
        echo json_encode(array(
            'success' => true,
            'data' => $unit
        ));
    } else {
        echo json_encode(array(
            'success' => false,
            'messages' => 'Unit not found.'
        ));
    }

    $stmt->close();
} catch (Exception $e) {
    error_log("Error in fetchSelectedUnit.php: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'messages' => 'Error occurred while fetching unit data.'
    ));
}

// Close database connection
$connect->close(); 