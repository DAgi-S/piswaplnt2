<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Basic authentication check
if (!isset($_SESSION['userId'])) {
    header('Location: login.php');
    exit();
}

// Set proper content type
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permission System Setup</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .setup-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        .status-message {
            margin-top: 20px;
            display: none;
        }
        .progress {
            margin-top: 20px;
            display: none;
        }
        .verification-list {
            list-style: none;
            padding-left: 0;
        }
        .verification-item {
            margin: 5px 0;
            padding: 10px;
            border-radius: 4px;
        }
        .verification-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .verification-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container setup-container">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0">Permission System Setup</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h4 class="alert-heading">Important Information</h4>
                    <p>This setup will create and configure the necessary components for the permission system:</p>
                    <ul>
                        <li>Create required database tables (roles, permissions, role_permissions)</li>
                        <li>Set up the admin role and basic permissions</li>
                        <li>Create the permission checking function</li>
                        <li>Configure the admin user</li>
                    </ul>
                    <hr>
                    <p class="mb-0">Please ensure you have backed up your database before proceeding.</p>
                </div>

                <div id="setupStatus" class="status-message alert" role="alert"></div>

                <div class="progress">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                </div>

                <div class="text-center mt-4">
                    <button id="startSetup" class="btn btn-primary btn-lg">
                        <i class="fas fa-cog"></i> Start Setup
                    </button>
                </div>

                <div id="verificationResults" class="mt-4" style="display: none;">
                    <h4>Setup Verification Results</h4>
                    <ul class="verification-list">
                        <li id="rolesVerification" class="verification-item">Roles Table: <span class="status"></span></li>
                        <li id="permissionsVerification" class="verification-item">Permissions Table: <span class="status"></span></li>
                        <li id="rolePermissionsVerification" class="verification-item">Role Permissions Table: <span class="status"></span></li>
                        <li id="adminUserVerification" class="verification-item">Admin User Setup: <span class="status"></span></li>
                        <li id="functionVerification" class="verification-item">Permission Function: <span class="status"></span></li>
                    </ul>
                </div>

                <div id="nextSteps" class="mt-4" style="display: none;">
                    <div class="alert alert-success">
                        <h4 class="alert-heading">Setup Complete!</h4>
                        <p>The permission system has been successfully configured. You can now:</p>
                        <ul>
                            <li>Access the system configuration page</li>
                            <li>Manage user roles and permissions</li>
                            <li>Configure system settings</li>
                        </ul>
                        <hr>
                        <a href="system_configuration.php" class="btn btn-success">Go to System Configuration</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <script>
        $(document).ready(function() {
            const $startButton = $('#startSetup');
            const $progress = $('.progress');
            const $progressBar = $('.progress-bar');
            const $statusMessage = $('#setupStatus');
            const $verificationResults = $('#verificationResults');
            const $nextSteps = $('#nextSteps');

            function updateVerificationItem(id, success, message) {
                const $item = $(`#${id}`);
                $item.removeClass('verification-success verification-error')
                     .addClass(success ? 'verification-success' : 'verification-error');
                $item.find('.status').html(message);
            }

            function handleError(message) {
                $statusMessage.removeClass('alert-success alert-info')
                            .addClass('alert-danger')
                            .html(`<strong>Error:</strong> ${message}`)
                            .show();
                $progressBar.css('width', '0%');
                $startButton.prop('disabled', false);
            }

            $startButton.click(function() {
                // Reset UI
                $startButton.prop('disabled', true);
                $progress.show();
                $progressBar.css('width', '0%');
                $statusMessage.removeClass('alert-danger alert-success')
                            .addClass('alert-info')
                            .html('Setting up permission system...')
                            .show();
                $verificationResults.hide();
                $nextSteps.hide();

                // Animate progress bar
                $progressBar.css('width', '50%');

                // Make AJAX request
                $.ajax({
                    url: 'php_action/setup_permissions.php',
                    method: 'POST',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $progressBar.css('width', '100%');
                            $statusMessage.removeClass('alert-info alert-danger')
                                        .addClass('alert-success')
                                        .html('<strong>Success!</strong> ' + response.message);

                            // Update verification results
                            $verificationResults.show();
                            updateVerificationItem('rolesVerification', response.verification.roles, 
                                response.verification.roles ? '<i class="fas fa-check"></i> Created successfully' : '<i class="fas fa-times"></i> Failed');
                            updateVerificationItem('permissionsVerification', response.verification.permissions,
                                response.verification.permissions ? '<i class="fas fa-check"></i> Created successfully' : '<i class="fas fa-times"></i> Failed');
                            updateVerificationItem('rolePermissionsVerification', response.verification.role_permissions,
                                response.verification.role_permissions ? '<i class="fas fa-check"></i> Created successfully' : '<i class="fas fa-times"></i> Failed');
                            updateVerificationItem('adminUserVerification', response.verification.admin_user,
                                response.verification.admin_user ? '<i class="fas fa-check"></i> Configured successfully' : '<i class="fas fa-times"></i> Failed');
                            updateVerificationItem('functionVerification', response.verification.has_permission_function,
                                response.verification.has_permission_function ? '<i class="fas fa-check"></i> Created successfully' : '<i class="fas fa-times"></i> Failed');

                            // Show next steps if everything is successful
                            if (!Object.values(response.verification).includes(false)) {
                                $nextSteps.show();
                            }
                        } else {
                            handleError(response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        handleError('Failed to set up permissions: ' + (error || 'Unknown error'));
                    }
                });
            });
        });
    </script>
</body>
</html> 