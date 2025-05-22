<?php
require_once 'core.php';

// Function to check if permission exists
function checkProductionPermission() {
    global $connect;
    
    // Define all required production permissions
    $productionPermissions = [
        'view_production_orders' => 'Can view production orders list and details',
        'create_production_order' => 'Can create new production orders',
        'edit_production_order' => 'Can edit existing production orders',
        'delete_production_order' => 'Can delete production orders'
    ];
    
    $results = [];
    
    foreach ($productionPermissions as $permissionName => $description) {
        // Check if permission exists
        $sql = "SELECT permission_id FROM permissions WHERE permission_name = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("s", $permissionName);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            // Permission doesn't exist, create it
            $sql = "INSERT INTO permissions (permission_name, description, module) 
                    VALUES (?, ?, 'production')";
            $stmt = $connect->prepare($sql);
            $stmt->bind_param("ss", $permissionName, $description);
            $stmt->execute();
            
            // Get the inserted permission ID
            $permissionId = $connect->insert_id;
            
            // Assign permission to role ID 2
            $sql = "INSERT INTO role_permissions (role_id, permission_id) VALUES (2, ?)";
            $stmt = $connect->prepare($sql);
            $stmt->bind_param("i", $permissionId);
            $stmt->execute();
            
            $results[] = "Created and assigned permission: {$permissionName}";
        } else {
            // Permission exists, make sure role 2 has it
            $permissionId = $result->fetch_assoc()['permission_id'];
            
            // Check if role 2 already has this permission
            $sql = "SELECT * FROM role_permissions WHERE role_id = 2 AND permission_id = ?";
            $stmt = $connect->prepare($sql);
            $stmt->bind_param("i", $permissionId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 0) {
                // Add permission to role 2
                $sql = "INSERT INTO role_permissions (role_id, permission_id) VALUES (2, ?)";
                $stmt = $connect->prepare($sql);
                $stmt->bind_param("i", $permissionId);
                $stmt->execute();
                $results[] = "Assigned existing permission: {$permissionName}";
            } else {
                $results[] = "Permission already assigned: {$permissionName}";
            }
        }
    }
    
    return ["status" => "Success", "details" => $results];
}

// Run the check
$result = checkProductionPermission();
echo json_encode($result);
?> 