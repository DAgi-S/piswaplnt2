<?php
require_once 'core.php';

// Delete sessions older than 30 minutes
$sql = "DELETE FROM sessions WHERE last_activity < (NOW() - INTERVAL 30 MINUTE)";

try {
    if ($connect->query($sql)) {
        error_log("Successfully cleaned up expired sessions");
    } else {
        error_log("Error cleaning up sessions: " . $connect->error);
    }
} catch (Exception $e) {
    error_log("Exception during session cleanup: " . $e->getMessage());
}
?> 