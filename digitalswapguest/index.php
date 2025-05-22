<?php
require_once 'includes/core.php';

// Debug session state
error_log("Login Page - Session ID: " . session_id());
error_log("Login Page - Session Data: " . print_r($_SESSION, true));

// If user is already logged in, redirect to dashboard
if (isLoggedIn()) {
    error_log("Login Page - User already logged in, redirecting to dashboard");
    header('Location: ' . SITE_URL . '/dashboard.php');
    exit();
}

error_log("Login Page - User not logged in, showing login form");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Guest Portal Login</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="custom/css/custom.css">
    
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            padding-top: 40px;
            padding-bottom: 40px;
        }
        
        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 15px;
            margin: auto;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        .card-header {
            background-color: #007bff;
            color: white;
            text-align: center;
            padding: 1.5rem;
            border-radius: 10px 10px 0 0;
            border: none;
        }
        
        .card-body {
            padding: 2rem;
        }
        
        .form-control {
            height: 45px;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .btn-login {
            height: 45px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 500;
        }
        
        .alert {
            margin-bottom: 1rem;
            display: none;
        }
        
        .input-group-text {
            background-color: transparent;
            border-right: none;
        }
        
        .form-control {
            border-left: none;
        }
        
        .form-control:focus {
            box-shadow: none;
            border-color: #ced4da;
        }
        
        .input-group:focus-within {
            box-shadow: 0 0 0 .2rem rgba(0,123,255,.25);
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="card">
            <div class="card-header text-center">
                <h4 class="mb-0">Guest Portal Login</h4>
            </div>
            <div class="card-body">
                <div class="alert alert-danger d-none" id="error-message" role="alert"></div>
                <form id="loginForm" method="post">
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-user"></i>
                            </span>
                            <input type="text" class="form-control" id="username" name="username" 
                                placeholder="Username" required autocomplete="off">
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" class="form-control" id="password" name="password" 
                                placeholder="Password" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 btn-login">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        // Hide error message initially
        $('#error-message').addClass('d-none');
        
        $('#loginForm').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            const $errorDiv = $('#error-message');
            
            // Validate form
            if (!$form[0].checkValidity()) {
                $errorDiv.html('Please fill in all required fields')
                        .removeClass('d-none');
                return;
            }
            
            // Hide any previous error messages
            $errorDiv.addClass('d-none');
            
            // Disable submit button and show loading state
            $submitBtn.prop('disabled', true)
                     .html('<i class="fas fa-spinner fa-spin"></i> Logging in...');
            
            $.ajax({
                url: 'php_action/guestLogin.php',
                type: 'POST',
                data: $form.serialize(),
                dataType: 'json'
            })
            .done(function(response) {
                if(response.success) {
                    // Show success message briefly before redirect
                    $errorDiv.removeClass('alert-danger')
                            .addClass('alert-success')
                            .html('Login successful! Redirecting...')
                            .removeClass('d-none');
                            
                    // Use the redirect URL from the server if provided
                    setTimeout(function() {
                        if (response.redirect) {
                            window.location.href = response.redirect;
                        } else {
                            // Fallback to dashboard
                            window.location.href = 'dashboard.php';
                        }
                    }, 500);
                } else {
                    $errorDiv.html(response.messages)
                            .removeClass('d-none');
                }
            })
            .fail(function(xhr, status, error) {
                console.error('Login error:', error);
                $errorDiv.html('An error occurred. Please try again.')
                        .removeClass('d-none');
            })
            .always(function() {
                // Re-enable submit button and restore text
                $submitBtn.prop('disabled', false)
                         .html('<i class="fas fa-sign-in-alt"></i> Login');
            });
        });
    });
    </script>
</body>
</html> 