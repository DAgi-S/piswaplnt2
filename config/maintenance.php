<?php
// Maintenance Mode Settings
$maintenance_mode = false;
$maintenance_message = 'System is under maintenance. Please try again later.';

// Function to check if system is in maintenance mode
function isMaintenanceMode() {
    global $maintenance_mode;
    return $maintenance_mode;
}

// Function to get maintenance message
function getMaintenanceMessage() {
    global $maintenance_message;
    return $maintenance_message;
}
?> 