<?php
require_once 'core.php';

// Default response
$output = array(
    "draw" => isset($_POST['draw']) ? intval($_POST['draw']) : 1,
    "recordsTotal" => 0,
    "recordsFiltered" => 0,
    "data" => array()
);

try {
    // Base query
    $query = "SELECT 
        al.*,
        COALESCE(u.username, 'System') as username 
    FROM audit_log al
    LEFT JOIN users u ON al.user_id = u.user_id
    WHERE 1=1";
    
    $whereClause = "";
    $params = array();
    
    // Apply date range filter
    if (!empty($_POST['daterange'])) {
        $dates = explode(' - ', $_POST['daterange']);
        if (count($dates) == 2) {
            $startDate = date('Y-m-d 00:00:00', strtotime(trim($dates[0])));
            $endDate = date('Y-m-d 23:59:59', strtotime(trim($dates[1])));
            $whereClause .= " AND al.timestamp BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }
    }
    
    // Apply activity type filter
    if (!empty($_POST['activityType'])) {
        $whereClause .= " AND al.activity_type = ?";
        $params[] = $_POST['activityType'];
    }
    
    // Get total records count
    $totalRecords = $connect->query("SELECT COUNT(*) FROM audit_log");
    $output['recordsTotal'] = (int)$totalRecords->fetchColumn();
    
    // Add where clause to query
    $query .= $whereClause;
    
    // Get filtered records count
    $stmtFiltered = $connect->prepare($query);
    if (!empty($params)) {
        $stmtFiltered->execute($params);
    } else {
        $stmtFiltered->execute();
    }
    $output['recordsFiltered'] = $stmtFiltered->rowCount();
    
    // Add sorting
    $query .= " ORDER BY al.timestamp DESC";
    
    // Add pagination
    if (isset($_POST['start']) && isset($_POST['length'])) {
        $start = intval($_POST['start']);
        $length = intval($_POST['length']);
        $query .= " LIMIT $start, $length";
    }
    
    // Execute final query
    $stmt = $connect->prepare($query);
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    
    // Format data for DataTables
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $output['data'][] = array(
            "timestamp" => date('Y-m-d H:i:s', strtotime($row['timestamp'])),
            "username" => $row['username'],
            "activity_type" => $row['activity_type'],
            "description" => $row['description'],
            "old_value" => !empty($row['old_value']) ? substr($row['old_value'], 0, 100) : '',
            "new_value" => !empty($row['new_value']) ? substr($row['new_value'], 0, 100) : '',
            "ip_address" => $row['ip_address'],
            "reference_id" => $row['reference_id']
        );
    }
    
} catch (Exception $e) {
    $output['error'] = $e->getMessage();
    error_log("Audit Log Error: " . $e->getMessage());
}

// Debug information
error_log("Query: " . $query);
error_log("Parameters: " . print_r($params, true));
error_log("Total Records: " . $output['recordsTotal']);
error_log("Filtered Records: " . $output['recordsFiltered']);
error_log("Data Count: " . count($output['data']));

// Ensure proper JSON encoding
header('Content-Type: application/json');
echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit();