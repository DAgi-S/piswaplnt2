<?php
session_start();

// Include database connection
require_once '../../php_action/db_connect.php';
require_once '../../php_action/core.php';

// Set headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Initialize response array
$response = array(
    'success' => false,
    'messages' => array(),
    'data' => array()
);

try {
    // Check if user is logged in
    if (!isset($_SESSION['userId'])) {
        http_response_code(401);
        throw new Exception("Unauthorized access");
    }

    // Check if distribution_id is provided
    if (!isset($_POST['distribution_id']) || empty($_POST['distribution_id'])) {
        throw new Exception("Distribution ID is required");
    }

    $distributionId = intval($_POST['distribution_id']);

    // Fetch distribution details
    $sql = "SELECT pd.*, 
            bc.cycle_number,
            i.name as investor_name,
            i.share_percentage
            FROM gps_profit_distributions pd
            LEFT JOIN gps_business_cycles bc ON pd.business_cycle_id = bc.id
            LEFT JOIN gps_investors i ON pd.investor_id = i.id
            WHERE pd.id = ?";

    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error preparing query: " . $connect->error);
    }

    $stmt->bind_param("i", $distributionId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Distribution not found");
    }

    $row = $result->fetch_assoc();
    
    $response['data'] = array(
        'id' => $row['id'],
        'cycle_number' => $row['cycle_number'] ?: 'N/A',
        'investor_name' => $row['investor_name'],
        'share_percentage' => $row['share_percentage'],
        'amount_etb' => number_format($row['amount_etb'], 2),
        'distribution_date' => $row['distribution_date'],
        'distribution_type' => ucfirst($row['distribution_type']),
        'status' => ucfirst($row['status']),
        'reinvested' => $row['reinvested'] ? 'Yes' : 'No',
        'created_at' => $row['created_at']
    );

    $response['success'] = true;

} catch (Exception $e) {
    $response['messages'][] = $e->getMessage();
    if (!isset($response['error'])) {
        $response['error'] = "Error fetching distribution details: " . $e->getMessage();
    }

} finally {
    // Close statement if it exists
    if (isset($stmt)) {
        $stmt->close();
    }

    // Close connection
    if (isset($connect) && $connect->ping()) {
        $connect->close();
    }
}

echo json_encode($response);
exit; 