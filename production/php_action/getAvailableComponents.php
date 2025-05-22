<?php
require_once 'core.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    echo json_encode(array('success' => false, 'message' => 'User not logged in'));
    exit();
}

try {
    // Query to get all available components with their current status for the user
    $query = "SELECT 
                dac.section_type,
                dac.component_key,
                dac.component_name,
                dac.component_icon,
                dac.is_default,
                CASE WHEN udp.user_id IS NOT NULL THEN 1 ELSE 0 END as is_active
              FROM dashboard_available_components dac
              LEFT JOIN user_dashboard_preferences udp 
                ON dac.component_key = udp.component_key 
                AND dac.section_type = udp.section_type 
                AND udp.user_id = ? 
                AND udp.is_active = 1
              ORDER BY dac.section_type, dac.component_name";
    
    $stmt = $connect->prepare($query);
    $stmt->bind_param("i", $_SESSION['userId']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Initialize components array
    $components = array(
        'buttons' => array(),
        'cards' => array(),
        'analytics' => array()
    );
    
    // Populate components array
    while ($row = $result->fetch_assoc()) {
        $section = $row['section_type'];
        $components[$section][] = array(
            'key' => $row['component_key'],
            'name' => $row['component_name'],
            'icon' => $row['component_icon'],
            'isDefault' => $row['is_default'] == 1,
            'isActive' => $row['is_active'] == 1
        );
    }
    
    echo json_encode(array(
        'success' => true,
        'components' => $components
    ));
    
} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'message' => $e->getMessage()
    ));
}

$connect->close();
?> 