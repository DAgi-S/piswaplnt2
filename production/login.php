<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        $localhost = "localhost";
        $dbUsername = "root";
        $dbPassword = "";
        $dbname = "pistocklnt";

        try {
            $connect = new mysqli($localhost, $dbUsername, $dbPassword, $dbname);
            
            if($connect->connect_error) {
                throw new Exception("Connection Failed: " . $connect->connect_error);
            }
            
            $stmt = $connect->prepare("SELECT user_id, username, password, role_id FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if(password_verify($password, $user['password'])) {
                    $_SESSION['userId'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['roleId'] = $user['role_id'];
                    
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error = 'Invalid password';
                }
            } else {
                $error = 'User not found';
            }
            
            $stmt->close();
            $connect->close();
            
        } catch(Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Production Management System</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 40px;
        }
        .login-container {
            max-width: 400px;
            margin: 0 auto;
            padding: 15px;
        }
        .login-panel {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .login-panel h2 {
            margin-bottom: 20px;
            text-align: center;
        }
        .error-message {
            color: #dc3545;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container login-container">
        <div class="login-panel">
            <h2>Production Management System</h2>
            <?php if($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>
        </div>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
</body>
</html> 