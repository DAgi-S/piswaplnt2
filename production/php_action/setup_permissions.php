<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$localhost = "localhost";
$username = "root";
$password = "";
$dbname = "pistocklnt";

$connect = new mysqli($localhost, $username, $password, $dbname);

if ($connect->connect_error) {
    die("Connection failed: " . $connect->connect_error);
}

echo "<h2>Setting up Permission System</h2>";

// 1. Create user_roles table
$sql = "CREATE TABLE IF NOT EXISTS user_roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if($connect->query($sql)) {
    echo "User roles table created successfully<br>";
} else {
    echo "Error creating user roles table: " . $connect->error . "<br>";
}

// 2. Create permissions table
$sql = "CREATE TABLE IF NOT EXISTS permissions (
    permission_id INT PRIMARY KEY AUTO_INCREMENT,
    permission_name VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if($connect->query($sql)) {
    echo "Permissions table created successfully<br>";
} else {
    echo "Error creating permissions table: " . $connect->error . "<br>";
}

// 3. Create role_permissions table
$sql = "CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT,
    permission_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES user_roles(role_id),
    FOREIGN KEY (permission_id) REFERENCES permissions(permission_id)
)";

if($connect->query($sql)) {
    echo "Role permissions table created successfully<br>";
} else {
    echo "Error creating role permissions table: " . $connect->error . "<br>";
}

// 4. Insert default roles
$roles = array(
    array(1, 'Super Admin', 'Has all system permissions'),
    array(2, 'Admin', 'System administrator with limited permissions'),
    array(3, 'User', 'Regular system user')
);

foreach($roles as $role) {
    $sql = "INSERT IGNORE INTO user_roles (role_id, role_name, description) VALUES (?, ?, ?)";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("iss", $role[0], $role[1], $role[2]);
    $stmt->execute();
}

echo "Default roles created<br>";

// 5. Insert default permissions
$permissions = array(
    array('view_user', 'Can view users'),
    array('create_user', 'Can create users'),
    array('edit_user', 'Can edit users'),
    array('delete_user', 'Can delete users'),
    array('view_role', 'Can view roles'),
    array('create_role', 'Can create roles'),
    array('edit_role', 'Can edit roles'),
    array('delete_role', 'Can delete roles')
);

foreach($permissions as $permission) {
    $sql = "INSERT IGNORE INTO permissions (permission_name, description) VALUES (?, ?)";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("ss", $permission[0], $permission[1]);
    $stmt->execute();
}

echo "Default permissions created<br>";

// 6. Assign all permissions to Super Admin (role_id = 1)
$sql = "SELECT permission_id FROM permissions";
$result = $connect->query($sql);

while($row = $result->fetch_assoc()) {
    $sql = "INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, ?)";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $row['permission_id']);
    $stmt->execute();
}

echo "Permissions assigned to Super Admin role<br>";

// 7. Update existing admin user to Super Admin role
$sql = "UPDATE users SET role_id = 1 WHERE username = 'admin'";
if($connect->query($sql)) {
    echo "Admin user updated to Super Admin role<br>";
} else {
    echo "Error updating admin user: " . $connect->error . "<br>";
}

$connect->close();

echo "<br>Setup complete. You can now log in with the admin account to access the user management page.";
?> 