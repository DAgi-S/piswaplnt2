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
    'data' => array()
);

try {
    // Check if user is logged in
    if (!isset($_SESSION['userId'])) {
        http_response_code(401);
        throw new Exception("Unauthorized access");
    }

    // Build the WHERE clause based on filters
    $where = array();
    $params = array();
    $types = '';

    if (!empty($_POST['start_date'])) {
        $where[] = "pd.distribution_date >= ?";
        $params[] = $_POST['start_date'];
        $types .= 's';
    }

    if (!empty($_POST['end_date'])) {
        $where[] = "pd.distribution_date <= ?";
        $params[] = $_POST['end_date'];
        $types .= 's';
    }

    if (!empty($_POST['investor_id'])) {
        $where[] = "pd.investor_id = ?";
        $params[] = intval($_POST['investor_id']);
        $types .= 'i';
    }

    if (!empty($_POST['status'])) {
        $where[] = "pd.status = ?";
        $params[] = $_POST['status'];
        $types .= 's';
    }

    // Construct the WHERE clause
    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

    // Fetch profit distributions
    $sql = "SELECT pd.*, 
            bc.cycle_number,
            i.name as investor_name,
            i.share_percentage
            FROM gps_profit_distributions pd
            LEFT JOIN gps_business_cycles bc ON pd.business_cycle_id = bc.id
            LEFT JOIN gps_investors i ON pd.investor_id = i.id
            $whereClause
            ORDER BY pd.distribution_date DESC";

    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error preparing query: " . $connect->error);
    }

    // Bind parameters if any
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $response['data'][] = array(
            'id' => $row['id'],
            'cycle_number' => $row['cycle_number'] ?: 'N/A',
            'investor_name' => $row['investor_name'],
            'share_percentage' => $row['share_percentage'],
            'amount_etb' => number_format($row['amount_etb'], 2),
            'distribution_date' => $row['distribution_date'],
            'distribution_type' => ucfirst($row['distribution_type']),
            'status' => $row['status'],
            'reinvested' => $row['reinvested'] ? 'Yes' : 'No'
        );
    }

} catch (Exception $e) {
    $response['error'] = $e->getMessage();

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