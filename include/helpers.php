<?php
/**
 * Helper functions for role-based access control
 * 
 * Usage example:
 * if (check_access('dashboard.view')) {
 *     // allow access
 * } else {
 *     // deny access
 * }
 */

/**
 * Checks if the current user has a specific permission
 * 
 * @param string $permission The name of the permission to check
 * @return bool True if user has permission, false otherwise
 */
function check_access($permission) {
    global $conn;
    return has_permission($_SESSION['user_id'], $permission, $conn);
} 