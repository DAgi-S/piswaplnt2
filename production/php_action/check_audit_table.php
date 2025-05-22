<?php
require_once 'core.php';

// Function to check audit table structure
function checkAuditTable() {
    global $connect;
    
    echo "Checking system_audit_trails table structure:\n";
    
    $sql = "DESCRIBE system_audit_trails";
    $result = $connect->query($sql);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "Column: {$row['Field']}, Type: {$row['Type']}, Null: {$row['Null']}, Key: {$row['Key']}, Default: {$row['Default']}\n";
        }
    } else {
        echo "Error checking table structure: " . $connect->error;
    }
}

// Run the check
checkAuditTable();
?> 