<?php
require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());

if($_POST) {
    $profitId = $_POST['profitId'];
    $date = $_POST['editDate'];
    $totalPurchase = $_POST['editTotalPurchase'];
    $totalSales = $_POST['editTotalSales'];
    $totalCredit = $_POST['editTotalCredit'];
    $totalExpenses = $_POST['editTotalExpenses'];
    $totalProfit = $_POST['editTotalProfit'];
    $comment = $_POST['editComment'];
    
    $sql = "UPDATE gps_profit SET 
        date = ?,
        total_purchase = ?,
        total_sales = ?,
        total_credit = ?,
        total_expenses = ?,
        total_profit = ?,
        comment = ?
        WHERE id = ?";
    
    $stmt = $connect->prepare($sql);
    $stmt->bind_param(
        "sdddddssi",
        $date,
        $totalPurchase,
        $totalSales,
        $totalCredit,
        $totalExpenses,
        $totalProfit,
        $comment,
        $profitId
    );
    
    if($stmt->execute()) {
        $valid['success'] = true;
        $valid['messages'] = "Profit record updated successfully";
    } else {
        $valid['success'] = false;
        $valid['messages'] = "Error while updating the profit record";
    }
    
    $stmt->close();
    $connect->close();
    
    echo json_encode($valid);
} 