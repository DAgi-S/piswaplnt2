<?php
require_once 'php_action/db_connect.php';

// Read the SQL file
$sql = file_get_contents('sample_data.sql');

// Execute multi query
if (mysqli_multi_query($connect, $sql)) {
    do {
        // Store first result set
        if ($result = mysqli_store_result($connect)) {
            mysqli_free_result($result);
        }
    } while (mysqli_next_result($connect));
    
    echo "Sample data imported successfully!";
} else {
    echo "Error importing sample data: " . mysqli_error($connect);
}

mysqli_close($connect); 