<?php
require_once 'php_action/core.php';
require_once 'php_action/db_connect.php';
require_once 'php_action/telegram_bot_management.php';

// Check if user is logged in and has permission
if (!isset($_SESSION['userId'])) {
    header('location: login.php');
    exit();
}

// Check for admin permission
$sql = "SELECT p.permission_name FROM permissions p 
        JOIN role_permissions rp ON p.permission_id = rp.permission_id
        JOIN users u ON u.role_id = rp.role_id
        WHERE u.user_id = ? AND p.permission_name = 'manage_telegram_bot'";

$stmt = $connect->prepare($sql);
$stmt->bind_param("i", $_SESSION['userId']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header('location: dashboard.php');
    exit();
}

// Process form submission for saving notification settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = isset($_POST['notification_settings']) ? $_POST['notification_settings'] : [];
    
    // Start transaction
    $connect->begin_transaction();
    
    try {
        // Delete existing settings
        $connect->query("DELETE FROM telegram_notification_settings");
        
        // Insert new settings
        if (!empty($settings)) {
            $stmt = $connect->prepare("INSERT INTO telegram_notification_settings (event_key, template_id, is_active) VALUES (?, ?, ?)");
            
            foreach ($settings as $eventKey => $setting) {
                // Only insert if a template is selected and template_id is not empty
                if (isset($setting['template_id']) && !empty($setting['template_id'])) {
                    $templateId = $setting['template_id'];
                    $isActive = isset($setting['is_active']) ? 1 : 0;
                    $stmt->bind_param("sii", $eventKey, $templateId, $isActive);
                    $stmt->execute();
                }
            }
        }
        
        // If we get here, commit the transaction
        $connect->commit();
        $successMessage = "Notification settings saved successfully";
    } catch (Exception $e) {
        // If there's an error, rollback the transaction
        $connect->rollback();
        $errorMessage = "Error saving settings: " . $e->getMessage();
    }
}

// Set page title and include header
$title = "Telegram Notification Management";
include 'includes/header.php';
?>

<style>
    /* Space optimization styles */
    .panel-primary {
        margin-bottom: 10px;
    }
    .panel-body {
        padding: 15px;
    }
    .category-grid {
        display: flex;
        flex-wrap: wrap;
        margin: -10px;
        padding: 10px;
    }
    .category-item {
        flex: 0 0 25%;
        padding: 10px;
    }
    .category-card {
        border: 1px solid #337ab7;
        border-radius: 4px;
        background: #fff;
        transition: all 0.3s ease;
        height: 100%;
    }
    .category-card:hover {
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    .category-header {
        background: #337ab7;
        color: white;
        padding: 10px 15px;
        border-radius: 3px 3px 0 0;
        cursor: pointer;
    }
    .category-header h3 {
        margin: 0;
        font-size: 14px;
        font-weight: bold;
    }
    .category-content {
        display: none;
        padding: 15px;
    }
    .notification-grid {
        display: flex;
        flex-wrap: wrap;
        margin: -10px;
    }
    .notification-item {
        flex: 0 0 25%;
        padding: 10px;
    }
    .notification-card {
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 15px;
        height: 100%;
        background: #fff;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .notification-title {
        font-weight: bold;
        margin-bottom: 10px;
        font-size: 13px;
    }
    .notification-select {
        margin-bottom: 10px;
    }
    .notification-toggle {
        text-align: center;
    }
    @media (max-width: 1200px) {
        .category-item, .notification-item {
            flex: 0 0 33.333%;
        }
    }
    @media (max-width: 992px) {
        .category-item, .notification-item {
            flex: 0 0 50%;
        }
    }
    @media (max-width: 576px) {
        .category-item, .notification-item {
            flex: 0 0 100%;
        }
    }
</style>

<div class="container-fluid" style="margin-top: 20px;">
    <div class="row">
        <div class="col-md-12">
            <ol class="breadcrumb">
                <li><a href="dashboard.php">Home</a></li>
                <li><a href="telegram_bot_settings.php">Telegram Bot Settings</a></li>
                <li class="active">Notification Management</li>
            </ol>

            <?php if (isset($successMessage)): ?>
            <div class="alert alert-success">
                <i class="glyphicon glyphicon-ok-circle"></i> <?php echo $successMessage; ?>
            </div>
            <?php endif; ?>

            <?php if (isset($errorMessage)): ?>
            <div class="alert alert-danger">
                <i class="glyphicon glyphicon-exclamation-sign"></i> <?php echo $errorMessage; ?>
            </div>
            <?php endif; ?>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <div class="page-heading"> <i class="glyphicon glyphicon-bell"></i> Telegram Notification Management</div>
                </div>
                <div class="panel-body">
                    <p>Configure which events in the system should trigger Telegram notifications and which templates to use.</p>
                    
                    <form action="" method="post" id="notificationSettingsForm">
                        <div class="row">
                            <div class="col-md-12 text-right" style="margin-bottom: 10px;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="glyphicon glyphicon-floppy-disk"></i> Save Settings
                                </button>
                                <a href="telegram_bot_settings.php" class="btn btn-default">
                                    <i class="glyphicon glyphicon-arrow-left"></i> Back
                                </a>
                            </div>
                        </div>
                        
                        <?php
                        // Get all notification templates
                        $templates = [];
                        $templatesResult = $connect->query("SELECT id, template_name, template_key FROM telegram_notification_templates WHERE is_active = 1 ORDER BY template_name");
                        if ($templatesResult) {
                            while ($row = $templatesResult->fetch_assoc()) {
                                $templates[$row['id']] = $row;
                            }
                        }
                        
                        // Get current notification settings
                        $currentSettings = [];
                        $settingsResult = $connect->query("SELECT * FROM telegram_notification_settings");
                        if ($settingsResult) {
                            while ($row = $settingsResult->fetch_assoc()) {
                                $currentSettings[$row['event_key']] = [
                                    'template_id' => $row['template_id'],
                                    'is_active' => $row['is_active']
                                ];
                            }
                        }
                        
                        // Define event groups
                        $eventGroups = [
                            'Digital Swap' => [
                                'digital_swap_create' => 'Digital Swap Created',
                                'digital_swap_approve' => 'Digital Swap Approved',
                                'digital_swap_reject' => 'Digital Swap Rejected'
                            ],
                            'Sales' => [
                                'sale_create' => 'New Sale Created',
                                'sale_payment' => 'Sale Payment Received',
                                'sale_complete' => 'Sale Completed',
                                'sale_return' => 'Sale Return Created'
                            ],
                            'Orders' => [
                                'order_create' => 'New Order Created',
                                'order_status_change' => 'Order Status Changed',
                                'order_shipped' => 'Order Shipped',
                                'order_delivered' => 'Order Delivered',
                                'order_cancelled' => 'Order Cancelled'
                            ],
                            'Purchases' => [
                                'purchase_create' => 'New Purchase Created',
                                'purchase_payment' => 'Purchase Payment Made',
                                'purchase_receive' => 'Purchase Received',
                                'purchase_return' => 'Purchase Return Created'
                            ],
                            'Quotations' => [
                                'quotation_create' => 'New Quotation Created',
                                'quotation_approved' => 'Quotation Approved',
                                'quotation_rejected' => 'Quotation Rejected',
                                'quotation_expired' => 'Quotation Expired'
                            ],
                            'GPS Letter' => [
                                'gps_letter_create' => 'GPS Letter Created',
                                'gps_letter_sent' => 'GPS Letter Sent'
                            ],
                            'Production' => [
                                'production_order_create' => 'Production Order Created',
                                'raw_material_purchase' => 'Raw Material Purchase',
                                'production_complete' => 'Production Completed',
                                'production_quality_issue' => 'Production Quality Issue'
                            ],
                            'POS' => [
                                'pos_sale' => 'POS Sale',
                                'pos_cash_drawer' => 'Cash Drawer Operation',
                                'pos_shift_change' => 'POS Shift Change'
                            ],
                            'User Sessions' => [
                                'user_login' => 'User Login',
                                'user_logout' => 'User Logout',
                                'login_failed' => 'Failed Login Attempt',
                                'session_terminated' => 'Session Terminated by Admin'
                            ],
                            'Reports' => [
                                'report_generated' => 'Report Generated',
                                'financial_alert' => 'Financial Alert'
                            ],
                            'Email' => [
                                'email_sent' => 'Email Sent',
                                'email_campaign' => 'Email Campaign Sent',
                                'email_error' => 'Email Error'
                            ],
                            'Inventory' => [
                                'stock_low' => 'Stock Low Alert',
                                'stock_adjustment' => 'Stock Adjustment',
                                'inventory_count' => 'Inventory Count Completed'
                            ],
                            'System' => [
                                'system_error' => 'System Error',
                                'backup_complete' => 'System Backup Completed',
                                'critical_alert' => 'Critical System Alert'
                            ]
                        ];
                        
                        // Display event groups and settings
                        ?>
                        <div class="category-grid">
                            <?php foreach ($eventGroups as $groupName => $events): ?>
                            <div class="category-item">
                                <div class="category-card">
                                    <div class="category-header" data-target="#group-<?php echo str_replace(' ', '-', strtolower($groupName)); ?>">
                                        <h3>
                                            <i class="glyphicon glyphicon-plus-sign"></i> <?php echo $groupName; ?>
                                        </h3>
                                    </div>
                                    <div class="category-content" id="group-<?php echo str_replace(' ', '-', strtolower($groupName)); ?>">
                                        <div class="notification-grid">
                                            <?php foreach ($events as $eventKey => $eventName): ?>
                                            <div class="notification-item">
                                                <div class="notification-card">
                                                    <div class="notification-title">
                                                        <?php echo $eventName; ?>
                                                    </div>
                                                    <div class="notification-select">
                                                        <select name="notification_settings[<?php echo $eventKey; ?>][template_id]" class="form-control input-sm">
                                                            <option value="">-- Select Template --</option>
                                                            <?php foreach ($templates as $tpl): ?>
                                                            <option value="<?php echo $tpl['id']; ?>" <?php echo (isset($currentSettings[$eventKey]) && $currentSettings[$eventKey]['template_id'] == $tpl['id']) ? 'selected' : ''; ?>>
                                                                <?php echo $tpl['template_name']; ?> (<?php echo $tpl['template_key']; ?>)
                                                            </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="notification-toggle">
                                                        <div class="checkbox" style="margin: 0;">
                                                            <label>
                                                                <input type="checkbox" name="notification_settings[<?php echo $eventKey; ?>][is_active]" value="1" <?php echo (isset($currentSettings[$eventKey]) && $currentSettings[$eventKey]['is_active']) ? 'checked' : ''; ?>>
                                                                <span class="label <?php echo (isset($currentSettings[$eventKey]) && $currentSettings[$eventKey]['is_active']) ? 'label-success' : 'label-default'; ?>">
                                                                    <?php echo (isset($currentSettings[$eventKey]) && $currentSettings[$eventKey]['is_active']) ? 'Enabled' : 'Disabled'; ?>
                                                                </span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="text-center" style="margin-top: 20px;">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="glyphicon glyphicon-floppy-disk"></i> Save Notification Settings
                            </button>
                            <a href="telegram_bot_settings.php" class="btn btn-default btn-lg">
                                <i class="glyphicon glyphicon-arrow-left"></i> Back to Bot Settings
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Toggle category sections
    $('.category-header').click(function() {
        var target = $(this).data('target');
        $(target).slideToggle('fast');
        $(this).find('.glyphicon').toggleClass('glyphicon-plus-sign glyphicon-minus-sign');
    });
    
    // Show Digital Swap section by default
    $('#group-digital-swap').show();
    $('#group-digital-swap').closest('.category-card').find('.glyphicon').toggleClass('glyphicon-plus-sign glyphicon-minus-sign');
    
    // Handle checkbox changes to update status label
    $('input[type="checkbox"]').change(function() {
        var statusLabel = $(this).siblings('.label');
        if ($(this).is(':checked')) {
            statusLabel.removeClass('label-default').addClass('label-success');
            statusLabel.text('Enabled');
        } else {
            statusLabel.removeClass('label-success').addClass('label-default');
            statusLabel.text('Disabled');
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?> 