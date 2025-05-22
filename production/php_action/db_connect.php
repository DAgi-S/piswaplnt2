<?php
// Include configuration file
require_once 'config.php';

// db connection
try {
    $connect = new mysqli($localhost, $username, $password, $dbname);

    if($connect->connect_error) {
        throw new Exception("Connection Failed: " . $connect->connect_error);
    }

    // Set charset to handle special characters correctly
    $connect->set_charset("utf8mb4");

} catch(Exception $e) {
    // Log the error
    error_log("Database Connection Error: " . $e->getMessage());
    
    // Show user-friendly message
    die("
        <div style='margin: 50px; padding: 20px; border: 1px solid #dc3545; border-radius: 5px; background-color: #f8d7da; color: #721c24;'>
            <h3 style='margin-top: 0;'>Database Connection Error</h3>
            <p>Unable to connect to the database. Please check if:</p>
            <ul>
                <li>The database server (MySQL) is running</li>
                <li>The database exists</li>
                <li>The database credentials are correct</li>
            </ul>
            <p>Please contact your system administrator for assistance.</p>
        </div>
    ");
} 