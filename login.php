<!DOCTYPE html>
<html>
<head>
    <title>Login | Stock Management System</title>
    <!-- bootstrap -->
    <link rel="stylesheet" href="assests/bootstrap/css/bootstrap.min.css">
    <!-- bootstrap theme-->
    <link rel="stylesheet" href="assests/bootstrap/css/bootstrap-theme.min.css">
    <!-- font awesome -->
    <link rel="stylesheet" href="assests/font-awesome/css/font-awesome.min.css">
    <!-- custom css -->
    <link rel="stylesheet" href="custom/css/custom.css">
    <!-- jquery -->
    <script src="assests/jquery/jquery.min.js"></script>
    <!-- bootstrap js -->
    <script src="assests/bootstrap/js/bootstrap.min.js"></script>
</head>
<body>
<div class="container">
    <div class="row vertical-offset-100">
        <div class="col-md-4 col-md-offset-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title text-center">Stock Management System</h3>
                </div>
                <div class="panel-body">
                    <div class="messages"></div>
                    <form class="form-horizontal" action="php_action/login.php" method="post" id="loginForm">
                        <fieldset>
                            <div class="form-group">
                                <label for="username" class="col-sm-3 control-label">Username</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" id="username" name="username" placeholder="Username" autocomplete="off" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="password" class="col-sm-3 control-label">Password</label>
                                <div class="col-sm-9">
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" autocomplete="off" />
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-sm-offset-3 col-sm-9">
                                    <button type="submit" class="btn btn-primary"><i class="fa fa-sign-in"></i> Login</button>
                                </div>
                            </div>
                        </fieldset>
                        <!-- Add CSRF token -->
                        <input type="hidden" name="csrf_token" value="<?php echo isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : ''; ?>">
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style type="text/css">
    .vertical-offset-100 {
        padding-top: 100px;
    }
    .messages {
        margin-bottom: 15px;
    }
    .panel {
        border-color: #ddd;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .panel-heading {
        background-color: #f8f9fa !important;
        border-bottom: 1px solid #ddd;
    }
    .panel-title {
        color: #333;
        font-weight: 600;
    }
    .btn-primary {
        margin-top: 10px;
    }
    .form-control:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
    }
</style>

<script type="text/javascript">
$(document).ready(function() {
    $("#loginForm").on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        
        // Basic client-side validation
        var username = $('#username').val().trim();
        var password = $('#password').val();
        
        if(!username || !password) {
            $('.messages').html('<div class="alert alert-warning alert-dismissible" role="alert">'+
              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
              'Please enter both username and password'+
            '</div>');
            return false;
        }

        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success == true) {
                    $('.messages').html('<div class="alert alert-success alert-dismissible" role="alert">'+
                      '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                      response.messages+
                    '</div>');

                    // Redirect based on role
                    setTimeout(function() {
                        switch(response.role_id) {
                            case 1: // Super Admin
                            case 2: // Admin
                                window.location.href = "dashboard.php";
                                break;
                            case 5: // Production Manager
                                window.location.href = "production/dashboard.php";
                                break;
                            case 6: // Production Supervisor
                                window.location.href = "production/dashboard.php";
                                break;
                            case 7: // Production Operator
                                window.location.href = "production/orders.php";
                                break;
                            default:
                                window.location.href = "dashboard.php";
                        }
                    }, 1000);
                    
                } else {
                    $('.messages').html('<div class="alert alert-warning alert-dismissible" role="alert">'+
                      '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                      response.messages+
                    '</div>');
                }
            },
            error: function(xhr, status, error) {
                $('.messages').html('<div class="alert alert-danger alert-dismissible" role="alert">'+
                  '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                  'An error occurred. Please try again.'+
                '</div>');
            }
        });
    });
});
</script>
</body>
</html> 