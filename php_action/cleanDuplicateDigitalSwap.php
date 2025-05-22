<?php
require_once 'core.php';
require_once 'db_connect.php';

// Start transaction
$connect->begin_transaction();

try {
    // Find duplicate records based on all relevant fields except ID
    $sql = "SELECT 
        transaction_date, type, name, platform, amount, comment,
        GROUP_CONCAT(id ORDER BY id DESC) as ids,
        COUNT(*) as cnt
    FROM digitalswap
    GROUP BY 
        transaction_date, type, name, platform, amount, comment
    HAVING COUNT(*) > 1";

    $result = $connect->query($sql);
    
    if (!$result) {
        throw new Exception("Error finding duplicates: " . $connect->error);
    }

    $duplicatesRemoved = 0;
    
    while ($row = $result->fetch_assoc()) {
        $ids = explode(',', $row['ids']);
        // Keep the first ID (latest) and remove others
        array_shift($ids); // Remove first ID from deletion list
        
        if (!empty($ids)) {
            $deleteIds = implode(',', $ids);
            $deleteSql = "DELETE FROM digitalswap WHERE id IN ($deleteIds)";
            
            if (!$connect->query($deleteSql)) {
                throw new Exception("Error removing duplicates: " . $connect->error);
            }
            
            $duplicatesRemoved += count($ids);
        }
    }

    // If everything is successful, commit the transaction
    $connect->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully cleaned $duplicatesRemoved duplicate entries",
        'duplicates_removed' => $duplicatesRemoved
    ]);

} catch (Exception $e) {
    // Rollback on error
    $connect->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 