<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'core.php';


// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'error' => true,
        'message' => 'Not logged in',
        'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => []
    ]);
    exit();
}

try {
    // Check database connection
    if (!$connect) {
        throw new Exception("Database connection failed");
    }

    // Log the query for debugging
    error_log("Executing print templates query");
    
    $query = "SELECT * FROM print_settings ORDER BY template_name";
    $result = $connect->query($query);
    
    if (!$result) {
        throw new Exception("Query failed: " . $connect->error);
    }
    
    $data = array();
    while ($row = $result->fetch_assoc()) {
        $data[] = array(
            'template_name' => $row['template_name'],
            'page_size' => $row['page_size'],
            'orientation' => $row['orientation'],
            'font_family' => $row['font_family'],
            'updated_at' => $row['updated_at'] ?? date('Y-m-d H:i:s'),
            'actions' => '' // This will be populated by DataTables render function
        );
    }
    
    error_log("Found " . count($data) . " templates");
    
    // Ensure proper JSON response format for DataTables
    $response = array(
        "draw" => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
        "recordsTotal" => count($data),
        "recordsFiltered" => count($data),
        "data" => $data
    );
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();

} catch (Exception $e) {
    error_log("Error in fetchPrintTemplates.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
        'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => []
    ]);
    exit();
} 