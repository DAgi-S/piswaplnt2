<?php
require_once 'core.php';
require_once 'db_connect.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>Accounts Table Check</h2>";

try {
    // Check if database connection is valid
    if (!$connect || $connect->connect_error) {
        throw new Exception("Database connection failed: " . ($connect ? $connect->connect_error : "Connection object is null"));
    }

    // Check if accounts table exists
    $checkTableQuery = "SHOW TABLES LIKE 'accounts'";
    $tableResult = $connect->query($checkTableQuery);
    
    if ($tableResult->num_rows > 0) {
        echo "<p style='color:green'>✓ Accounts table exists</p>";
        
        // Check table structure
        $describeQuery = "DESCRIBE accounts";
        $describeResult = $connect->query($describeQuery);
        
        if ($describeResult) {
            echo "<h3>Table Structure:</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            
            while ($row = $describeResult->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['Field'] . "</td>";
                echo "<td>" . $row['Type'] . "</td>";
                echo "<td>" . $row['Null'] . "</td>";
                echo "<td>" . $row['Key'] . "</td>";
                echo "<td>" . $row['Default'] . "</td>";
                echo "<td>" . $row['Extra'] . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
            
            // Check for required columns
            $requiredColumns = ['id', 'account_owner', 'account_platform', 'currency', 'status'];
            $missingColumns = [];
            
            $describeResult = $connect->query($describeQuery);
            $columns = [];
            while ($row = $describeResult->fetch_assoc()) {
                $columns[] = $row['Field'];
            }
            
            foreach ($requiredColumns as $col) {
                if (!in_array($col, $columns)) {
                    $missingColumns[] = $col;
                }
            }
            
            if (count($missingColumns) > 0) {
                echo "<p style='color:red'>✗ Missing required columns: " . implode(', ', $missingColumns) . "</p>";
            } else {
                echo "<p style='color:green'>✓ All required columns exist</p>";
            }
            
            // Check for data
            $dataQuery = "SELECT COUNT(*) as count FROM accounts";
            $dataResult = $connect->query($dataQuery);
            $dataRow = $dataResult->fetch_assoc();
            $count = $dataRow['count'];
            
            echo "<p>Total records: " . $count . "</p>";
            
            if ($count > 0) {
                echo "<h3>Sample Data:</h3>";
                $sampleQuery = "SELECT * FROM accounts LIMIT 5";
                $sampleResult = $connect->query($sampleQuery);
                
                echo "<table border='1' cellpadding='5'>";
                $firstRow = $sampleResult->fetch_assoc();
                if ($firstRow) {
                    echo "<tr>";
                    foreach (array_keys($firstRow) as $key) {
                        echo "<th>" . $key . "</th>";
                    }
                    echo "</tr>";
                    
                    // Output first row
                    echo "<tr>";
                    foreach ($firstRow as $value) {
                        echo "<td>" . $value . "</td>";
                    }
                    echo "</tr>";
                    
                    // Output remaining rows
                    while ($row = $sampleResult->fetch_assoc()) {
                        echo "<tr>";
                        foreach ($row as $value) {
                            echo "<td>" . $value . "</td>";
                        }
                        echo "</tr>";
                    }
                }
                echo "</table>";
            } else {
                echo "<p style='color:red'>✗ No data in accounts table</p>";
                
                // Create sample accounts
                echo "<h3>Creating Sample Account:</h3>";
                
                $createAccountQuery = "INSERT INTO accounts 
                    (account_owner, account_platform, currency, status) 
                    VALUES ('Default User', 'Cash Account', 'USD', 1)";
                
                if ($connect->query($createAccountQuery)) {
                    echo "<p style='color:green'>✓ Created sample account</p>";
                } else {
                    echo "<p style='color:red'>✗ Failed to create sample account: " . $connect->error . "</p>";
                }
            }
        } else {
            echo "<p style='color:red'>✗ Could not describe accounts table: " . $connect->error . "</p>";
        }
    } else {
        echo "<p style='color:red'>✗ Accounts table does not exist</p>";
        
        // Create accounts table
        echo "<h3>Creating Accounts Table:</h3>";
        
        $createTableQuery = "CREATE TABLE `accounts` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `account_owner` varchar(100) NOT NULL,
            `account_platform` varchar(100) NOT NULL,
            `account_number` varchar(100) DEFAULT NULL,
            `currency` varchar(10) NOT NULL DEFAULT 'USD',
            `status` tinyint(1) NOT NULL DEFAULT '1',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if ($connect->query($createTableQuery)) {
            echo "<p style='color:green'>✓ Created accounts table</p>";
            
            // Insert sample account
            $insertSampleQuery = "INSERT INTO accounts 
                (account_owner, account_platform, currency, status) 
                VALUES ('Default User', 'Cash Account', 'USD', 1)";
            
            if ($connect->query($insertSampleQuery)) {
                echo "<p style='color:green'>✓ Created sample account</p>";
            } else {
                echo "<p style='color:red'>✗ Failed to create sample account: " . $connect->error . "</p>";
            }
        } else {
            echo "<p style='color:red'>✗ Failed to create accounts table: " . $connect->error . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?> 