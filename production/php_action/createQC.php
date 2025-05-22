<?php
require_once '../includes/db_connect.php';

// Set header type to JSON
header('Content-Type: application/json');

// Initialize response array
$response = array();

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['success'] = false;
    $response['messages'] = 'Invalid request method';
    echo json_encode($response);
    exit();
}

try {
    // Get form data
    $productionOrderId = isset($_POST['productionOrderId']) ? intval($_POST['productionOrderId']) : 0;
    $inspectionDate = isset($_POST['inspectionDate']) ? $_POST['inspectionDate'] : '';
    $quantityChecked = isset($_POST['quantityChecked']) ? floatval($_POST['quantityChecked']) : 0;
    $quantityPassed = isset($_POST['quantityPassed']) ? floatval($_POST['quantityPassed']) : 0;
    $quantityFailed = isset($_POST['quantityFailed']) ? floatval($_POST['quantityFailed']) : 0;
    $defectType = isset($_POST['defectType']) ? $_POST['defectType'] : '';
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';

    // Validate required fields
    if (!$productionOrderId || !$inspectionDate || $quantityChecked <= 0) {
        throw new Exception('Please fill in all required fields');
    }

    // Validate quantities
    if ($quantityPassed + $quantityFailed !== $quantityChecked) {
        throw new Exception('Sum of passed and failed quantities must equal checked quantity');
    }

    // Validate inspection date
    $inspectionDateObj = DateTime::createFromFormat('Y-m-d', $inspectionDate);
    if (!$inspectionDateObj || $inspectionDateObj->format('Y-m-d') !== $inspectionDate) {
        throw new Exception('Invalid inspection date format');
    }

    // Verify production order exists and is valid
    $sql = "SELECT id, status FROM production_orders WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $productionOrderId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Invalid production order');
    }

    $orderData = $result->fetch_assoc();
    if (!in_array($orderData['status'], ['inprogress', 'completed'])) {
        throw new Exception('Production order must be in progress or completed');
    }

    // Determine QC status based on quantities
    if ($quantityFailed === 0) {
        $status = 'passed';
    } elseif ($quantityPassed === 0) {
        $status = 'failed';
    } else {
        $status = 'partially_passed';
    }

    // Start transaction
    $connect->begin_transaction();

    // Insert quality control entry
    $sql = "INSERT INTO quality_control (
                production_order_id, 
                inspection_date, 
                quantity_checked, 
                quantity_passed, 
                quantity_failed, 
                defect_type, 
                notes, 
                status, 
                created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $connect->prepare($sql);
    $createdBy = isset($_SESSION['userId']) ? $_SESSION['userId'] : null;
    
    $stmt->bind_param(
        "isdddssssi",
        $productionOrderId,
        $inspectionDate,
        $quantityChecked,
        $quantityPassed,
        $quantityFailed,
        $defectType,
        $notes,
        $status,
        $createdBy
    );

    $stmt->execute();

    if ($stmt->affected_rows !== 1) {
        throw new Exception('Failed to create quality control entry');
    }

    // Get the inserted ID
    $qcId = $stmt->insert_id;

    // If there are defects, log them in quality_control_results
    if ($quantityFailed > 0 && !empty($defectType)) {
        $sql = "INSERT INTO quality_control_results (
                    control_date,
                    measured_value,
                    result_status,
                    inspector_id
                ) VALUES (?, ?, ?, ?)";
        
        $stmt = $connect->prepare($sql);
        $resultStatus = 'failed';
        
        $stmt->bind_param(
            "sssi",
            $inspectionDate,
            $defectType,
            $resultStatus,
            $createdBy
        );

        $stmt->execute();
    }

    // Commit transaction
    $connect->commit();

    // Create audit log entry
    $auditSql = "INSERT INTO audit_logs (
                    user_id,
                    action,
                    table_name,
                    record_id,
                    changes,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, NOW())";

    $auditStmt = $connect->prepare($auditSql);
    $action = 'create';
    $tableName = 'quality_control';
    $changes = json_encode([
        'production_order_id' => $productionOrderId,
        'inspection_date' => $inspectionDate,
        'quantity_checked' => $quantityChecked,
        'quantity_passed' => $quantityPassed,
        'quantity_failed' => $quantityFailed,
        'status' => $status
    ]);

    $auditStmt->bind_param(
        "issis",
        $createdBy,
        $action,
        $tableName,
        $qcId,
        $changes
    );
    $auditStmt->execute();

    // Set success response
    $response['success'] = true;
    $response['messages'] = 'Quality control entry created successfully';
    $response['qcId'] = $qcId;

} catch (Exception $e) {
    // Rollback transaction if active
    if ($connect->inTransaction()) {
        $connect->rollback();
    }

    $response['success'] = false;
    $response['messages'] = $e->getMessage();

} finally {
    // Close any open statements
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($auditStmt)) {
        $auditStmt->close();
    }

    // Return JSON response
    echo json_encode($response);
} 