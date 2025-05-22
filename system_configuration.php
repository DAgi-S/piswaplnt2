<?php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Required files
require_once 'php_action/db_connect.php';
require_once 'php_action/core.php';

// Basic security check
if (!isset($_SESSION['userId'])) {
    header('location: login.php');
    exit();
}

// Initialize variables
$success = false;
$message = '';
$hasPermission = false;
$canEdit = false;
$userId = $_SESSION['userId'];

try {
    // First check if user exists
    $userQuery = "SELECT * FROM users WHERE user_id = ?";
    $stmt = $connect->prepare($userQuery);
    if (!$stmt) {
        throw new Exception("Database error: " . $connect->error);
    }
    
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    
    if (!$userData) {
        throw new Exception("User not found");
    }
    
    // Get user's role
    $roleQuery = "SELECT r.role_name 
                  FROM roles r 
                  INNER JOIN role_permissions rp ON r.role_id = rp.role_id 
                  WHERE r.role_id = 1 
                  LIMIT 1";
    
    $result = $connect->query($roleQuery);
    $roleData = $result->fetch_assoc();
    
    // If user has admin role, grant all permissions
    if ($roleData && strtolower($roleData['role_name']) === 'admin') {
        $hasPermission = true;
        $canEdit = true;
    } else {
        // Check specific permissions
        $permissionQuery = "SELECT DISTINCT p.permission_name 
                          FROM permissions p 
                          INNER JOIN role_permissions rp ON p.permission_id = rp.permission_id 
                          WHERE rp.role_id = 1 
                          AND p.permission_name IN ('view_system_config', 'edit_system_config', 'manage_system_config')";
        
        $result = $connect->query($permissionQuery);
        
        while ($row = $result->fetch_assoc()) {
            if ($row['permission_name'] === 'view_system_config') {
                $hasPermission = true;
            }
            if (in_array($row['permission_name'], ['edit_system_config', 'manage_system_config'])) {
                $canEdit = true;
            }
        }
    }

    if (!$hasPermission) {
        throw new Exception("You don't have permission to access system configuration.");
    }

    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!$canEdit) {
            throw new Exception("You don't have permission to modify system configuration.");
        }

        if (isset($_POST['email_config'])) {
            // Validate and sanitize inputs
            $mailServer = filter_input(INPUT_POST, 'mail_server', FILTER_SANITIZE_STRING);
            $mailPort = filter_input(INPUT_POST, 'mail_port', FILTER_VALIDATE_INT);
            $mailUsername = filter_input(INPUT_POST, 'mail_username', FILTER_SANITIZE_STRING);
            $mailPassword = $_POST['mail_password']; // Will be encrypted before storage
            $defaultSender = filter_input(INPUT_POST, 'mail_from', FILTER_VALIDATE_EMAIL);

            if (!$mailServer || !$mailPort || !$mailUsername || !$defaultSender) {
                throw new Exception("Please fill all required fields with valid values.");
            }

            // Update configuration in database
            $updateQuery = "INSERT INTO system_config_settings 
                           (category_id, setting_key, setting_value, data_type) 
                           VALUES (
                               (SELECT category_id FROM system_config_categories WHERE category_name = 'Email'),
                               ?, ?, ?
                           ) 
                           ON DUPLICATE KEY UPDATE 
                           setting_value = VALUES(setting_value),
                           data_type = VALUES(data_type),
                           updated_at = NOW()";
            
            $stmt = $connect->prepare($updateQuery);
            
            // Update each setting
            $settings = [
                'mail_server' => ['value' => $mailServer, 'type' => 'string'],
                'mail_port' => ['value' => $mailPort, 'type' => 'integer'],
                'mail_username' => ['value' => $mailUsername, 'type' => 'email'],
                'mail_password' => ['value' => password_hash($mailPassword, PASSWORD_DEFAULT), 'type' => 'password'],
                'mail_from' => ['value' => $defaultSender, 'type' => 'email']
            ];

            foreach ($settings as $key => $setting) {
                $stmt->bind_param("sss", $key, $setting['value'], $setting['type']);
                $stmt->execute();
            }

            // Log the configuration change
            $logQuery = "INSERT INTO system_configuration_history 
                        (config_key, new_value, changed_by, created_at) 
                        VALUES (?, ?, ?, NOW())";
            $stmt = $connect->prepare($logQuery);
            
            foreach ($settings as $key => $setting) {
                $value = ($key === 'mail_password') ? '********' : $setting['value'];
                $stmt->bind_param("ssi", $key, $value, $userId);
                $stmt->execute();
            }

            $success = true;
            $message = "Email configuration updated successfully";
        }
        // Add similar blocks for database, backup, and maintenance configurations
    }

} catch (Exception $e) {
    error_log("Error in system_configuration.php: " . $e->getMessage());
    $message = $e->getMessage();
    $success = false;
}

// Include header
include('includes/header.php');
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <ol class="breadcrumb">
                <li><a href="dashboard.php">Home</a></li>
                <li class="active">System Configuration</li>
            </ol>

            <?php if(!empty($message)): ?>
            <div class="alert alert-<?php echo $success ? 'success' : 'danger'; ?> alert-dismissible" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <?php if($hasPermission): ?>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <div class="page-heading">
                        <i class="glyphicon glyphicon-wrench"></i> System Configuration
                    </div>
                </div>
                <div class="panel-body">
                    <ul class="nav nav-tabs" role="tablist">
                        <li role="presentation" class="active">
                            <a href="#email" aria-controls="email" role="tab" data-toggle="tab">
                                <i class="glyphicon glyphicon-envelope"></i> Email Configuration
                            </a>
                        </li>
                        <li role="presentation">
                            <a href="#database" aria-controls="database" role="tab" data-toggle="tab">
                                <i class="glyphicon glyphicon-hdd"></i> Database Configuration
                            </a>
                        </li>
                        <li role="presentation">
                            <a href="#backup" aria-controls="backup" role="tab" data-toggle="tab">
                                <i class="glyphicon glyphicon-cloud-upload"></i> Backup Configuration
                            </a>
                        </li>
                        <li role="presentation">
                            <a href="#maintenance" aria-controls="maintenance" role="tab" data-toggle="tab">
                                <i class="glyphicon glyphicon-wrench"></i> Maintenance Mode
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <!-- Email Configuration Tab -->
                        <div role="tabpanel" class="tab-pane active" id="email">
                            <form class="form-horizontal" action="" method="post">
                                <input type="hidden" name="email_config" value="1">
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Mail Server</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="mail_server" id="mail_server" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Mail Port</label>
                                    <div class="col-sm-9">
                                        <input type="number" class="form-control" name="mail_port" id="mail_port" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Mail Username</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="mail_username" id="mail_username" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Mail Password</label>
                                    <div class="col-sm-9">
                                        <input type="password" class="form-control" name="mail_password" id="mail_password" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Default Sender</label>
                                    <div class="col-sm-9">
                                        <input type="email" class="form-control" name="mail_from" id="mail_from" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Test Email Recipient</label>
                                    <div class="col-sm-9">
                                        <input type="email" class="form-control" id="test_email_recipient" value="salem@lebawi.net" placeholder="Enter test email recipient">
                                        <span class="help-block">Email address to send test email to</span>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="col-sm-offset-3 col-sm-9">
                                        <button type="submit" class="btn btn-primary" <?php echo (!$canEdit) ? 'disabled' : ''; ?>>Save Email Configuration</button>
                                        <button type="button" class="btn btn-info" id="testEmailConfig">Test Configuration</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Database Configuration Tab -->
                        <div role="tabpanel" class="tab-pane" id="database">
                            <form id="databaseConfigForm" class="form-horizontal" action="" method="post">
                                <input type="hidden" name="database_config" value="1">
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Database Host</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="db_host" id="db_host" value="localhost" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Database Name</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="db_name" id="db_name" value="pistocklntmarch" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Username</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="db_user" id="db_user" value="root" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Password</label>
                                    <div class="col-sm-9">
                                        <input type="password" class="form-control" name="db_password" id="db_password">
                                        <span class="help-block text-muted">Password is optional for localhost connections</span>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="col-sm-offset-3 col-sm-9">
                                        <button type="button" class="btn btn-primary" id="saveDBConfig">Save Database Configuration</button>
                                        <button type="button" class="btn btn-info" id="testDBConfig">Test Connection</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Backup Configuration Tab -->
                        <div role="tabpanel" class="tab-pane" id="backup">
                            <form id="backupConfigForm" class="form-horizontal" action="" method="post">
                                <input type="hidden" name="backup_config" value="1">
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Backup Directory</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="backup_dir" id="backup_dir" value="backups/" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Backup Frequency (hours)</label>
                                    <div class="col-sm-9">
                                        <input type="number" class="form-control" name="backup_frequency" id="backup_frequency" value="24" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Keep Last N Backups</label>
                                    <div class="col-sm-9">
                                        <input type="number" class="form-control" name="keep_backups" id="keep_backups" value="7" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="col-sm-offset-3 col-sm-9">
                                        <button type="button" class="btn btn-primary" id="saveBackupConfig">Save Backup Configuration</button>
                                        <button type="button" class="btn btn-info" id="createBackup">Create Backup Now</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Maintenance Mode Tab -->
                        <div role="tabpanel" class="tab-pane" id="maintenance">
                            <form class="form-horizontal" action="" method="post">
                                <input type="hidden" name="maintenance_config" value="1">
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Maintenance Mode</label>
                                    <div class="col-sm-9">
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" name="maintenance_mode" value="1"> Enable Maintenance Mode
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Maintenance Message</label>
                                    <div class="col-sm-9">
                                        <textarea class="form-control" name="maintenance_message" rows="3">System is under maintenance. Please try again later.</textarea>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="col-sm-offset-3 col-sm-9">
                                        <button type="submit" class="btn btn-primary" <?php echo (!$canEdit) ? 'disabled' : ''; ?>>Save Maintenance Settings</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-danger">
                <strong>Error!</strong> You do not have permission to access this page.
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Load current settings
    function loadEmailSettings() {
        $.ajax({
            url: 'php_action/get_system_settings.php',
            type: 'GET',
            data: { category: 'Email' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var settings = response.settings;
                    // Populate form fields
                    $('#mail_server').val(settings.mail_server ? settings.mail_server.value : '');
                    $('#mail_port').val(settings.mail_port ? settings.mail_port.value : '');
                    $('#mail_username').val(settings.mail_username ? settings.mail_username.value : '');
                    $('#mail_from').val(settings.mail_from ? settings.mail_from.value : '');
                    // Don't populate password for security
                } else {
                    showAlert('danger', 'Failed to load settings: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                showAlert('danger', 'Error loading settings: ' + error);
            }
        });
    }

    // Load database settings
    function loadDatabaseSettings() {
        $.ajax({
            url: 'php_action/get_system_settings.php',
            type: 'GET',
            data: { category: 'Database' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var settings = response.settings;
                    // Populate form fields
                    $('#db_host').val(settings.db_host ? settings.db_host.value : 'localhost');
                    $('#db_name').val(settings.db_name ? settings.db_name.value : 'pistocklntmarch');
                    $('#db_user').val(settings.db_user ? settings.db_user.value : 'root');
                    // Don't populate password for security
                } else {
                    showAlert('danger', 'Failed to load database settings: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                showAlert('danger', 'Error loading database settings: ' + error);
            }
        });
    }

    // Load backup settings
    function loadBackupSettings() {
        $.ajax({
            url: 'php_action/get_system_settings.php',
            type: 'GET',
            data: { category: 'Backup' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var settings = response.settings;
                    $('#backup_dir').val(settings.backup_dir ? settings.backup_dir.value : 'backups/');
                    $('#backup_frequency').val(settings.backup_frequency ? settings.backup_frequency.value : '24');
                    $('#keep_backups').val(settings.keep_backups ? settings.keep_backups.value : '7');
                } else {
                    showAlert('danger', 'Failed to load backup settings: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                showAlert('danger', 'Error loading backup settings: ' + error);
            }
        });
    }

    // Load settings when page loads
    loadEmailSettings();
    loadDatabaseSettings();
    loadBackupSettings();

    // Add tab change handler to reload settings
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        var target = $(e.target).attr("href");
        if (target === '#database') {
            loadDatabaseSettings();
        } else if (target === '#email') {
            loadEmailSettings();
        } else if (target === '#backup') {
            loadBackupSettings();
        }
    });

    // Handle test email configuration
    $('#testEmailConfig').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Testing...');
        
        // Remove any existing alerts
        $('.alert').remove();
        
        var formData = {
            mail_server: $('#mail_server').val(),
            mail_port: $('#mail_port').val(),
            mail_username: $('#mail_username').val(),
            mail_password: $('#mail_password').val(),
            default_sender: $('#mail_from').val(),
            test_recipient: $('#test_email_recipient').val() || 'salem@lebawi.net'
        };

        // Validate form data
        if (!formData.mail_server || !formData.mail_port || !formData.mail_username || 
            !formData.mail_password || !formData.default_sender) {
            showAlert('danger', 'Please fill in all required fields');
            btn.prop('disabled', false).html('Test Configuration');
            return;
        }
        
        // Show testing message
        showAlert('info', 'Testing email configuration... This may take a few seconds.');
        
        // Set up AJAX request with timeout
        $.ajax({
            url: 'php_action/test_email_config.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            timeout: 30000, // 30 second timeout
            xhrFields: {
                withCredentials: true
            },
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                } else {
                    showAlert('danger', response.message || 'Failed to test email configuration');
                }
            },
            error: function(xhr, status, error) {
                var errorMessage;
                if (status === 'timeout') {
                    errorMessage = 'The test email request timed out. Please check your SMTP server settings.';
                } else if (xhr.responseJSON) {
                    errorMessage = xhr.responseJSON.message;
                } else {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        errorMessage = response.message;
                    } catch(e) {
                        errorMessage = 'Error testing configuration: ' + (error || 'Unknown error occurred');
                    }
                }
                showAlert('danger', errorMessage);
            },
            complete: function() {
                btn.prop('disabled', false).html('Test Configuration');
                // Remove the info alert if it exists
                $('.alert-info').remove();
            }
        });
    });

    // Database Configuration Handling
    $('#saveDBConfig').on('click', function(e) {
        e.preventDefault();
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
        
        // Remove any existing alerts
        $('.alert').remove();
        
        var formData = {
            db_host: $('#db_host').val(),
            db_name: $('#db_name').val(),
            db_user: $('#db_user').val(),
            db_password: $('#db_password').val()
        };

        // Validate form data
        var isLocalhost = formData.db_host.toLowerCase() === 'localhost';
        if (!formData.db_host || !formData.db_name || !formData.db_user) {
            showAlert('danger', 'Please fill in all required fields' + 
                     (isLocalhost ? ' (password is optional for localhost)' : ''));
            btn.prop('disabled', false).html('Save Database Configuration');
            return;
        }

        // Show saving message
        showAlert('info', 'Saving database configuration...');

        $.ajax({
            url: 'php_action/save_system_settings.php',
            type: 'POST',
            data: {
                category: 'Database',
                settings: formData
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                } else {
                    showAlert('danger', response.message || 'Failed to save database configuration');
                }
            },
            error: function(xhr, status, error) {
                var errorMessage;
                if (xhr.responseJSON) {
                    errorMessage = xhr.responseJSON.message;
                } else {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        errorMessage = response.message;
                    } catch(e) {
                        errorMessage = 'Error saving configuration: ' + (error || 'Unknown error occurred');
                    }
                }
                showAlert('danger', errorMessage);
            },
            complete: function() {
                btn.prop('disabled', false).html('Save Database Configuration');
                // Remove the info alert if it exists
                $('.alert-info').remove();
            }
        });
    });

    // Test database connection handling
    $('#testDBConfig').on('click', function(e) {
        e.preventDefault();
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Testing...');
        
        // Remove any existing alerts
        $('.alert').remove();
        
        var formData = {
            db_host: $('#db_host').val(),
            db_name: $('#db_name').val(),
            db_user: $('#db_user').val(),
            db_password: $('#db_password').val()
        };

        // Validate form data
        var isLocalhost = formData.db_host.toLowerCase() === 'localhost';
        if (!formData.db_host || !formData.db_name || !formData.db_user) {
            showAlert('danger', 'Please fill in all required fields' + 
                     (isLocalhost ? ' (password is optional for localhost)' : ''));
            btn.prop('disabled', false).html('Test Connection');
            return;
        }
        
        // Show testing message
        showAlert('info', 'Testing database connection... Please wait.');
        
        $.ajax({
            url: 'php_action/test_db_config.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                } else {
                    showAlert('danger', response.message || 'Failed to test database connection');
                }
            },
            error: function(xhr, status, error) {
                var errorMessage;
                if (xhr.responseJSON) {
                    errorMessage = xhr.responseJSON.message;
                } else {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        errorMessage = response.message;
                    } catch(e) {
                        errorMessage = 'Error testing connection: ' + (error || 'Unknown error occurred');
                    }
                }
                showAlert('danger', errorMessage);
            },
            complete: function() {
                btn.prop('disabled', false).html('Test Connection');
                // Remove the info alert if it exists
                $('.alert-info').remove();
            }
        });
    });

    // Save backup configuration
    $('#saveBackupConfig').on('click', function(e) {
        e.preventDefault();
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
        
        var formData = {
            backup_dir: $('#backup_dir').val(),
            backup_frequency: $('#backup_frequency').val(),
            keep_backups: $('#keep_backups').val()
        };

        // Validate form data
        if (!formData.backup_dir || !formData.backup_frequency || !formData.keep_backups) {
            showAlert('danger', 'Please fill in all required fields');
            btn.prop('disabled', false).html('Save Backup Configuration');
            return;
        }

        $.ajax({
            url: 'php_action/save_system_settings.php',
            type: 'POST',
            data: {
                category: 'Backup',
                settings: formData
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                } else {
                    showAlert('danger', response.message || 'Failed to save backup configuration');
                }
            },
            error: function(xhr, status, error) {
                showAlert('danger', 'Error saving configuration: ' + error);
            },
            complete: function() {
                btn.prop('disabled', false).html('Save Backup Configuration');
            }
        });
    });

    // Create backup now
    $('#createBackup').on('click', function(e) {
        e.preventDefault();
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Creating Backup...');
        
        // Show backup in progress message
        showAlert('info', 'Creating backup... This may take a few minutes.');
        
        $.ajax({
            url: 'php_action/handle_backup.php',
            type: 'POST',
            data: {
                action: 'create_backup'
            },
            dataType: 'json',
            xhrFields: {
                withCredentials: true
            },
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                } else {
                    showAlert('danger', response.message || 'Failed to create backup');
                }
            },
            error: function(xhr, status, error) {
                var errorMessage;
                if (xhr.responseJSON) {
                    errorMessage = xhr.responseJSON.message;
                } else {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        errorMessage = response.message || error;
                    } catch(e) {
                        errorMessage = 'Error creating backup: ' + error;
                    }
                }
                showAlert('danger', errorMessage);
                console.error('Backup Error:', xhr.responseText);
            },
            complete: function() {
                btn.prop('disabled', false).html('Create Backup Now');
                // Remove the info alert
                $('.alert-info').remove();
            }
        });
    });

    // Helper function to show alerts
    function showAlert(type, message) {
        // Remove existing alerts of the same type
        $('.alert-' + type).remove();
        
        var alert = '<div class="alert alert-' + type + ' alert-dismissible" role="alert">' +
                   '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                   '<span aria-hidden="true">&times;</span></button>' + message + '</div>';
        
        // Add the new alert after the panel heading
        $('.panel-heading').after(alert);
        
        // Scroll to the alert
        $('html, body').animate({
            scrollTop: $('.alert').offset().top - 100
        }, 200);

        // Auto-hide success and info messages after 5 seconds
        if (type === 'success' || type === 'info') {
            setTimeout(function() {
                $('.alert-' + type).fadeOut('slow', function() {
                    $(this).remove();
                });
            }, 5000);
        }
    }

    // Auto-hide alerts after 5 seconds
    $(document).on('click', '.alert .close', function(e) {
        $(this).parent().remove();
    });

    setTimeout(function() {
        $('.alert-success').fadeOut('slow');
    }, 5000);
});
</script>

<?php
// Include footer
include('includes/footer.php');
?>