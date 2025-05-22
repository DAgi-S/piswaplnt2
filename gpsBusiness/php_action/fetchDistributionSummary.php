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
    'total_distributed' => 0,
    'completed_count' => 0,
    'pending_count' => 0,
    'average_distribution' => 0
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
        $where[] = "distribution_date >= ?";
        $params[] = $_POST['start_date'];
        $types .= 's';
    }

    if (!empty($_POST['end_date'])) {
        $where[] = "distribution_date <= ?";
        $params[] = $_POST['end_date'];
        $types .= 's';
    }

    if (!empty($_POST['investor_id'])) {
        $where[] = "investor_id = ?";
        $params[] = intval($_POST['investor_id']);
        $types .= 'i';
    }

    if (!empty($_POST['status'])) {
        $where[] = "status = ?";
        $params[] = $_POST['status'];
        $types .= 's';
    }

    // Construct the WHERE clause
    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

    // Get summary data
    $sql = "SELECT 
            COUNT(*) as total_count,
            SUM(CASE WHEN status = 'distributed' THEN amount_etb ELSE 0 END) as total_distributed,
            SUM(CASE WHEN status = 'distributed' THEN 1 ELSE 0 END) as completed_count,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            AVG(CASE WHEN status = 'distributed' THEN amount_etb ELSE NULL END) as average_distribution
            FROM gps_profit_distributions
            $whereClause";

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
    $summary = $result->fetch_assoc();

    $response['success'] = true;
    $response['total_distributed'] = floatval($summary['total_distributed'] ?: 0);
    $response['completed_count'] = intval($summary['completed_count'] ?: 0);
    $response['pending_count'] = intval($summary['pending_count'] ?: 0);
    $response['average_distribution'] = floatval($summary['average_distribution'] ?: 0);

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