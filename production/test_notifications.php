<?php
require_once 'includes/header.php';
require_once 'php_action/db_connect.php';
require_once 'php_action/classes/NotificationHandler.php';

// Initialize notification handler
$notificationHandler = new NotificationHandler($connect);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $testItem = [
            'id' => 1,
            'name' => 'Test Product',
            'current_stock' => 5,
            'unit' => 'pcs',
            'min_stock_level' => 10,
            'warehouse_name' => 'Test Warehouse'
        ];
        
        $notificationHandler->sendLowStockNotification($testItem, 50);
        $success = "Test notifications sent successfully!";
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-bell"></i> Test Notifications
            </div>
            <div class="panel-body">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success">
                        <?php echo $success; ?>
                        <p>Please check:</p>
                        <ol>
                            <li>Your email inbox (swapcapital@lebawi.net)</li>
                            <li>Your Telegram bot chat (chat ID: 317393086)</li>
                        </ol>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="well">
                    <h4>Current Configuration</h4>
                    <dl class="dl-horizontal">
                        <dt>Email Server:</dt>
                        <dd>mail.lebawi.net</dd>
                        <dt>Email From:</dt>
                        <dd>swapcapital@lebawi.net</dd>
                        <dt>Telegram Bot:</dt>
                        <dd>@Ramamanufacturing_bot</dd>
                        <dt>Telegram Chat ID:</dt>
                        <dd>317393086</dd>
                    </dl>
                </div>

                <form method="POST" action="">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-paper-plane"></i> Send Test Notifications
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 