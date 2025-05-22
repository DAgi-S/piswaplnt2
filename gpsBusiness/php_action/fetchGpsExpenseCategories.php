<?php
require_once '../../php_action/core.php';

// Initialize response array
$response = array(
    'data' => array()
);

// Fetch expense categories data
$sql = "SELECT id, name, expense_type, description, unit_price, created_at 
        FROM gps_expense_categories 
        ORDER BY name ASC";

$result = $connect->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $actionButtons = '
            <div class="btn-group">
                <button class="btn btn-primary btn-sm editCategory" data-id="'.$row['id'].'"><i class="fas fa-edit"></i></button>
                <button class="btn btn-danger btn-sm removeCategory" data-id="'.$row['id'].'"><i class="fas fa-trash"></i></button>
            </div>';

        $response['data'][] = array(
            $row['name'],
            $row['expense_type'],
            $row['description'],
            number_format($row['unit_price'], 2),
            $actionButtons
        );
    }
}

echo json_encode($response); 