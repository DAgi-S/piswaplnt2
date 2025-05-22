<?php 
require_once 'php_action/db_connect.php';
session_start();

if(isset($_SESSION['userId'])) {
    header('location: ' . $store_url . 'dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pi Stock - Forgot Password</title>
    <link rel="stylesheet" href="<?php echo $store_url; ?>assests/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo $store_url; ?>assests/bootstrap/css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="<?php echo $store_url; ?>assests/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="<?php echo $store_url; ?>custom/css/custom.css">
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
                        <form class="form-horizontal" id="resetPasswordForm">
                            <fieldset>
                                <div class="form-group">
                                    <label for="email" class="col-sm-3 control-label">Email</label>
                                    <div class="col-sm-9">
                                        <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-sm-offset-3 col-sm-9">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="glyphicon glyphicon-send"></i> Send Reset Link
                                        </button>
                                    </div>
                                </div>
                            </fieldset>
                        </form>
                        <div class="form-group">
                            <div class="col-sm-12 text-center">
                                <a href="index.php">Back to Login</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo $store_url; ?>assests/jquery/jquery.min.js"></script>
    <script src="<?php echo $store_url; ?>assests/bootstrap/js/bootstrap.min.js"></script>
    <script src="<?php echo $store_url; ?>custom/js/forgot-password.js"></script>
</body>
</html> 