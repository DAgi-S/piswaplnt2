<?php 
require_once 'php_action/db_connect.php';
require_once 'php_action/config.php';
session_start();

// Verify token is valid
if(!isset($_GET['token'])) {
    header('Location: index.php');
    exit();
}

$token = $_GET['token'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <link rel="stylesheet" href="<?php echo $store_url; ?>assests/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo $store_url; ?>assests/bootstrap/css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="<?php echo $store_url; ?>custom/css/custom.css">
</head>
<body>
    <div class="container">
        <div class="row vertical">
            <div class="col-md-5 col-md-offset-4">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title">Set New Password</h3>
                    </div>
                    <div class="panel-body">
                        <div class="messages"></div>
                        <form id="resetPasswordForm">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
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

    <script src="<?php echo $store_url; ?>assests/jquery/jquery.min.js"></script>
    <script src="<?php echo $store_url; ?>assests/bootstrap/js/bootstrap.min.js"></script>
    <script src="<?php echo $store_url; ?>custom/js/reset-password.js"></script>
</body>
</html> 