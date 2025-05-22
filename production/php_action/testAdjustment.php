<?php
// This is a test script to manually test the adjustment functionality

// Include necessary files
require_once 'core.php';
require_once 'db_connect.php';

// Output header
echo "<!DOCTYPE html>
<html>
<head>
    <title>Test Payment Adjustment</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>Test Payment Adjustment</h1>";

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>Submitting Test Adjustment</h2>";
    
    // Get values from form
    $saleId = $_POST['sale_id'];
    $adjustmentAmount = $_POST['adjustment_amount'];
    $adjustmentType = $_POST['adjustment_type'];
    $notes = $_POST['notes'];
    
    // Create test data
    $testData = [
        'sale_id' => $saleId,
        'adjustment_amount' => $adjustmentAmount,
        'adjustment_type' => $adjustmentType,
        'notes' => $notes
    ];
    
    echo "<p>Test data:</p>";
    echo "<pre>" . print_r($testData, true) . "</pre>";
    
    // Make POST request to the adjustment script
    $ch = curl_init('http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/adjustPaymentOverage.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $testData);
    
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    // Display response
    echo "<h3>Response:</h3>";
    if ($error) {
        echo "<p class='error'>cURL Error: " . htmlspecialchars($error) . "</p>";
    } else {
        echo "<p>HTTP Status: " . $info['http_code'] . "</p>";
        echo "<p>Content Type: " . $info['content_type'] . "</p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
        
        // Try to decode JSON
        $decoded = json_decode($response, true);
        if ($decoded === null) {
            echo "<p class='error'>JSON Error: " . json_last_error_msg() . "</p>";
        } else {
            echo "<h3>Decoded JSON:</h3>";
            echo "<pre>" . print_r($decoded, true) . "</pre>";
            
            if ($decoded['success']) {
                echo "<p class='success'>Adjustment was successful!</p>";
            } else {
                echo "<p class='error'>Adjustment failed: " . htmlspecialchars($decoded['message']) . "</p>";
            }
        }
    }
} else {
    // Fetch sales with negative balance (overpaid)
    $query = "SELECT id, order_number, total_amount, paid_amount, balance FROM sales_orders WHERE balance < 0 ORDER BY id DESC";
    $result = $connect->query($query);
    
    if ($result->num_rows > 0) {
        echo "<h2>Available Overpaid Sales</h2>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Order Number</th><th>Total</th><th>Paid</th><th>Balance</th><th>Overpayment</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            $overpayment = abs($row['balance']);
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['order_number'] . "</td>";
            echo "<td>" . number_format($row['total_amount'], 2) . "</td>";
            echo "<td>" . number_format($row['paid_amount'], 2) . "</td>";
            echo "<td>" . number_format($row['balance'], 2) . "</td>";
            echo "<td>" . number_format($overpayment, 2) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No overpaid sales found.</p>";
    }
    
    // Display test form
    echo "
    <h2>Test Form</h2>
    <form method='post' action=''>
        <p>
            <label for='sale_id'>Sale ID:</label><br>
            <input type='number' name='sale_id' id='sale_id' required>
        </p>
        <p>
            <label for='adjustment_amount'>Adjustment Amount:</label><br>
            <input type='number' step='0.01' name='adjustment_amount' id='adjustment_amount' required>
        </p>
        <p>
            <label for='adjustment_type'>Adjustment Type:</label><br>
            <select name='adjustment_type' id='adjustment_type'>
                <option value='refund'>Refund to Client</option>
                <option value='adjustment'>Balance Adjustment</option>
            </select>
        </p>
        <p>
            <label for='notes'>Notes:</label><br>
            <textarea name='notes' id='notes' rows='3' cols='40'>Test adjustment</textarea>
        </p>
        <p>
            <button type='submit'>Test Adjustment</button>
        </p>
    </form>";
}

echo "</body></html>";
?> 