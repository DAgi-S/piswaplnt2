<?php
// This is a simplified direct database adjustment script that bypasses the payment table
// to fix overpaid sales by directly updating the sales_orders table only

require_once 'core.php';
require_once 'db_connect.php';

// Set proper headers
header('Content-Type: text/html; charset=utf-8');

// Output styled page
echo '<!DOCTYPE html>
<html>
<head>
    <title>Simple Overpayment Fix</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .container { max-width: 800px; margin: 0 auto; }
        .alert { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; }
        .alert-info { color: #31708f; background-color: #d9edf7; border-color: #bce8f1; }
        .alert-success { color: #3c763d; background-color: #dff0d8; border-color: #d6e9c6; }
        .alert-warning { color: #8a6d3b; background-color: #fcf8e3; border-color: #faebcc; }
        .alert-danger { color: #a94442; background-color: #f2dede; border-color: #ebccd1; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 10px; text-align: left; }
        th { background-color: #f5f5f5; }
        button { padding: 10px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; border-radius: 4px; }
        button:hover { background: #45a049; }
        input, select { padding: 8px; width: 100%; box-sizing: border-box; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Simple Overpayment Fix</h1>';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sale_id'])) {
    $saleId = intval($_POST['sale_id']);
    $action = isset($_POST['action']) ? $_POST['action'] : 'view_only';
    
    try {
        // Get current sale information
        $query = "SELECT id, order_number, total_amount, paid_amount, balance FROM sales_orders WHERE id = ?";
        $stmt = $connect->prepare($query);
        $stmt->bind_param("i", $saleId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Sale ID $saleId not found");
        }
        
        $sale = $result->fetch_assoc();
        $currentPaidAmount = floatval($sale['paid_amount']);
        $totalAmount = floatval($sale['total_amount']);
        $currentBalance = floatval($sale['balance']);
        
        // Check if it's actually overpaid
        if ($currentBalance >= 0) {
            throw new Exception("This sale is not overpaid. Current balance: " . number_format($currentBalance, 2));
        }
        
        $overpayment = $currentPaidAmount - $totalAmount;
        
        if ($action === 'fix_directly') {
            // Begin transaction
            $connect->begin_transaction();
            
            // Simple fix: Just update the sales_orders table directly
            $query = "UPDATE sales_orders SET 
                      paid_amount = ?, 
                      balance = 0,
                      payment_status = 'paid',
                      updated_at = NOW()
                      WHERE id = ?";
            
            $stmt = $connect->prepare($query);
            $newPaidAmount = $totalAmount; // Set paid amount equal to total
            $stmt->bind_param("di", $newPaidAmount, $saleId);
            $stmt->execute();
            
            // Commit the transaction
            $connect->commit();
            
            echo '<div class="alert alert-success">
                <strong>Success!</strong> The overpayment has been fixed directly.
                <p>Original overpayment: ' . number_format($overpayment, 2) . '</p>
                <p>The sale is now marked as fully paid with a zero balance.</p>
                <p><strong>Note:</strong> This method updates only the sales_orders table without creating payment records.</p>
            </div>';
        } else {
            // Action is view only - don't make changes
            echo '<div class="alert alert-info">
                <p>You chose to view only. No changes were made.</p>
                <p>This sale has an overpayment of ' . number_format($overpayment, 2) . '</p>
            </div>';
        }
        
        // Show the updated sale info
        $query = "SELECT id, order_number, total_amount, paid_amount, balance, payment_status FROM sales_orders WHERE id = ?";
        $stmt = $connect->prepare($query);
        $stmt->bind_param("i", $saleId);
        $stmt->execute();
        $result = $stmt->get_result();
        $updatedSale = $result->fetch_assoc();
        
        echo '<h3>Updated Sale Information</h3>
            <table>
                <tr><th>Order Number</th><td>' . htmlspecialchars($updatedSale['order_number'] ?? '') . '</td></tr>
                <tr><th>Total Amount</th><td>' . number_format($updatedSale['total_amount'], 2) . '</td></tr>
                <tr><th>Paid Amount</th><td>' . number_format($updatedSale['paid_amount'], 2) . '</td></tr>
                <tr><th>Balance</th><td>' . number_format($updatedSale['balance'], 2) . '</td></tr>
                <tr><th>Payment Status</th><td>' . htmlspecialchars($updatedSale['payment_status'] ?? '') . '</td></tr>
            </table>';
    } catch (Exception $e) {
        // Rollback if needed
        if ($connect && $connect->ping()) {
            try {
                $connect->rollback();
            } catch (Exception $rollbackException) {
                // Silently handle if no transaction is active
            }
        }
        
        echo '<div class="alert alert-danger">
            <strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '
        </div>';
    }
    
    echo '<p><a href="?">Back to form</a></p>';
} else {
    // Show the form with overpaid sales
    $query = "SELECT id, order_number, total_amount, paid_amount, balance FROM sales_orders WHERE balance < 0 ORDER BY id DESC";
    $result = $connect->query($query);
    
    if ($result && $result->num_rows > 0) {
        echo '<div class="alert alert-warning">
            <strong>Warning:</strong> This is a simplified tool that only updates the sales_orders table. No payment records will be created.
        </div>
        
        <h2>Available Overpaid Sales</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Order Number</th>
                <th>Total</th>
                <th>Paid</th>
                <th>Balance</th>
                <th>Overpayment</th>
                <th>Action</th>
            </tr>';
        
        while ($row = $result->fetch_assoc()) {
            $overpayment = abs($row['balance']);
            echo '<tr>
                <td>' . $row['id'] . '</td>
                <td>' . htmlspecialchars($row['order_number'] ?? '') . '</td>
                <td>' . number_format($row['total_amount'], 2) . '</td>
                <td>' . number_format($row['paid_amount'], 2) . '</td>
                <td>' . number_format($row['balance'], 2) . '</td>
                <td>' . number_format($overpayment, 2) . '</td>
                <td>
                    <form method="post" action="" style="display: inline;">
                        <input type="hidden" name="sale_id" value="' . $row['id'] . '">
                        <input type="hidden" name="action" value="view_only">
                        <button type="submit">View</button>
                    </form>
                    &nbsp;
                    <form method="post" action="" style="display: inline;" onsubmit="return confirm(\'Are you sure you want to fix this overpayment? This will only update the order, no payment records will be created.\');">
                        <input type="hidden" name="sale_id" value="' . $row['id'] . '">
                        <input type="hidden" name="action" value="fix_directly">
                        <button type="submit" style="background-color: #f44336;">Fix</button>
                    </form>
                </td>
            </tr>';
        }
        
        echo '</table>';
    } else {
        echo '<div class="alert alert-info">No overpaid sales found.</div>';
    }
    
    echo '<h2>Manual Fix</h2>
    <p>Use this form to fix an overpayment for a specific sale ID:</p>
    
    <form method="post" action="">
        <div>
            <label for="sale_id">Sale ID:</label>
            <input type="number" name="sale_id" id="sale_id" required>
        </div>
        
        <div>
            <label for="action">Action:</label>
            <select name="action" id="action">
                <option value="view_only">View Only (No Changes)</option>
                <option value="fix_directly">Fix Directly</option>
            </select>
        </div>
        
        <div>
            <button type="submit">Submit</button>
        </div>
    </form>';
}

echo '</div></body></html>';
?> 