<?php
require_once 'core.php';

function verifyUserPermissions() {
    global $connect;
    
    // Get current user's role ID from session
    $roleId = isset($_SESSION['roleId']) ? $_SESSION['roleId'] : null;
    $userId = isset($_SESSION['userId']) ? $_SESSION['userId'] : null;
    
    if (!$roleId || !$userId) {
        return ["error" => true, "message" => "User not logged in"];
    }
    
    // Check user's permissions
    $sql = "SELECT p.permission_name, p.description 
            FROM permissions p 
            JOIN role_permissions rp ON p.permission_id = rp.permission_id 
            WHERE rp.role_id = ?";
            
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $roleId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $permissions = [];
    while ($row = $result->fetch_assoc()) {
        $permissions[] = $row;
    }
    
    return [
        "userId" => $userId,
        "roleId" => $roleId,
        "permissions" => $permissions
    ];
}

// Run the verification
$result = verifyUserPermissions();
echo json_encode($result);
?> 