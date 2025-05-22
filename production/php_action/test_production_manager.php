<?php
/**
 * Test script for ProductionManager
 * 
 * This script tests the ProductionManager class implementation
 * without affecting the existing system
 */

// Include necessary files
require_once 'db_connect.php';
require_once 'classes/ProductionManager.php';

// Set error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check if this is called via browser or command line
$isCli = php_sapi_name() === 'cli';
$newline = $isCli ? PHP_EOL : '<br>';

/**
 * Helper function to output formatted messages
 * 
 * @param string $message The message to output
 * @param string $type The type of message (success, error, info)
 */
function output($message, $type = 'info') {
    global $isCli, $newline;
    
    $prefix = '';
    if (!$isCli) {
        switch ($type) {
            case 'success':
                $prefix = '<span style="color: green; font-weight: bold;">✓ </span>';
                break;
            case 'error':
                $prefix = '<span style="color: red; font-weight: bold;">✗ </span>';
                break;
            case 'info':
                $prefix = '<span style="color: blue; font-weight: bold;">ℹ </span>';
                break;
        }
    } else {
        switch ($type) {
            case 'success':
                $prefix = "\033[32m✓ \033[0m";
                break;
            case 'error':
                $prefix = "\033[31m✗ \033[0m";
                break;
            case 'info':
                $prefix = "\033[34mℹ \033[0m";
                break;
        }
    }
    
    echo $prefix . $message . $newline;
}

// Initialize the ProductionManager with the database connection
try {
    $productionManager = new ProductionManager($connect);
    output("ProductionManager initialized successfully", "success");
} catch (Exception $e) {
    output("Failed to initialize ProductionManager: " . $e->getMessage(), "error");
    exit;
}

// Function to test calculateProductionRequirements
function testCalculateProductionRequirements($productionManager, $orderNumber, $productId, $targetQuantity) {
    output("Testing calculateProductionRequirements with order: $orderNumber, product: $productId, quantity: $targetQuantity", "info");
    
    try {
        $result = $productionManager->calculateProductionRequirements($orderNumber, $productId, $targetQuantity);
        
        if ($result['status']) {
            output("Success: " . $result['message'], "success");
            
            // Display details of the calculated requirements
            if (isset($result['data']) && isset($result['data']['required_materials']) && !empty($result['data']['required_materials'])) {
                output("Material requirements calculated:", "info");
                echo "<div style='margin-left: 20px; padding: 10px; background-color: #f5f5f5; border-left: 4px solid #4CAF50;'>";
                
                // Product information
                $productId = isset($result['data']['product']['id']) ? $result['data']['product']['id'] : 'N/A';
                $productName = isset($result['data']['product']['product_name']) ? 
                    $result['data']['product']['product_name'] : 
                    (isset($result['data']['product']['name']) ? $result['data']['product']['name'] : 'Unknown');
                
                echo "<h3>Product: " . $productName . " (ID: " . $productId . ")</h3>";
                echo "<h3>Target Quantity: " . $result['data']['target_quantity'] . "</h3>";
                echo "<h3>Total Cost: " . number_format($result['data']['total_cost'], 2) . "</h3>";
                echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
                echo "<thead style='background-color: #f2f2f2;'><tr>";
                echo "<th>Material</th><th>Qty Per Unit</th><th>Wastage %</th><th>Required Qty</th><th>Unit</th><th>Unit Cost</th><th>Total Cost</th><th>Debug</th>";
                echo "</tr></thead><tbody>";
                
                foreach ($result['data']['required_materials'] as $material) {
                    echo "<tr>";
                    $materialName = isset($material['material_name']) ? $material['material_name'] : 'Unknown Material';
                    $materialId = isset($material['material_id']) ? $material['material_id'] : 'N/A';
                    $qtyPerUnit = isset($material['quantity_per_unit']) ? number_format($material['quantity_per_unit'], 2) : '0.00';
                    $wastagePercent = isset($material['wastage_percent']) ? number_format($material['wastage_percent'], 2) : '0.00';
                    $requiredQty = isset($material['required_quantity']) ? number_format($material['required_quantity'], 2) : '0.00';
                    $unit = isset($material['unit']) ? $material['unit'] : '';
                    $unitCost = isset($material['unit_cost']) ? number_format($material['unit_cost'], 2) : '0.00';
                    $totalCost = isset($material['total_cost']) ? number_format($material['total_cost'], 2) : '0.00';
                    
                    echo "<td>" . $materialName . " (ID: " . $materialId . ")</td>";
                    echo "<td align='right'>" . $qtyPerUnit . "</td>";
                    echo "<td align='right'>" . $wastagePercent . "%</td>";
                    echo "<td align='right'>" . $requiredQty . "</td>";
                    echo "<td>" . $unit . "</td>";
                    echo "<td align='right'>" . $unitCost . "</td>";
                    echo "<td align='right'>" . $totalCost . "</td>";
                    
                    // Display debug info
                    echo "<td style='font-size: 0.8em;'>";
                    if (isset($material['debug'])) {
                        echo "Cost isset: " . ($material['debug']['unit_cost_isset'] ? 'Yes' : 'No') . "<br>";
                        echo "Cost value: " . $material['debug']['unit_cost_value'] . "<br>";
                        echo "Required qty: " . $material['debug']['required_qty'] . "<br>";
                        echo "Calc cost: " . $material['debug']['calculated_cost'];
                    } else {
                        echo "No debug info";
                    }
                    echo "</td>";
                    
                    echo "</tr>";
                }
                
                echo "</tbody></table>";
                echo "</div>";
            } else {
                output("No material requirements were returned in the result", "error");
            }
        } else {
            output("Failed: " . $result['message'], "error");
        }
    } catch (Exception $e) {
        output("Exception: " . $e->getMessage(), "error");
    }
}

// Get test data - To run the test, we need a valid production order number, product ID, and quantity
// You can modify these values based on your database
$testOrderNumber = isset($_GET['order']) ? $_GET['order'] : 'PO-test';
$testProductId = isset($_GET['product']) ? intval($_GET['product']) : 1; // Use a valid product ID
$testQuantity = isset($_GET['quantity']) ? floatval($_GET['quantity']) : 10;

// Check if we should run the test
$runTest = isset($_GET['run_test']) && $_GET['run_test'] === '1';

if (!$isCli) {
    // HTML form for setting test parameters
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>ProductionManager Test</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
            h1 { color: #333; }
            form { margin-bottom: 20px; padding: 15px; background: #f5f5f5; border-radius: 5px; }
            label { display: block; margin-bottom: 5px; font-weight: bold; }
            input[type="text"], input[type="number"] { width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px; }
            input[type="submit"] { background: #4CAF50; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; }
            input[type="submit"]:hover { background: #45a049; }
            .results { margin-top: 20px; padding: 15px; background: #f9f9f9; border-radius: 5px; }
            .note { margin-top: 20px; padding: 10px; background: #ffffd8; border-radius: 5px; border-left: 4px solid #ffd600; }
        </style>
    </head>
    <body>
        <h1>ProductionManager Test</h1>
        
        <form method="GET">
            <div>
                <label for="order">Production Order Number:</label>
                <input type="text" id="order" name="order" value="' . htmlspecialchars($testOrderNumber) . '">
            </div>
            <div>
                <label for="product">Product ID:</label>
                <input type="number" id="product" name="product" value="' . htmlspecialchars($testProductId) . '">
            </div>
            <div>
                <label for="quantity">Target Quantity:</label>
                <input type="number" id="quantity" name="quantity" step="0.01" value="' . htmlspecialchars($testQuantity) . '">
            </div>
            <input type="hidden" name="run_test" value="1">
            <input type="submit" value="Run Test">
        </form>
        
        <div class="note">
            <strong>Note:</strong> This test script allows you to test the new PHP implementation without affecting the existing system.
            Make sure to use valid test data that exists in your database.
        </div>
        
        <div class="results">
            <h2>Test Results</h2>';
}

// Run the test if requested
if ($runTest || $isCli) {
    // First, check if the order exists, if not, create it for testing
    try {
        $stmt = $connect->prepare("SELECT id FROM production_orders WHERE order_number = ?");
        
        if ($connect instanceof PDO) {
            $stmt->execute([$testOrderNumber]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt->bind_param("s", $testOrderNumber);
            $stmt->execute();
            $result = $stmt->get_result();
            $order = $result->fetch_assoc();
            $stmt->close();
        }
        
        if (!$order) {
            output("Creating test production order: $testOrderNumber", "info");
            
            // Create a test order for testing purposes
            if ($connect instanceof PDO) {
                $stmt = $connect->prepare("
                    INSERT INTO production_orders 
                    (order_number, product_id, target_quantity, completed_quantity, 
                     start_date, expected_completion_date, status, notes)
                    VALUES (?, ?, ?, 0, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'draft', 'Test order')
                ");
                $stmt->execute([$testOrderNumber, $testProductId, $testQuantity]);
            } else {
                $stmt = $connect->prepare("
                    INSERT INTO production_orders 
                    (order_number, product_id, target_quantity, completed_quantity, 
                     start_date, expected_completion_date, status, notes)
                    VALUES (?, ?, ?, 0, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'draft', 'Test order')
                ");
                $zero = 0;
                $status = 'draft';
                $notes = 'Test order';
                $today = date('Y-m-d');
                $endDate = date('Y-m-d', strtotime('+7 days'));
                $stmt->bind_param("sid", $testOrderNumber, $testProductId, $testQuantity);
                $stmt->execute();
                $stmt->close();
            }
            
            output("Test order created successfully", "success");
        } else {
            output("Using existing production order: $testOrderNumber", "info");
        }
        
        // Run the test
        testCalculateProductionRequirements($productionManager, $testOrderNumber, $testProductId, $testQuantity);
        
    } catch (Exception $e) {
        output("Error setting up test: " . $e->getMessage(), "error");
    }
}

if (!$isCli) {
    echo '</div></body></html>';
} 