<?php
require_once 'core.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    echo json_encode(array('success' => false, 'message' => 'User not logged in'));
    exit();
}

$userId = $_SESSION['userId'];

try {
    // Query to get user's active preferences with component details
    $query = "SELECT udp.section_type, udp.component_key, udp.position, dac.component_name, dac.component_icon
             FROM user_dashboard_preferences udp
             JOIN dashboard_available_components dac 
                ON udp.section_type = dac.section_type 
                AND udp.component_key = dac.component_key
             WHERE udp.user_id = ? AND udp.is_active = 1
             ORDER BY udp.position";
    
    $stmt = $connect->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Initialize preferences array
    $preferences = array(
        'buttons' => array(),
        'cards' => array(),
        'analytics' => array()
    );
    
    // Populate preferences array
    while ($row = $result->fetch_assoc()) {
        $section = $row['section_type'];
        $preferences[$section][] = array(
            'key' => $row['component_key'],
            'name' => $row['component_name'],
            'icon' => $row['component_icon']
        );
    }
    
    // If no preferences found, insert and return default preferences
    if (empty($preferences['buttons']) && empty($preferences['cards']) && empty($preferences['analytics'])) {
        // Get default components
        $defaultQuery = "SELECT section_type, component_key, component_name, component_icon 
                        FROM dashboard_available_components 
                        WHERE is_default = 1";
        $defaultResult = $connect->query($defaultQuery);
        
        if ($defaultResult) {
            $position = 0;
            $insertQuery = "INSERT INTO user_dashboard_preferences 
                          (user_id, section_type, component_key, is_active, position) 
                          VALUES (?, ?, ?, 1, ?)";
            $insertStmt = $connect->prepare($insertQuery);
            
            while ($row = $defaultResult->fetch_assoc()) {
                $section = $row['section_type'];
                $preferences[$section][] = array(
                    'key' => $row['component_key'],
                    'name' => $row['component_name'],
                    'icon' => $row['component_icon']
                );
                
                // Insert default preference
                $insertStmt->bind_param("issi", $userId, $row['section_type'], $row['component_key'], $position);
                $insertStmt->execute();
                $position++;
            }
        }
    }
    
    echo json_encode(array('success' => true, 'preferences' => $preferences));
    
} catch (Exception $e) {
    echo json_encode(array('success' => false, 'message' => $e->getMessage()));
}

$connect->close();
?> 