<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode([
        'success' => false,
        'messages' => ['Session expired. Please log in again.']
    ]);
    exit();
}

require_once 'core.php';

// Set headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$response = array(
    'success' => false,
    'messages' => []
);

try {
    // Check database connection
    if (!isset($connect) || $connect->connect_error) {
        throw new Exception("Database connection failed");
    }

    // Validate action
    if (!isset($_POST['action'])) {
        throw new Exception("Invalid action");
    }

    if ($_POST['action'] === 'create') {
        // Validate start date
        if (!isset($_POST['start_date']) || empty($_POST['start_date'])) {
            throw new Exception("Start date is required");
        }

        // Generate cycle number if not provided
        $cycle_number = isset($_POST['cycle_number']) && !empty($_POST['cycle_number']) 
            ? $_POST['cycle_number'] 
            : generateCycleNumber($connect);

        // Start transaction
        $connect->begin_transaction();

        // Insert new cycle
        $sql = "INSERT INTO gps_business_cycles (
                    cycle_number, 
                    start_date, 
                    status, 
                    created_at
                ) VALUES (?, ?, 'active', NOW())";
        
        $stmt = $connect->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparing query: " . $connect->error);
        }

        $stmt->bind_param("ss", $cycle_number, $_POST['start_date']);
        
        if (!$stmt->execute()) {
            throw new Exception("Error creating business cycle: " . $stmt->error);
        }

        $connect->commit();
        
        $response['success'] = true;
        $response['messages'][] = "Business cycle created successfully";
        $response['cycle_id'] = $connect->insert_id;
    }

} catch (Exception $e) {
    if (isset($connect) && $connect->errno != 2006) {
        $connect->rollback();
    }
    error_log("Error in manageCycle.php: " . $e->getMessage());
    $response['messages'][] = $e->getMessage();
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
}

echo json_encode($response);
exit;

// Helper function to generate cycle number
function generateCycleNumber($connect) {
    $year = date('Y');
    
    // Get the last cycle number for this year
    $sql = "SELECT cycle_number 
            FROM gps_business_cycles 
            WHERE cycle_number LIKE 'BC-" . $year . "-%' 
            ORDER BY id DESC LIMIT 1";
    
    $result = $connect->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $last_number = intval(substr($row['cycle_number'], -3));
        $new_number = $last_number + 1;
    } else {
        $new_number = 1;
    }
    
    return sprintf("BC-%d-%03d", $year, $new_number);
} 