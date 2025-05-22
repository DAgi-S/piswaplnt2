<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../php_action/core.php';
require_once '../php_action/db_connect.php';

// Set proper headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Check database connection
    if ($connect->connect_error) {
        throw new Exception("Connection failed: " . $connect->connect_error);
    }

    $sql = "SELECT 
        pe.id,
        pe.expense_number,
        pe.expense_date,
        pe.amount,
        pe.status,
        pec.name as category_name,
        po.order_number as production_order
    FROM production_expenses pe
    LEFT JOIN production_expense_categories pec ON pe.category_id = pec.id
    LEFT JOIN production_orders po ON pe.production_order_id = po.id
    ORDER BY pe.expense_date DESC";

    $result = $connect->query($sql);

    if (!$result) {
        throw new Exception("Query failed: " . $connect->error);
    }

    $output = array('data' => array());

    if($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $expenseId = $row['id'];
            
            // Status badge
            $status = '';
            if($row['status'] == 'draft') {
                $status = '<span class="label label-default">Draft</span>';
            } else if($row['status'] == 'pending') {
                $status = '<span class="label label-warning">Pending</span>';
            } else if($row['status'] == 'approved') {
                $status = '<span class="label label-success">Approved</span>';
            } else if($row['status'] == 'rejected') {
                $status = '<span class="label label-danger">Rejected</span>';
            } else if($row['status'] == 'paid') {
                $status = '<span class="label label-info">Paid</span>';
            }

            // Action buttons
            $actions = '
                <div class="btn-group">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Action <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a href="#" class="editExpenseBtn" id="'.$expenseId.'"><i class="fa fa-edit"></i> Edit</a></li>
                        <li><a href="#" class="removeExpenseBtn" id="'.$expenseId.'"><i class="fa fa-trash"></i> Remove</a></li>
                    </ul>
                </div>';

            $output['data'][] = array(
                $row['expense_number'],
                date('d M Y', strtotime($row['expense_date'])),
                $row['category_name'],
                number_format($row['amount'], 2),
                $row['production_order'],
                $status,
                $actions
            );
        }
    }

    echo json_encode($output);

} catch (Exception $e) {
    error_log("Error in fetchExpenseData.php: " . $e->getMessage());
    echo json_encode(array(
        'error' => true,
        'message' => $e->getMessage()
    ));
} finally {
    if (isset($connect)) {
        $connect->close();
    }
} 