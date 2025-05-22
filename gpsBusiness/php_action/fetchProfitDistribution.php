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

    // Check if cycle_id is provided
    if (!isset($_POST['cycle_id']) || empty($_POST['cycle_id'])) {
        http_response_code(400);
        throw new Exception("Cycle ID is required");
    }

    $cycleId = intval($_POST['cycle_id']);

    // Fetch profit distributions for the cycle
    $sql = "SELECT pd.*, i.name as investor_name, i.share_percentage 
            FROM gps_profit_distributions pd
            JOIN gps_investors i ON pd.investor_id = i.id
            WHERE pd.business_cycle_id = ?
            ORDER BY pd.distribution_date DESC";

    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error preparing query: " . $connect->error);
    }

    $stmt->bind_param("i", $cycleId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $response['data'][] = array(
            'investor' => $row['investor_name'],
            'share_percentage' => $row['share_percentage'],
            'amount_etb' => number_format($row['amount_etb'], 2),
            'distribution_date' => $row['distribution_date'],
            'distribution_type' => $row['distribution_type'],
            'status' => $row['status'],
            'reinvested' => $row['reinvested'] ? 'Yes' : 'No'
        );
    }

    $response['success'] = true;

} catch (Exception $e) {
    $response['success'] = false;
    $response['messages'][] = $e->getMessage();

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