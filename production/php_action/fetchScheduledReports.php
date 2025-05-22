<?php
require_once 'core.php';
require_once 'classes/Database.php';

// Set proper content type for JSON response
header('Content-Type: application/json; charset=utf-8');

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    echo json_encode([
        'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Unauthorized access'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    $db = new Database();

    // Get total records count
    $totalRecords = $db->fetchOne("SELECT COUNT(*) as count FROM report_schedules");
    $totalRecords = isset($totalRecords['count']) ? intval($totalRecords['count']) : 0;
    
    // Build the query
    $sql = "SELECT 
                id,
                report_type,
                frequency,
                format,
                email_recipients,
                last_run,
                next_run,
                status,
                error_message,
                created_at
            FROM report_schedules";

    // Handle search if present
    $searchValue = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
    $filteredRecords = $totalRecords;
    $params = [];
    
    if (!empty($searchValue)) {
        $sql .= " WHERE report_type LIKE :search 
                  OR frequency LIKE :search 
                  OR format LIKE :search 
                  OR status LIKE :search";
        $params[':search'] = "%{$searchValue}%";
        
        $countSql = "SELECT COUNT(*) as count FROM report_schedules 
                     WHERE report_type LIKE :search 
                     OR frequency LIKE :search 
                     OR format LIKE :search 
                     OR status LIKE :search";
        $countResult = $db->fetchOne($countSql, $params);
        $filteredRecords = isset($countResult['count']) ? intval($countResult['count']) : 0;
    }

    // Handle ordering
    if (isset($_POST['order'])) {
        $columns = ['report_type', 'frequency', 'format', 'next_run', 'status', null];
        $orderColumn = $columns[$_POST['order'][0]['column']] ?? 'next_run';
        $orderDir = in_array(strtoupper($_POST['order'][0]['dir']), ['ASC', 'DESC']) ? strtoupper($_POST['order'][0]['dir']) : 'ASC';
        
        if ($orderColumn) {
            $sql .= " ORDER BY {$orderColumn} {$orderDir}";
        }
    } else {
        $sql .= " ORDER BY next_run ASC";
    }

    // Handle pagination
    if (isset($_POST['start']) && isset($_POST['length'])) {
        $start = intval($_POST['start']);
        $length = intval($_POST['length']);
        $sql .= " LIMIT :start, :length";
        $params[':start'] = intval($start);
        $params[':length'] = intval($length);
    }

    // Execute query
    $result = $db->fetchAll($sql, $params);
    $data = [];

    foreach ($result as $row) {
        // Format dates
        $row['last_run'] = $row['last_run'] ? date('Y-m-d H:i:s', strtotime($row['last_run'])) : 'Never';
        $row['next_run'] = date('Y-m-d H:i:s', strtotime($row['next_run']));
        $row['created_at'] = date('Y-m-d H:i:s', strtotime($row['created_at']));

        // Format report type for display
        $row['report_type'] = ucwords(str_replace('_', ' ', $row['report_type']));

        // Format frequency for display
        $row['frequency'] = ucfirst($row['frequency']);

        // Format format for display
        $row['format'] = strtoupper($row['format']);

        // Ensure all values are strings to prevent JSON encoding issues
        foreach ($row as $key => $value) {
            $row[$key] = strval($value);
        }

        $data[] = $row;
    }

    // Return JSON response
    echo json_encode([
        'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $filteredRecords,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Error in fetchScheduledReports.php: " . $e->getMessage());
    echo json_encode([
        'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'An error occurred while fetching reports'
    ], JSON_UNESCAPED_UNICODE);
} 