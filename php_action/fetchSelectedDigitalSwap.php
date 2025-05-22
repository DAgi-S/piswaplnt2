<?php
require_once 'core.php';
require_once 'db_connect.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Clear any previous output
while (ob_get_level()) {
    ob_end_clean();
}

// Log request details for debugging
error_log("fetchSelectedDigitalswap.php accessed");
error_log("POST data: " . print_r($_POST, true));

// Set header for JSON response
header('Content-Type: application/json');

$response = array(
    'success' => false,
    'messages' => '',
    'data' => null
);

try {
    // Validate input
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        throw new Exception('Digital swap ID is required');
    }

    $id = (int)$_POST['id'];
    error_log("Processing digital swap ID: " . $id);

    // Prepare the SQL query with JOINs to get all necessary information
    $sql = "SELECT 
                ds.*,
                COALESCE(tt.name, ds.type) as type,
                COALESCE(a.account_platform, ds.platform) as platform,
                a.account_owner
            FROM digitalswap ds
            LEFT JOIN transaction_types tt ON ds.type_id = tt.id
            LEFT JOIN accounts a ON ds.account_id = a.id
            WHERE ds.id = ?";

    error_log("SQL Query: " . $sql);

    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $connect->error);
    }

    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception("Get result failed: " . $stmt->error);
    }

    if ($result->num_rows === 0) {
        throw new Exception("Digital swap record not found");
    }

    $data = $result->fetch_assoc();
    error_log("Raw data from database: " . print_r($data, true));

    // Format the data
    $data['transaction_date'] = date('Y-m-d', strtotime($data['transaction_date']));
    $data['amount'] = number_format((float)$data['amount'], 2, '.', '');
    
    // Handle image URL
    if (!empty($data['image'])) {
        // Get the current script's directory path
        $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
                  "://" . $_SERVER['HTTP_HOST'] . $scriptPath;
        $baseUrl = str_replace('/php_action', '', $baseUrl); // Remove php_action from path
        
        // Construct the full image URL
        $data['image_url'] = $baseUrl . '/' . ltrim($data['image'], '/');
        error_log("Image URL constructed: " . $data['image_url']);
    } else {
        $data['image_url'] = null;
    }

    $response['success'] = true;
    $response['messages'] = 'Digital swap details retrieved successfully';
    $response['data'] = $data;

    $stmt->close();

} catch (Exception $e) {
    error_log("Digital Swap View Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    $response['success'] = false;
    $response['messages'] = $e->getMessage();
}

// Close the database connection
$connect->close();

// Log the response for debugging
error_log("Response being sent: " . print_r($response, true));

// Send JSON response
echo json_encode($response, JSON_UNESCAPED_UNICODE); 