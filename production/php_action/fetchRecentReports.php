<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

$response = array('success' => false, 'data' => array());

try {
    // Get recent reports (last 10)
    $sql = "SELECT 
                r.id,
                TRIM(r.report_name) as name,
                TRIM(r.report_type) as type,
                DATE_FORMAT(r.created_at, '%Y-%m-%d %H:%i:%s') as date,
                TRIM(r.report_url) as url,
                COALESCE(u.username, 'System') as generated_by
            FROM reports r
            LEFT JOIN users u ON r.created_by = u.user_id
            WHERE r.report_name IS NOT NULL 
            AND r.report_type IS NOT NULL 
            AND r.report_url IS NOT NULL
            ORDER BY r.created_at DESC
            LIMIT 10";
    
    $result = $connect->query($sql);
    
    if ($result) {
        $response['data'] = array();
        while ($row = $result->fetch_assoc()) {
            // Format report type for display (e.g., 'sales_report' -> 'Sales Report')
            $type = ucwords(str_replace('_', ' ', $row['type']));
            
            // Validate URL
            $url = filter_var($row['url'], FILTER_SANITIZE_URL);
            
            $response['data'][] = array(
                'id' => (int)$row['id'],
                'name' => htmlspecialchars($row['name']),
                'type' => $type,
                'generated_by' => htmlspecialchars($row['generated_by']),
                'date' => $row['date'],
                'url' => $url
            );
        }
        $response['success'] = true;
    } else {
        throw new Exception("Error fetching reports: " . $connect->error);
    }
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT);
?> 