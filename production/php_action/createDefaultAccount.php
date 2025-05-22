<?php
require_once 'core.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>Creating Default Account</h2>";

try {
    // Check if database connection is valid
    if (!$connect || $connect->connect_error) {
        throw new Exception("Database connection failed: " . ($connect ? $connect->connect_error : "Connection object is null"));
    }
    
    // Check if accounts table exists
    $checkTableQuery = "SHOW TABLES LIKE 'accounts'";
    $tableResult = $connect->query($checkTableQuery);
    
    if ($tableResult->num_rows == 0) {
        // Create accounts table
        echo "<p>Accounts table doesn't exist. Creating it...</p>";
        
        $createTableQuery = "CREATE TABLE IF NOT EXISTS `accounts` (
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
        
        if (!$connect->query($createTableQuery)) {
            throw new Exception("Failed to create accounts table: " . $connect->error);
        }
        
        echo "<p style='color:green'>✓ Accounts table created</p>";
    } else {
        echo "<p>Accounts table exists.</p>";
    }
    
    // Check if there are any accounts
    $countQuery = "SELECT COUNT(*) as count FROM accounts WHERE status = 1";
    $countResult = $connect->query($countQuery);
    $countRow = $countResult->fetch_assoc();
    $accountCount = $countRow['count'];
    
    echo "<p>Found {$accountCount} active accounts.</p>";
    
    if ($accountCount == 0) {
        // No accounts, create default ones
        echo "<p>No accounts found. Creating default accounts...</p>";
        
        $defaultAccounts = [
            ["Default", "Cash Account", "USD"],
            ["Default", "Bank Account", "USD"],
            ["Default", "PayPal", "USD"]
        ];
        
        $insertQuery = "INSERT INTO accounts (account_owner, account_platform, currency, status) VALUES (?, ?, ?, 1)";
        $stmt = $connect->prepare($insertQuery);
        
        if (!$stmt) {
            throw new Exception("Failed to prepare insert statement: " . $connect->error);
        }
        
        $stmt->bind_param("sss", $owner, $platform, $currency);
        
        foreach ($defaultAccounts as $account) {
            $owner = $account[0];
            $platform = $account[1];
            $currency = $account[2];
            
            if (!$stmt->execute()) {
                echo "<p style='color:red'>✗ Failed to create account '{$platform}': " . $stmt->error . "</p>";
            } else {
                echo "<p style='color:green'>✓ Created account '{$platform}'</p>";
            }
        }
        
        echo "<p style='color:green'>Default accounts created successfully!</p>";
    } else {
        echo "<p style='color:green'>Accounts exist - no need to create defaults.</p>";
    }
    
    // Show existing accounts
    $accountsQuery = "SELECT id, account_owner, account_platform, currency, status FROM accounts ORDER BY id";
    $accountsResult = $connect->query($accountsQuery);
    
    echo "<h3>Existing Accounts:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Owner</th><th>Platform</th><th>Currency</th><th>Status</th></tr>";
    
    while ($row = $accountsResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['account_owner'] . "</td>";
        echo "<td>" . $row['account_platform'] . "</td>";
        echo "<td>" . $row['currency'] . "</td>";
        echo "<td>" . ($row['status'] ? 'Active' : 'Inactive') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<p><a href='fetchAccountsForSales.php' target='_blank'>Test Fetch Accounts API</a></p>";
    echo "<p><a href='fetchAccounts.php' target='_blank'>Test Fallback API</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?> 