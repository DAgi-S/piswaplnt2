<?php
// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'core.php';

// Initialize the response array
$output = array(
    'data' => array()
);

try {
    // Debug session state
    error_log("Session state - userId: " . (isset($_SESSION['userId']) ? $_SESSION['userId'] : 'not set'));
    
    // Check if user is logged in
    if (!isset($_SESSION['userId'])) {
        throw new Exception("Session expired. Please log in again.");
    }

    // Check database connection
    if (!isset($connect) || $connect->connect_error) {
        throw new Exception("Database connection failed: " . ($connect->connect_error ?? 'Connection not established'));
    }

    // Debug query
    error_log("Executing query: SELECT * FROM gps_payment_followup ORDER BY payment_date DESC, id DESC");

    // Query to fetch payment follow-up data
    $sql = "SELECT * FROM gps_payment_followup ORDER BY payment_date DESC, id DESC";
    $stmt = $connect->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Error preparing statement: " . $connect->error);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Error executing query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    // Debug result
    error_log("Query executed. Number of rows: " . $result->num_rows);

    while ($row = $result->fetch_assoc()) {
        // Format amount with currency
        $amount = $row['currency'] . ' ' . number_format($row['amount'], 2);
        
        // Format rate
        $rate = is_null($row['rate']) ? '-' : number_format($row['rate'], 2);
        
        // Format transfer info
        $transfer_to = !empty($row['transfer_to']) ? htmlspecialchars($row['transfer_to']) : '-';
        $bank_platform = !empty($row['bank_platform_name']) ? htmlspecialchars($row['bank_platform_name']) : '-';
        
        // Format comment with HTML entities
        $comment = !empty($row['comment']) ? htmlspecialchars($row['comment']) : '';
        
        // Create view image link if exists
        $imageLink = '';
        if (!empty($row['payment_image'])) {
            $ext = strtolower(pathinfo($row['payment_image'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                $imageLink = ' <a href="../'.$row['payment_image'].'" target="_blank" class="btn btn-xs btn-info"><i class="fas fa-image"></i> View</a>';
            } else {
                $imageLink = ' <a href="../'.$row['payment_image'].'" target="_blank" class="btn btn-xs btn-info"><i class="fas fa-file-pdf"></i> View</a>';
            }
        }

        // Create action buttons
        $actionButtons = '
            <div class="btn-group">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                    Action <span class="caret"></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-right">
                    <li><a href="#" onclick="editPaymentFollowup('.$row['id'].')"><i class="fas fa-edit"></i> Edit</a></li>
                    <li><a href="#" onclick="deletePaymentFollowup('.$row['id'].')"><i class="fas fa-trash"></i> Delete</a></li>
                </ul>
            </div>' . $imageLink;

        // Add the row to the output
        $output['data'][] = array(
            date('Y-m-d', strtotime($row['payment_date'])),
            htmlspecialchars($row['paid_by']),
            $amount,
            $rate,
            $transfer_to,
            $bank_platform,
            $comment,
            $actionButtons
        );
    }

    $stmt->close();

} catch (Exception $e) {
    error_log("Error in fetchGpsPaymentFollowup.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(array(
        'error' => true,
        'message' => "Could not load payment follow-up data. Please try refreshing the page.",
        'debug' => $e->getMessage()
    ));
    exit();
}

// Close the database connection
if (isset($connect)) {
    $connect->close();
}

// Set the content type to JSON
header('Content-Type: application/json');

// Return the JSON response
echo json_encode($output); 