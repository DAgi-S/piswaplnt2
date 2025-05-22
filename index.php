<?php 
require_once 'php_action/db_connect.php';

session_start();

// Define base URL - this should be set in your configuration file ideally
$store_url = '';
if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    $store_url = "https://";
} else {
    $store_url = "http://";
}
$store_url .= $_SERVER['HTTP_HOST'];
$store_url .= rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/';

if(isset($_SESSION['userId'])) {
    // Check if session is still valid
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
        // Last request was more than 30 minutes ago
        session_unset();     // unset $_SESSION variable for this page
        session_destroy();   // destroy session data
        header('location: ' . $store_url . 'index.php');
        exit();
    }
    $_SESSION['LAST_ACTIVITY'] = time(); // Update last activity timestamp

    // Redirect based on role
    switch($_SESSION['roleId']) {
        case 1: // Super Admin
        case 2: // Admin
            header('location: ' . $store_url . 'dashboard.php');
            break;
        case 5: // Production Manager
            header('location: ' . $store_url . 'production/dashboard.php');
            break;
        case 6: // Production Supervisor
            header('location: ' . $store_url . 'production/dashboard.php');
            break;
        case 7: // Production Operator
            header('location: ' . $store_url . 'production/orders.php');
            break;
        default:
            header('location: ' . $store_url . 'dashboard.php');
    }
    exit();
}

$errors = array();

if($_POST) {        
    $username = mysqli_real_escape_string($connect, $_POST['username']);
    $password = $_POST['password'];

    if(empty($username) || empty($password)) {
        if($username == "") {
            $errors[] = "Username is required";
        } 
        if($password == "") {
            $errors[] = "Password is required";
        }
    } else {
        // Check if user exists and verify password
        $sql = "SELECT u.*, ur.role_id, ur.role_name, ur.status as role_status
                FROM users u
                JOIN user_roles ur ON u.role_id = ur.role_id
                WHERE u.username = ? AND u.status = 1";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Check if role is active
            if($user['role_status'] != 1) {
                $errors[] = "Your role is currently inactive. Please contact administrator.";
            }
            // Verify password using password_verify
            else if(password_verify($password, $user['password'])) {
                // Start with a clean session
                session_regenerate_id(true);
                
                // Set session variables
                $_SESSION['userId'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['roleId'] = $user['role_id'];
                $_SESSION['role'] = $user['role_name'];
                $_SESSION['LAST_ACTIVITY'] = time();
                $_SESSION['CREATED'] = time();
                $_SESSION['IP'] = $_SERVER['REMOTE_ADDR'];
                
                // Get user permissions
                $permSql = "SELECT p.permission_name 
                           FROM permissions p 
                           JOIN role_permissions rp ON p.permission_id = rp.permission_id 
                           WHERE rp.role_id = ?";
                $permStmt = $connect->prepare($permSql);
                $permStmt->bind_param("i", $user['role_id']);
                $permStmt->execute();
                $permResult = $permStmt->get_result();
                
                $permissions = array();
                while($row = $permResult->fetch_assoc()) {
                    $permissions[] = $row['permission_name'];
                }
                $_SESSION['permissions'] = $permissions;
                
                // Ensure all output buffers are cleared
                while (ob_get_level()) {
                    ob_end_clean();
                }
                
                // Redirect based on role
                switch($user['role_id']) {
                    case 1: // Super Admin
                    case 2: // Admin
                        header('Location: ' . $store_url . 'dashboard.php');
                        break;
                    case 5: // Production Manager
                        header('Location: ' . $store_url . 'production/dashboard.php');
                        break;
                    case 6: // Production Supervisor
                        header('Location: ' . $store_url . 'production/dashboard.php');
                        break;
                    case 7: // Production Operator
                        header('Location: ' . $store_url . 'production/orders.php');
                        break;
                    default:
                        header('Location: ' . $store_url . 'dashboard.php');
                }
                exit();
            } else {
                $errors[] = "Incorrect username/password combination";
            }
        } else {
            $errors[] = "Incorrect username/password combination";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
	<title>Pi Stock</title>

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
  <!-- jquery ui -->  
  <link rel="stylesheet" href="assests/jquery-ui/jquery-ui.min.css">
  <script src="assests/jquery-ui/jquery-ui.min.js"></script>

  <!-- bootstrap js -->
	<script src="assests/bootstrap/js/bootstrap.min.js"></script>
</head>
<body>
	<div class="container">
		<div class="row vertical">
			<div class="col-md-5 col-md-offset-4">
				<div class="panel panel-info">
					<div class="panel-heading">
						<h3 class="panel-title">Please Sign in</h3>
					</div>
					<div class="panel-body">

						<div class="messages">
							<?php if($errors) {
								foreach ($errors as $key => $value) {
									echo '<div class="alert alert-warning" role="alert">
									<i class="glyphicon glyphicon-exclamation-sign"></i>
									'.$value.'</div>';										
									}
								} ?>
						</div>

						<form class="form-horizontal" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post" id="loginForm">
							<fieldset>
							  <div class="form-group">
									<label for="username" class="col-sm-2 control-label">Username</label>
									<div class="col-sm-10">
									  <input type="text" class="form-control" id="username" name="username" placeholder="Username" autocomplete="off" />
									</div>
								</div>
								<div class="form-group">
									<label for="password" class="col-sm-2 control-label">Password</label>
									<div class="col-sm-10">
									  <input type="password" class="form-control" id="password" name="password" placeholder="Password" autocomplete="off" />
									</div>
								</div>								
								<div class="form-group">
									<div class="col-sm-offset-2 col-sm-10">
									  <a href="forgot-password.php">Forgot Password?</a>
									</div>
								</div>
								<div class="form-group">
									<div class="col-sm-offset-2 col-sm-10">
									  <button type="submit" class="btn btn-default"> <i class="glyphicon glyphicon-log-in"></i> Sign in</button>
									</div>
								</div>
							</fieldset>
						</form>
					</div>
					<!-- panel-body -->
				</div>
				<!-- /panel -->
			</div>
			<!-- /col-md-4 -->
		</div>
		<!-- /row -->
	</div>
	<!-- container -->	
</body>
</html>







	