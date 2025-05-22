<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Connect to database
$localhost = "localhost";
$username = "root";
$password = "";
$dbname = "pistocklnt";
$connect = new mysqli($localhost, $username, $password, $dbname);

if ($connect->connect_error) {
    die("Connection failed: " . $connect->connect_error);
}

echo "<h2>Session Information:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if(isset($_SESSION['userId'])) {
    echo "<h2>User Database Information:</h2>";
    $userId = $_SESSION['userId'];
    
    // Get user details
    $sql = "SELECT * FROM users WHERE user_id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($row = $result->fetch_assoc()) {
        echo "User found in database:<br>";
        echo "User ID: " . $row['user_id'] . "<br>";
        echo "Username: " . $row['username'] . "<br>";
        echo "Role ID: " . $row['role_id'] . "<br>";
        echo "Status: " . $row['status'] . "<br>";
    } else {
        echo "ERROR: User ID {$userId} not found in database.<br>";
    }
} else {
    echo "ERROR: No user ID in session - you are not logged in.<br>";
}

echo "<h2>Database Tables Check:</h2>";
$tables = array('users', 'roles', 'role_permissions');
foreach($tables as $table) {
    $result = $connect->query("SHOW TABLES LIKE '$table'");
    echo "$table table exists: " . ($result->num_rows > 0 ? "Yes" : "No") . "<br>";
    
    if($result->num_rows > 0) {
        $result = $connect->query("DESCRIBE $table");
        echo "Columns in $table table:<br>";
        while($row = $result->fetch_assoc()) {
            echo "- " . $row['Field'] . " (" . $row['Type'] . ")<br>";
        }
    }
}

echo "<h2>All Users in Database:</h2>";
$result = $connect->query("SELECT user_id, username, role_id, status FROM users");
if($result) {
    while($row = $result->fetch_assoc()) {
        echo "User ID: " . $row['user_id'] . 
             ", Username: " . $row['username'] . 
             ", Role ID: " . $row['role_id'] . 
             ", Status: " . $row['status'] . "<br>";
    }
} else {
    echo "Error querying users table: " . $connect->error;
}

$connect->close();
?> 