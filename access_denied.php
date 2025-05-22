<?php
require_once 'php_action/db_connect.php';
require_once 'php_action/core.php';
require_once 'includes/header.php';
session_start();

$message = isset($_SESSION['error']) ? $_SESSION['error'] : "You don't have sufficient permissions to access this page.";
unset($_SESSION['error']); // Clear the error message
echo ("user id: " . $_SESSION['userId']);
echo ("<br>");
echo ("user id type: " . gettype($_SESSION['userId']));

?>
<!DOCTYPE html>
<html>
<head>
    <title>Access Denied</title>
    <link rel="stylesheet" href="assests/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assests/bootstrap/css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="assests/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="custom/css/custom.css">
    
</head>
<body>
    <div class="container">
        <div class="row" style="margin-top: 50px;">
            <div class="col-md-6 col-md-offset-3">
                <div class="panel panel-danger">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="glyphicon glyphicon-exclamation-sign"></i> Access Denied</h3>
                    </div>
                    <div class="panel-body">
                        <div class="alert alert-danger" role="alert">
                            <?php 
                            if(isset($_SESSION['error'])) {
                                echo $_SESSION['error'];
                                unset($_SESSION['error']);
                            } else {
                                echo "You don't have permission to access this resource. Please contact your administrator.";
                            }
                            ?>
                        </div>
                        <a href="dashboard.php" class="btn btn-primary">
                            <i class="glyphicon glyphicon-home"></i> Return to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 
<?php
require_once 'includes/footer.php';
?>