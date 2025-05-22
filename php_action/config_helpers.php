<?php
// Helper functions for system configuration

/**
 * Updates a configuration file with new settings
 */
function updateConfigFile($file, $config) {
    try {
        // Validate file path
        $file = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . basename($file);
        if (!is_writable($file)) {
            throw new Exception("Configuration file is not writable");
        }

        // Create backup
        $backupFile = $file . '.bak.' . date('Y-m-d-H-i-s');
        if (!copy($file, $backupFile)) {
            throw new Exception("Failed to create backup of configuration file");
        }

        // Prepare content
        $content = "<?php\n// Last updated: " . date('Y-m-d H:i:s') . "\n\n";
        foreach ($config as $key => $value) {
            // Sanitize the value
            $value = str_replace(["'", "\\"], ["\'", "\\\\"], $value);
            $content .= "define('" . strtoupper($key) . "', '" . $value . "');\n";
        }

        // Write to temporary file first
        $tempFile = $file . '.tmp';
        if (file_put_contents($tempFile, $content) === false) {
            throw new Exception("Failed to write to temporary configuration file");
        }

        // Verify the temporary file
        if (!is_readable($tempFile)) {
            throw new Exception("Cannot read temporary configuration file");
        }

        // Rename temporary file to actual file
        if (!rename($tempFile, $file)) {
            unlink($tempFile);
            throw new Exception("Failed to update configuration file");
        }

        return true;
    } catch (Exception $e) {
        error_log("Error updating config file: " . $e->getMessage());
        return false;
    }
}

/**
 * Updates the maintenance mode configuration
 */
function updateMaintenanceMode($enabled, $message) {
    try {
        $file = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'maintenance.php';
        
        // Validate file path and permissions
        if (!is_writable($file)) {
            throw new Exception("Maintenance configuration file is not writable");
        }

        // Create backup
        $backupFile = $file . '.bak.' . date('Y-m-d-H-i-s');
        if (!copy($file, $backupFile)) {
            throw new Exception("Failed to create backup of maintenance configuration");
        }

        // Sanitize message
        $message = str_replace(["'", "\\"], ["\'", "\\\\"], $message);

        // Prepare content
        $content = "<?php\n// Last updated: " . date('Y-m-d H:i:s') . "\n\n";
        $content .= "\$maintenance_mode = " . ($enabled ? 'true' : 'false') . ";\n";
        $content .= "\$maintenance_message = '" . $message . "';\n\n";
        $content .= "function isMaintenanceMode() {\n";
        $content .= "    global \$maintenance_mode;\n";
        $content .= "    return \$maintenance_mode;\n";
        $content .= "}\n\n";
        $content .= "function getMaintenanceMessage() {\n";
        $content .= "    global \$maintenance_message;\n";
        $content .= "    return \$maintenance_message;\n";
        $content .= "}\n";

        // Write to temporary file first
        $tempFile = $file . '.tmp';
        if (file_put_contents($tempFile, $content) === false) {
            throw new Exception("Failed to write to temporary maintenance file");
        }

        // Verify the temporary file
        if (!is_readable($tempFile)) {
            throw new Exception("Cannot read temporary maintenance file");
        }

        // Rename temporary file to actual file
        if (!rename($tempFile, $file)) {
            unlink($tempFile);
            throw new Exception("Failed to update maintenance file");
        }

        return true;
    } catch (Exception $e) {
        error_log("Error updating maintenance mode: " . $e->getMessage());
        return false;
    }
}
?> 