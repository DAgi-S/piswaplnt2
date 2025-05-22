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
    'messages' => array()
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

    // Check database connection
    if ($connect->connect_error) {
        http_response_code(500);
        throw new Exception("Connection failed: " . $connect->connect_error);
    }

    // Start transaction
    $connect->begin_transaction();

    // First, get cycle details and calculate totals
    $cycleSql = "SELECT 
        bc.*,
        (SELECT COALESCE(SUM(o.total_price), 0) 
         FROM gps_orders o 
         JOIN gps_business_cycle_orders bco ON o.id = bco.order_id 
         WHERE bco.business_cycle_id = bc.id) as total_purchase_etb,
        (SELECT COALESCE(SUM(s.total), 0) 
         FROM gps_sales s 
         JOIN gps_business_cycle_sales bcs ON s.id = bcs.sale_id 
         WHERE bcs.business_cycle_id = bc.id) as total_sales_etb,
        (SELECT COALESCE(SUM(o.credit_amount), 0) 
         FROM gps_orders o 
         JOIN gps_business_cycle_orders bco ON o.id = bco.order_id 
         WHERE bco.business_cycle_id = bc.id) as total_credit_amount,
        (SELECT COALESCE(SUM(amount_etb), 0) 
         FROM gps_business_expenses 
         WHERE business_cycle_id = bc.id) as total_expenses_etb
    FROM gps_business_cycles bc
    WHERE bc.id = ?";

    $cycleStmt = $connect->prepare($cycleSql);
    if (!$cycleStmt) {
        throw new Exception("Error preparing cycle query: " . $connect->error);
    }

    $cycleStmt->bind_param("i", $cycleId);
    $cycleStmt->execute();
    $cycleResult = $cycleStmt->get_result();
    $cycle = $cycleResult->fetch_assoc();

    if (!$cycle) {
        http_response_code(404);
        throw new Exception("Business cycle not found");
    }

    if ($cycle['status'] !== 'active') {
        http_response_code(400);
        throw new Exception("Only active cycles can be completed");
    }

    // Calculate net profit
    $netProfit = $cycle['total_sales_etb'] - $cycle['total_purchase_etb'] - $cycle['total_expenses_etb'];

    // Get all investors
    $investorsSql = "SELECT id, name, share_percentage FROM gps_investors WHERE share_percentage > 0";
    $investorsResult = $connect->query($investorsSql);
    
    if (!$investorsResult) {
        throw new Exception("Error fetching investors: " . $connect->error);
    }

    // Calculate and store profit distributions
    while ($investor = $investorsResult->fetch_assoc()) {
        $investorShare = ($netProfit * $investor['share_percentage']) / 100;
        
        $distributionSql = "INSERT INTO gps_profit_distributions (
            business_cycle_id,
            investor_id,
            distribution_date,
            amount_etb,
            distribution_type,
            status,
            reinvested,
            notes,
            created_at
        ) VALUES (?, ?, CURRENT_DATE(), ?, 'profit', 'pending', 0, ?, NOW())";

        $distributionStmt = $connect->prepare($distributionSql);
        if (!$distributionStmt) {
            throw new Exception("Error preparing distribution query: " . $connect->error);
        }

        $notes = "Profit distribution for Cycle #" . $cycle['id'];
        $distributionStmt->bind_param("iids", $cycleId, $investor['id'], $investorShare, $notes);
        
        if (!$distributionStmt->execute()) {
            throw new Exception("Error creating profit distribution for " . $investor['name']);
        }
        
        $distributionStmt->close();
    }

    // Update cycle status and totals
    $updateSql = "UPDATE gps_business_cycles SET 
                  status = 'completed',
                  end_date = CURRENT_DATE(),
                  total_purchase_etb = ?,
                  total_sales_etb = ?,
                  total_expenses_etb = ?,
                  total_credit_amount = ?,
                  net_profit_etb = ?,
                  updated_at = NOW()
                  WHERE id = ?";

    $updateStmt = $connect->prepare($updateSql);
    if (!$updateStmt) {
        throw new Exception("Error preparing update query: " . $connect->error);
    }

    $updateStmt->bind_param("dddddi", 
        $cycle['total_purchase_etb'],
        $cycle['total_sales_etb'],
        $cycle['total_expenses_etb'],
        $cycle['total_credit_amount'],
        $netProfit,
        $cycleId
    );
    
    if (!$updateStmt->execute()) {
        throw new Exception("Error completing business cycle: " . $updateStmt->error);
    }

    // Add entry to gps_profit table
    $profitSql = "INSERT INTO gps_profit (
        date,
        total_purchase,
        total_sales,
        total_credit,
        total_expenses,
        total_profit,
        comment,
        created_at
    ) VALUES (CURRENT_DATE(), ?, ?, ?, ?, ?, ?, NOW())";

    $profitStmt = $connect->prepare($profitSql);
    if (!$profitStmt) {
        throw new Exception("Error preparing profit entry query: " . $connect->error);
    }

    $comment = "Profit from Business Cycle #" . $cycle['id'];
    $profitStmt->bind_param("ddddds", 
        $cycle['total_purchase_etb'],
        $cycle['total_sales_etb'],
        $cycle['total_credit_amount'],
        $cycle['total_expenses_etb'],
        $netProfit,
        $comment
    );
    
    if (!$profitStmt->execute()) {
        throw new Exception("Error creating profit entry: " . $profitStmt->error);
    }

    // Commit transaction
    $connect->commit();

    $response['success'] = true;
    $response['messages'][] = "Business cycle completed successfully with profit distributions";

} catch (Exception $e) {
    // Rollback transaction if active
    if ($connect && $connect->ping()) {
        $connect->rollback();
    }

    $response['success'] = false;
    $response['messages'][] = $e->getMessage();

} finally {
    // Close statements if they exist
    if (isset($cycleStmt)) {
        $cycleStmt->close();
    }
    if (isset($updateStmt)) {
        $updateStmt->close();
    }
    if (isset($profitStmt)) {
        $profitStmt->close();
    }

    // Close connection
    if (isset($connect) && $connect->ping()) {
        $connect->close();
    }
}

// Ensure no whitespace or output before this point
echo json_encode($response);
exit; 