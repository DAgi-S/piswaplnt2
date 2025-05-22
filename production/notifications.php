<?php
require_once 'php_action/core.php';
require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="fa fa-bell"></i> Notifications
                    <span class="badge" id="unreadCount">0</span>
                </div>
            </div>
            <div class="panel-body">
                <div class="notifications-container">
                    <!-- Notifications will be loaded here -->
                </div>
                <div id="loadingNotifications" class="text-center" style="display: none;">
                    <i class="fa fa-spinner fa-spin"></i> Loading notifications...
                </div>
                <div id="noNotifications" class="text-center text-muted" style="display: none;">
                    <p>No notifications to display</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/handlebars.js/4.7.7/handlebars.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>

<!-- Notification Template -->
<script id="notification-template" type="text/template">
    <div class="notification-item {{#unless is_read}}unread{{/unless}}" data-notification-id="{{notification_id}}">
        <div class="notification-icon">
            <i class="fa {{icon}}"></i>
        </div>
        <div class="notification-content">
            <div class="notification-title">{{title}}</div>
            <div class="notification-message">{{message}}</div>
            <div class="notification-time">{{created_at}}</div>
        </div>
    </div>
</script>

<!-- Custom CSS -->
<style>
.notifications-container {
    max-height: 600px;
    overflow-y: auto;
}

.notification-item {
    padding: 15px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
    transition: background-color 0.2s;
    display: flex;
    align-items: flex-start;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-item.unread {
    background-color: #e8f4fe;
}

.notification-icon {
    margin-right: 15px;
    width: 30px;
    text-align: center;
}

.notification-icon i {
    font-size: 20px;
    color: #007bff;
}

.notification-content {
    flex: 1;
}

.notification-title {
    font-weight: bold;
    margin-bottom: 5px;
}

.notification-message {
    color: #666;
    margin-bottom: 5px;
}

.notification-time {
    font-size: 0.85em;
    color: #999;
}

#unreadCount {
    background-color: #dc3545;
    margin-left: 5px;
}
</style>

<!-- Custom JavaScript -->
<script src="../custom/js/notifications.js"></script>

<?php require_once 'includes/footer.php'; ?> 