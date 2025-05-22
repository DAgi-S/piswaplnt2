<?php
require_once 'core.php';

// Function to check and fix permissions
function checkAndFixPermissions() {
    global $connect;
    
    // Define required production permissions
    $requiredPermissions = [
        [
            'name' => 'view_product',
            'description' => 'Can view products',
            'module' => 'Product'
        ],
        [
            'name' => 'create_product',
            'description' => 'Can create new products',
            'module' => 'Product'
        ],
        [
            'name' => 'edit_product',
            'description' => 'Can edit existing products',
            'module' => 'Product'
        ],
        [
            'name' => 'delete_product',
            'description' => 'Can delete products',
            'module' => 'Product'
        ],
        [
            'name' => 'view_production',
            'description' => 'Can view production module',
            'module' => 'Product'
        ],
        [
            'name' => 'manage_production',
            'description' => 'Can manage production',
            'module' => 'Product'
        ]
    ];
    
    echo "Current Permissions in Database:\n";
    $sql = "SELECT * FROM permissions WHERE module = 'Product' OR permission_name LIKE '%product%'";
    $result = $connect->query($sql);
    
    echo "\nExisting Permissions:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['permission_name']} ({$row['module']}): {$row['description']}\n";
    }
    
    echo "\nChecking and Adding Missing Permissions:\n";
    foreach ($requiredPermissions as $perm) {
        $sql = "SELECT permission_id FROM permissions WHERE permission_name = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("s", $perm['name']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            // Add missing permission
            $sql = "INSERT INTO permissions (permission_name, description, module) VALUES (?, ?, ?)";
            $stmt = $connect->prepare($sql);
            $stmt->bind_param("sss", $perm['name'], $perm['description'], $perm['module']);
            $stmt->execute();
            $permissionId = $connect->insert_id;
            
            // Assign to role ID 2
            $sql = "INSERT INTO role_permissions (role_id, permission_id) VALUES (2, ?)";
            $stmt = $connect->prepare($sql);
            $stmt->bind_param("i", $permissionId);
            $stmt->execute();
            
            echo "Added permission: {$perm['name']}\n";
        } else {
            $permissionId = $result->fetch_assoc()['permission_id'];
            
            // Check if role 2 has this permission
            $sql = "SELECT * FROM role_permissions WHERE role_id = 2 AND permission_id = ?";
            $stmt = $connect->prepare($sql);
            $stmt->bind_param("i", $permissionId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 0) {
                // Assign to role ID 2
                $sql = "INSERT INTO role_permissions (role_id, permission_id) VALUES (2, ?)";
                $stmt = $connect->prepare($sql);
                $stmt->bind_param("i", $permissionId);
                $stmt->execute();
                echo "Assigned existing permission to role 2: {$perm['name']}\n";
            } else {
                echo "Permission already exists and is assigned: {$perm['name']}\n";
            }
        }
    }
    
    echo "\nFinal Permissions Check:\n";
    $sql = "SELECT p.*, rp.role_id 
            FROM permissions p 
            LEFT JOIN role_permissions rp ON p.permission_id = rp.permission_id AND rp.role_id = 2 
            WHERE p.module = 'Product' OR p.permission_name LIKE '%product%'";
    $result = $connect->query($sql);
    
    while ($row = $result->fetch_assoc()) {
        $status = $row['role_id'] == 2 ? "Assigned to role 2" : "Not assigned to role 2";
        echo "- {$row['permission_name']} ({$row['module']}): {$status}\n";
    }
}

// Run the check and fix
checkAndFixPermissions();
?> 