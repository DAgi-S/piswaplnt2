<?php
require_once 'php_action/core.php';
require_once 'php_action/config/mail_config.php';

// Debug session information
echo "Session Info:<br>";
echo "userId: " . (isset($_SESSION['userId']) ? $_SESSION['userId'] : 'not set') . "<br>";
echo "userRole: " . (isset($_SESSION['userRole']) ? $_SESSION['userRole'] : 'not set') . "<br>";
exit(); // Temporary exit to see debug info

// Check if user is logged in and has admin privileges
if (!isset($_SESSION['userId']) || !isset($_SESSION['userRole']) || $_SESSION['userRole'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Include header after permission checks
require_once 'includes/header.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Email Test Page</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="custom/css/custom.css">
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h1>Email Configuration Test</h1>
                <div class="card">
                    <div class="card-header">
                        <h3>Current Configuration</h3>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <tr>
                                <th>SMTP Server</th>
                                <td><?php echo MAIL_SERVER; ?></td>
                            </tr>
                            <tr>
                                <th>SMTP Port</th>
                                <td><?php echo MAIL_PORT; ?></td>
                            </tr>
                            <tr>
                                <th>SMTP Username</th>
                                <td><?php echo MAIL_USERNAME; ?></td>
                            </tr>
                            <tr>
                                <th>SMTP Encryption</th>
                                <td><?php echo defined('MAIL_ENCRYPTION') ? MAIL_ENCRYPTION : 'none'; ?></td>
                            </tr>
                            <tr>
                                <th>Default Sender</th>
                                <td><?php echo MAIL_DEFAULT_SENDER; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h3>Send Test Email</h3>
                    </div>
                    <div class="card-body">
                        <div id="emailResult"></div>
                        <button id="sendTestEmail" class="btn btn-primary">Send Test Email</button>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h3>Debug Output</h3>
                    </div>
                    <div class="card-body">
                        <pre id="debugOutput"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/jquery/jquery.min.js"></script>
    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#sendTestEmail').click(function() {
                var btn = $(this);
                btn.prop('disabled', true).text('Sending...');
                
                $('#emailResult').html('');
                $('#debugOutput').html('');
                
                $.ajax({
                    url: 'php_action/testEmail.php',
                    method: 'POST',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#emailResult').html(
                                '<div class="alert alert-success">' + 
                                response.messages + 
                                '</div>'
                            );
                        } else {
                            $('#emailResult').html(
                                '<div class="alert alert-danger">' + 
                                response.messages + 
                                '</div>'
                            );
                        }
                        
                        // Show debug information
                        var debugInfo = response.debug || '';
                        if (response.smtp_info) {
                            debugInfo += '\n\nSMTP Configuration:';
                            debugInfo += '\nHost: ' + response.smtp_info.host;
                            debugInfo += '\nPort: ' + response.smtp_info.port;
                            debugInfo += '\nEncryption: ' + response.smtp_info.encryption;
                        }
                        $('#debugOutput').text(debugInfo);
                    },
                    error: function(xhr, status, error) {
                        $('#emailResult').html(
                            '<div class="alert alert-danger">' +
                            'Error: ' + error +
                            '</div>'
                        );
                    },
                    complete: function() {
                        btn.prop('disabled', false).text('Send Test Email');
                    }
                });
            });
        });
    </script>
</body>
</html> 