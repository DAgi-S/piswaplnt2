<?php
// Don't start session here since core.php handles it
// if (session_status() === PHP_SESSION_NONE) {
//     session_start();
// }

// Function to check if user is logged in
function requireLogin() {
    if (!isset($_SESSION['userId'])) {
        header('location: login.php');
        exit();
    }
}

// Function to check if session is active
function isSessionActive() {
    return isset($_SESSION['userId']) && !empty($_SESSION['userId']);
}

// Function to get current user ID
function getCurrentUserId() {
    return isset($_SESSION['userId']) ? $_SESSION['userId'] : null;
}

// Function to get user role
function getUserRole() {
    return isset($_SESSION['roleId']) ? $_SESSION['roleId'] : null;
}

// Function to check if user has specific role
function hasRole($roleId) {
    return isset($_SESSION['roleId']) && $_SESSION['roleId'] === $roleId;
}

// Function to get user session data
function getSessionData() {
    return [
        'user_id' => getCurrentUserId(),
        'username' => isset($_SESSION['userName']) ? $_SESSION['userName'] : null,
        'role_id' => getUserRole(),
        'last_activity' => isset($_SESSION['last_activity']) ? $_SESSION['last_activity'] : null,
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ];
}

// Update last activity only if session exists
if (isset($_SESSION['userId'])) {
    $_SESSION['last_activity'] = time();
} 