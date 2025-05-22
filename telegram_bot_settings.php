<?php
require_once 'php_action/core.php';
require_once 'php_action/db_connect.php';
require_once 'php_action/telegram_bot_management.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    header('location: login.php');
    exit();
}

// Check permissions for Telegram bot management
$userId = $_SESSION['userId'];

// Define required permissions
$requiredPermissions = [
    'api.telegram.view',        // Basic view access
    'api.telegram.configure',   // Configure bot settings
    'api.telegram.manage',      // Manage bot operations
    'api.telegram.monitor'      // Monitor bot status and logs
];

// Check if user has any of the required permissions
$sql = "SELECT DISTINCT p.permission_name, p.description 
        FROM permissions p 
        JOIN role_permissions rp ON p.permission_id = rp.permission_id
        JOIN users u ON u.role_id = rp.role_id
        WHERE u.user_id = ? AND p.permission_name IN ('" . implode("','", $requiredPermissions) . "')";

$stmt = $connect->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

// Store user permissions for later use
$userPermissions = [];
while ($row = $result->fetch_assoc()) {
    $userPermissions[$row['permission_name']] = true;
}

// Check if user has at least view permission
if (!isset($userPermissions['api.telegram.view'])) {
    $_SESSION['error'] = "Access denied. You need appropriate permissions to view Telegram bot settings.";
    header('location: dashboard.php');
    exit();
}

// Set page title
$title = "Telegram Bot Settings";

// Define permission flags for UI control
$canConfigureBot = isset($userPermissions['api.telegram.configure']);
$canManageBot = isset($userPermissions['api.telegram.manage']);
$canMonitorBot = isset($userPermissions['api.telegram.monitor']);

include 'includes/header.php';
?>

<style>
    /* Table alignment styles */
    #botTable th:nth-child(1),
    #botTable td:nth-child(1),
    #templateTable th:nth-child(1),
    #templateTable td:nth-child(1),
    #logsTable th:nth-child(1),
    #logsTable td:nth-child(1) {
        text-align: right; /* Right align ID columns */
    }
    
    #botTable th:not(:nth-child(1)):not(:last-child),
    #botTable td:not(:nth-child(1)):not(:last-child),
    #templateTable th:not(:nth-child(1)):not(:last-child),
    #templateTable td:not(:nth-child(1)):not(:last-child),
    #logsTable th:not(:nth-child(1)),
    #logsTable td:not(:nth-child(1)) {
        text-align: left; /* Left align text columns */
    }
    
    #botTable th:last-child,
    #botTable td:last-child,
    #templateTable th:last-child,
    #templateTable td:last-child {
        text-align: center; /* Center align action columns */
    }
</style>

<div class="container-fluid" style="margin-top: 20px;">
    <div class="row">
        <div class="col-md-12">
            <ol class="breadcrumb">
                <li><a href="dashboard.php">Home</a></li>
                <li class="active">Telegram Bot Settings</li>
            </ol>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <div class="page-heading"> <i class="glyphicon glyphicon-send"></i> Telegram Bot Management</div>
                </div> <!-- /panel-heading -->
                <div class="panel-body">
                    <div class="remove-messages"></div>

                    <ul class="nav nav-tabs" role="tablist">
                        <li role="presentation" class="active"><a href="#botSettings" aria-controls="botSettings" role="tab" data-toggle="tab">Bot Settings</a></li>
                        <?php if ($canManageBot): ?>
                        <li role="presentation"><a href="#templates" aria-controls="templates" role="tab" data-toggle="tab">Notification Templates</a></li>
                        <?php endif; ?>
                        <?php if ($canMonitorBot): ?>
                        <li role="presentation"><a href="#logs" aria-controls="logs" role="tab" data-toggle="tab">Notification Logs</a></li>
                        <?php endif; ?>
                        <?php if ($canConfigureBot): ?>
                        <li role="presentation"><a href="telegram_notification_management.php"><i class="glyphicon glyphicon-cog"></i> Configure Notifications</a></li>
                        <?php endif; ?>
                    </ul>

                    <div class="tab-content" style="margin-top: 20px;">
                        <div role="tabpanel" class="tab-pane active" id="botSettings">
                            <!-- Bot Settings Panel -->
                            <?php if ($canConfigureBot): ?>
                            <div class="row">
                                <div class="col-md-12">
                                    <button class="btn btn-primary" data-toggle="modal" data-target="#addBotModal" id="addBotBtn">
                                        <i class="glyphicon glyphicon-plus-sign"></i> Add New Bot
                                    </button>
                                </div>
                            </div>
                            <?php endif; ?>
                            <br>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped" id="botTable">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Bot Name</th>
                                                    <th>Bot Token</th>
                                                    <th>Chat ID</th>
                                                    <th>Status</th>
                                                    <th>Created At</th>
                                                    <th>Created By</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="botTableBody">
                                                <!-- Bot data will be loaded here via AJAX -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($canManageBot): ?>
                        <div role="tabpanel" class="tab-pane" id="templates">
                            <!-- Templates Panel -->
                            <div class="row">
                                <div class="col-md-12">
                                    <button class="btn btn-primary" data-toggle="modal" data-target="#addTemplateModal" id="addTemplateBtn">
                                        <i class="glyphicon glyphicon-plus-sign"></i> Add New Template
                                    </button>
                                </div>
                            </div>
                            <br>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped" id="templateTable">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Template Name</th>
                                                    <th>Template Key</th>
                                                    <th>Status</th>
                                                    <th>Created At</th>
                                                    <th>Created By</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="templateTableBody">
                                                <!-- Template data will be loaded here via AJAX -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($canMonitorBot): ?>
                        <div role="tabpanel" class="tab-pane" id="logs">
                            <!-- Logs Panel -->
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped" id="logsTable">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Template</th>
                                                    <th>Message</th>
                                                    <th>Status</th>
                                                    <th>Sent At</th>
                                                    <th>Created At</th>
                                                </tr>
                                            </thead>
                                            <tbody id="logsTableBody">
                                                <!-- Logs data will be loaded here via AJAX -->
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <div class="text-center" id="loadMoreLogs">
                                        <button class="btn btn-default">Load More</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div> <!-- /panel-body -->
            </div> <!-- /panel -->
        </div> <!-- /col-md-12 -->
    </div> <!-- /row -->
</div> <!-- /container -->

<!-- Add Bot Modal -->
<div class="modal fade" id="addBotModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addBotForm" action="php_action/telegram_bot_management.php" method="post">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Add New Telegram Bot</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="botName">Bot Name</label>
                        <input type="text" class="form-control" id="botName" name="botName" placeholder="Enter bot name" required>
                    </div>
                    <div class="form-group">
                        <label for="botToken">Bot Token</label>
                        <input type="text" class="form-control" id="botToken" name="botToken" placeholder="Enter bot token" required>
                    </div>
                    <div class="form-group">
                        <label for="chatId">Chat ID</label>
                        <input type="text" class="form-control" id="chatId" name="chatId" placeholder="Enter chat ID" required>
                    </div>
                    <div class="form-group">
                        <label for="webhookUrl">Webhook URL (Optional)</label>
                        <input type="text" class="form-control" id="webhookUrl" name="webhookUrl" placeholder="Enter webhook URL (if any)">
                    </div>
                    <div class="form-group">
                        <button type="button" id="testBotConnection" class="btn btn-info">Test Connection</button>
                        <span id="testStatus"></span>
                    </div>
                    <input type="hidden" name="action" value="createBot">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Bot Modal -->
<div class="modal fade" id="editBotModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editBotForm" action="php_action/telegram_bot_management.php" method="post">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Edit Telegram Bot</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="editBotName">Bot Name</label>
                        <input type="text" class="form-control" id="editBotName" name="botName" placeholder="Enter bot name" required>
                    </div>
                    <div class="form-group">
                        <label for="editBotToken">Bot Token</label>
                        <input type="text" class="form-control" id="editBotToken" name="botToken" placeholder="Enter bot token" required>
                    </div>
                    <div class="form-group">
                        <label for="editChatId">Chat ID</label>
                        <input type="text" class="form-control" id="editChatId" name="chatId" placeholder="Enter chat ID" required>
                    </div>
                    <div class="form-group">
                        <label for="editWebhookUrl">Webhook URL (Optional)</label>
                        <input type="text" class="form-control" id="editWebhookUrl" name="webhookUrl" placeholder="Enter webhook URL (if any)">
                    </div>
                    <div class="form-group">
                        <label for="editIsActive">Status</label>
                        <select class="form-control" id="editIsActive" name="isActive">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="button" id="editTestBotConnection" class="btn btn-info">Test Connection</button>
                        <span id="editTestStatus"></span>
                    </div>
                    <input type="hidden" name="action" value="updateBot">
                    <input type="hidden" name="id" id="editBotId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Template Modal -->
<div class="modal fade" id="addTemplateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addTemplateForm" action="php_action/telegram_bot_management.php" method="post">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Add New Notification Template</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="templateName">Template Name</label>
                        <input type="text" class="form-control" id="templateName" name="templateName" placeholder="Enter template name" required>
                    </div>
                    <div class="form-group">
                        <label for="templateKey">Template Key</label>
                        <input type="text" class="form-control" id="templateKey" name="templateKey" placeholder="Enter template key (e.g., new_order)" required>
                    </div>
                    <div class="form-group">
                        <label for="messageTemplate">Message Template</label>
                        <textarea class="form-control" id="messageTemplate" name="messageTemplate" rows="5" placeholder="Enter message template with variables like {{variable_name}}" required></textarea>
                        <p class="help-block">Example: New order #{{order_id}} has been placed by {{customer_name}}.</p>
                    </div>
                    <input type="hidden" name="action" value="createTemplate">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Template Modal -->
<div class="modal fade" id="editTemplateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editTemplateForm" action="php_action/telegram_bot_management.php" method="post">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Edit Notification Template</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="editTemplateName">Template Name</label>
                        <input type="text" class="form-control" id="editTemplateName" name="templateName" placeholder="Enter template name" required>
                    </div>
                    <div class="form-group">
                        <label for="editTemplateKey">Template Key</label>
                        <input type="text" class="form-control" id="editTemplateKey" name="templateKey" placeholder="Enter template key (e.g., new_order)" required>
                    </div>
                    <div class="form-group">
                        <label for="editMessageTemplate">Message Template</label>
                        <textarea class="form-control" id="editMessageTemplate" name="messageTemplate" rows="5" placeholder="Enter message template with variables like {{variable_name}}" required></textarea>
                        <p class="help-block">Example: New order #{{order_id}} has been placed by {{customer_name}}.</p>
                    </div>
                    <div class="form-group">
                        <label for="editTemplateIsActive">Status</label>
                        <select class="form-control" id="editTemplateIsActive" name="isActive">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <input type="hidden" name="action" value="updateTemplate">
                    <input type="hidden" name="id" id="editTemplateId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Log Modal -->
<div class="modal fade" id="viewLogModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">View Notification Log</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Template</label>
                    <p id="logTemplate" class="form-control-static"></p>
                </div>
                <div class="form-group">
                    <label>Message</label>
                    <pre id="logMessage" class="well"></pre>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <p id="logStatus" class="form-control-static"></p>
                </div>
                <div class="form-group">
                    <label>Error</label>
                    <p id="logError" class="form-control-static"></p>
                </div>
                <div class="form-group">
                    <label>Sent At</label>
                    <p id="logSentAt" class="form-control-static"></p>
                </div>
                <div class="form-group">
                    <label>Created At</label>
                    <p id="logCreatedAt" class="form-control-static"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="deleteConfirmModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Confirm Delete</h4>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this item?</p>
                <p class="text-warning"><small>This cannot be undone.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="assets/js/telegram_bot.js"></script>

<!-- Pass permissions to JavaScript -->
<script>
    var userPermissions = {
        canConfigureBot: <?php echo json_encode($canConfigureBot); ?>,
        canManageBot: <?php echo json_encode($canManageBot); ?>,
        canMonitorBot: <?php echo json_encode($canMonitorBot); ?>
    };
</script> 