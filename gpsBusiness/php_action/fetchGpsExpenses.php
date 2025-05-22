<?php
require_once '../../php_action/core.php';

// Initialize response array
$response = array(
    'data' => array()
);

// Fetch expenses data
$sql = "SELECT id, name, expense_type, date, unit_price, quantity, total, created_at 
        FROM gps_expenses 
        ORDER BY date DESC";

$result = $connect->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $actionButtons = '
            <div class="btn-group">
                <button class="btn btn-primary btn-sm editExpense" data-id="'.$row['id'].'"><i class="fas fa-edit"></i></button>
                <button class="btn btn-danger btn-sm removeExpense" data-id="'.$row['id'].'"><i class="fas fa-trash"></i></button>
            </div>';

        $response['data'][] = array(
            date('Y-m-d', strtotime($row['date'])),
            $row['name'],
            $row['expense_type'],
            number_format($row['unit_price'], 2),
            $row['quantity'],
            number_format($row['total'], 2),
            $actionButtons
        );
    }
}

echo json_encode($response); 