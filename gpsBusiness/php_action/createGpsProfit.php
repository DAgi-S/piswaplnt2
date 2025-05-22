<?php
require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());

if($_POST) {
    $date = $_POST['date'];
    $totalPurchase = $_POST['totalPurchase'];
    $totalSales = $_POST['totalSales'];
    $totalCredit = $_POST['totalCredit'];
    $totalExpenses = $_POST['totalExpenses'];
    $totalProfit = $_POST['totalProfit'];
    $comment = $_POST['comment'];

    $sql = "INSERT INTO gps_profit (date, total_purchase, total_sales, total_credit, total_expenses, total_profit, comment) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("sddddds", $date, $totalPurchase, $totalSales, $totalCredit, $totalExpenses, $totalProfit, $comment);

    if($stmt->execute()) {
        $valid['success'] = true;
        $valid['messages'] = "Profit record added successfully";
    } else {
        $valid['success'] = false;
        $valid['messages'] = "Error while adding the profit record";
    }

    $stmt->close();
    $connect->close();

    echo json_encode($valid);
} 