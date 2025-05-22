<?php
function checkPagePermission($requiredPermission) {
    // Super Admin (role_id = 2) has all permissions
    if ($_SESSION['roleId'] === 2) {
        return true;
    }

    // Check if user has the required permission
    if (!hasPermission($requiredPermission)) {
        header('Location: access_denied.php');
        exit();
    }
    
    return true;
} 