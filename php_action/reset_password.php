<?php
// Usage: reset_password.php?user=admin&new=yournewpassword
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'pistocklnt1march';

header('Content-Type: text/plain');

$username = isset($_GET['user']) ? $_GET['user'] : '';
$newpass  = isset($_GET['new']) ? $_GET['new'] : '';

if (!$username || !$newpass) {
    echo "Usage: reset_password.php?user=USERNAME&new=NEWPASSWORD\n";
    exit(1);
}

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo "[ERROR] Database connection failed: {$conn->connect_error}\n";
    exit(1);
}

$hash = password_hash($newpass, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE users SET password=? WHERE username=?");
if (!$stmt) {
    echo "[ERROR] Prepare failed: {$conn->error}\n";
    exit(1);
}
$stmt->bind_param('ss', $hash, $username);
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo "[SUCCESS] Password for user '$username' has been reset.\n";
    } else {
        echo "[INFO] No user updated. Check username.\n";
    }
} else {
    echo "[ERROR] Execute failed: {$stmt->error}\n";
}
$stmt->close();
$conn->close(); 