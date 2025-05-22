<?php 
require_once 'php_action/config.php';
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <link rel="stylesheet" href="assests/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="custom/css/custom.css">
</head>
<body>
    <div class="container">
        <div class="row vertical">
            <div class="col-md-5 col-md-offset-4">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title">Reset Password</h3>
                    </div>
                    <div class="panel-body">
                        <div class="messages"></div>
                        <form id="directResetForm">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="password">New Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="form-group">
                                <label for="confirmPassword">Confirm Password</label>
                                <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Reset Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assests/jquery/jquery.min.js"></script>
    <script src="assests/bootstrap/js/bootstrap.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#directResetForm').on('submit', function(e) {
            e.preventDefault();
            
            const password = $('#password').val();
            const confirmPassword = $('#confirmPassword').val();
            
            if (password !== confirmPassword) {
                $('.messages').html('<div class="alert alert-danger">Passwords do not match!</div>');
                return;
            }
            
            $.ajax({
                url: 'php_action/directPasswordReset.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        $('.messages').html('<div class="alert alert-success">' + response.messages + '</div>');
                        setTimeout(() => window.location.href = 'index.php', 2000);
                    } else {
                        $('.messages').html('<div class="alert alert-danger">' + response.messages + '</div>');
                    }
                },
                error: function() {
                    $('.messages').html('<div class="alert alert-danger">Error occurred. Please try again.</div>');
                }
            });
        });
    });
    </script>
</body>
</html> 