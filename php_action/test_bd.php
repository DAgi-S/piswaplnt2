<?php
// Test database connection and show server info
$host = isset($_GET['host']) ? $_GET['host'] : 'localhost';
$user = isset($_GET['user']) ? $_GET['user'] : 'lebawinet_ramauser';
$pass = isset($_GET['pass']) ? $_GET['pass'] : 'Dagi-0924';
$db   = isset($_GET['db'])   ? $_GET['db']   : 'lebawinet_rama';

header('Content-Type: text/plain');

// Server info
echo "Server Host: ".gethostname()."\n";
echo "Server IP: ".gethostbyname(gethostname())."\n";
echo "Domain: ".(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'N/A')."\n";
echo "PHP Version: ".phpversion()."\n";
echo "MySQLi extension: ".(extension_loaded('mysqli') ? 'enabled' : 'disabled')."\n";

// Try connection
$conn = @new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo "\n[ERROR] Database connection failed: ".$conn->connect_error."\n";
    exit(1);
} else {
    echo "\n[SUCCESS] Connected to database '$db' as user '$user'.\n";
}

// Check privileges
$res = $conn->query("SHOW GRANTS FOR CURRENT_USER()");
if ($res) {
    $user = '';
    $rows = [];
    while ($row = $res->fetch_array()) {
        $rows[] = $row[0];
    }
    if (count($rows) > 0 && preg_match('/GRANT.*ON.*TO `(.*?)`@`(.*?)`/i', $rows[0], $matches)) {
        $user = $matches[1] . '@' . $matches[2];
    } else {
        $user = 'CURRENT_USER';
    }
    echo "\nPrivileges for $user:\n";
    if (count($rows) > 0) {
        foreach ($rows as $priv) {
            echo $priv . "\n";
        }
    } else {
        echo "[INFO] No privileges found or insufficient privileges to view grants.\n";
    }
} else {
    echo "\n[INFO] Could not retrieve privileges: " . $conn->error . "\n";
}

// List databases
$res = $conn->query("SHOW DATABASES");
if ($res) {
    echo "\nDatabases:\n";
    while ($row = $res->fetch_array()) {
        echo "- ".$row[0]."\n";
    }
}

// List tables in current db
$res = $conn->query("SHOW TABLES");
if ($res) {
    $count = 0;
    echo "\nTables in $db:\n";
    while ($row = $res->fetch_array()) {
        echo "- ".$row[0]."\n";
        $count++;
    }
    if ($count === 0) {
        echo "[INFO] No tables found in database '$db'.\n";
    }
} else {
    echo "\n[INFO] Could not list tables: ".$conn->error."\n";
}

// List users in users table
$res = $conn->query("SELECT username, status FROM users");
if ($res) {
    $count = 0;
    echo "\nUsers in 'users' table:\n";
    while ($row = $res->fetch_assoc()) {
        echo "- Username: {$row['username']}, Status: {$row['status']}\n";
        $count++;
    }
    if ($count === 0) {
        echo "[INFO] No users found in 'users' table.\n";
    }
} else {
    echo "\n[INFO] Could not query users table: ".$conn->error."\n";
}

$conn->close(); 