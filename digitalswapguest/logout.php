<?php
require_once 'includes/core.php';

// Store the guest_id before we clear the session
$guest_id = $_SESSION['guest_id'] ?? null;

// Log the logout activity if we have a guest_id
if ($guest_id) {
    try {
        if (!logActivity('logout', 'User logged out')) {
            error_log("Failed to log logout activity for guest_id: " . $guest_id);
        }
    } catch (Exception $e) {
        error_log("Exception while logging logout: " . $e->getMessage());
    }
}

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: ' . SITE_URL . '/index.php');
exit();
?> 