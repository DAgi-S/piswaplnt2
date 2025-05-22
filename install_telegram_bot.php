<?php
// Installation script for Telegram Bot Integration
require_once 'php_action/db_connect.php';
require_once 'php_action/core.php';

// Check if user is logged in and is a Super Admin
if (!isset($_SESSION['userId'])) {
    header('location: login.php');
    exit();
}

// Check if user is a Super Admin
$sql = "SELECT r.role_name FROM users u JOIN user_roles r ON u.role_id = r.role_id WHERE u.user_id = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("i", $_SESSION['userId']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0 || $result->fetch_assoc()['role_name'] !== 'Super Admin') {
    // Not a Super Admin
    header('location: dashboard.php');
    exit();
}

// Process installation if form is submitted
$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    try {
        // Read the SQL file
        $sqlFile = 'sql/telegram_bot_settings.sql';
        
        if (!file_exists($sqlFile)) {
            throw new Exception("SQL file not found: $sqlFile");
        }
        
        $sql = file_get_contents($sqlFile);
        
        if (empty($sql)) {
            throw new Exception("SQL file is empty");
        }
        
        // Split SQL statements
        $statements = explode(';', $sql);
        
        // Execute each statement
        foreach ($statements as $statement) {
            $statement = trim($statement);
            
            if (!empty($statement)) {
                if (!$connect->query($statement)) {
                    throw new Exception("Error executing statement: " . $connect->error);
                }
            }
        }
        
        // Update permissions for the current user if needed
        $updatePermissionSql = "INSERT IGNORE INTO role_permissions (role_id, permission_id) 
                              SELECT u.role_id, p.permission_id 
                              FROM users u, permissions p 
                              WHERE u.user_id = ? AND p.permission_name = 'manage_telegram_bot'";
        $updateStmt = $connect->prepare($updatePermissionSql);
        $updateStmt->bind_param("i", $_SESSION['userId']);
        $updateStmt->execute();
        
        $successMsg = "Telegram Bot Integration has been installed successfully!";
    } catch (Exception $e) {
        $errorMsg = "Error during installation: " . $e->getMessage();
    }
}

// Set page title
$title = "Install Telegram Bot Integration";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="container-fluid" style="margin-top: 20px;">
    <div class="row">
        <div class="col-md-12">
            <ol class="breadcrumb">
                <li><a href="dashboard.php">Home</a></li>
                <li class="active">Install Telegram Bot Integration</li>
            </ol>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <div class="page-heading"> <i class="glyphicon glyphicon-wrench"></i> Install Telegram Bot Integration</div>
                </div> <!-- /panel-heading -->
                <div class="panel-body">
                    <?php if (!empty($successMsg)): ?>
                        <div class="alert alert-success">
                            <?php echo $successMsg; ?>
                        </div>
                        <div class="text-center">
                            <a href="telegram_bot_settings.php" class="btn btn-primary">Go to Telegram Bot Settings</a>
                        </div>
                    <?php elseif (!empty($errorMsg)): ?>
                        <div class="alert alert-danger">
                            <?php echo $errorMsg; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <p>This will install the Telegram Bot Integration which includes:</p>
                            <ul>
                                <li>Creating necessary database tables</li>
                                <li>Setting up permissions</li>
                                <li>Adding default notification templates</li>
                            </ul>
                            <p><strong>Note:</strong> This should only be run once. Running it multiple times is safe but unnecessary.</p>
                        </div>

                        <form method="post">
                            <div class="text-center">
                                <button type="submit" name="install" class="btn btn-primary">Install Telegram Bot Integration</button>
                                <a href="dashboard.php" class="btn btn-default">Cancel</a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div> <!-- /panel-body -->
            </div> <!-- /panel -->
        </div> <!-- /col-md-12 -->
    </div> <!-- /row -->
</div> <!-- /container -->

<?php include 'includes/footer.php'; ?> 