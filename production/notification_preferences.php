<?php
require_once 'php_action/core.php';
require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="fa fa-bell"></i> Notification Preferences
                </div>
            </div>
            <div class="panel-body">
                <div id="response"></div>
                <form id="notificationPreferencesForm">
                    <!-- Notification Channels -->
                    <div class="section-header">
                        <h4>Notification Channels</h4>
                        <p class="text-muted">Choose how you want to receive notifications</p>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email Notifications</label>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="channels[email]" value="1"> Enable email notifications
                                    </label>
                                    <button type="button" class="btn btn-xs btn-info" id="testEmailBtn">
                                        <i class="fa fa-envelope"></i> Test Email
                                    </button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Browser Notifications</label>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="channels[browser]" value="1"> Enable browser notifications
                                    </label>
                                    <button type="button" class="btn btn-xs btn-info" id="testBrowserBtn">
                                        <i class="fa fa-desktop"></i> Test Browser
                                    </button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>SMS Notifications</label>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="channels[sms]" value="1"> Enable SMS notifications
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Types -->
                    <div class="section-header">
                        <h4>Notification Types</h4>
                        <p class="text-muted">Select which types of notifications you want to receive</p>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <!-- Inventory Notifications -->
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h4 class="panel-title">Inventory Notifications</h4>
                                </div>
                                <div class="panel-body">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="types[inventory_low]" value="1"> Low stock alerts
                                        </label>
                                    </div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="types[restock]" value="1"> Restock notifications
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Order Notifications -->
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h4 class="panel-title">Order Notifications</h4>
                                </div>
                                <div class="panel-body">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="types[order_new]" value="1"> New orders
                                        </label>
                                    </div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="types[order_status]" value="1"> Order status changes
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <!-- System Notifications -->
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h4 class="panel-title">System Notifications</h4>
                                </div>
                                <div class="panel-body">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="types[system_maintenance]" value="1"> System maintenance
                                        </label>
                                    </div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="types[system_updates]" value="1"> System updates
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Security Notifications -->
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h4 class="panel-title">Security Notifications</h4>
                                </div>
                                <div class="panel-body">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="types[security_login]" value="1"> Login attempts
                                        </label>
                                    </div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="types[security_changes]" value="1"> Account changes
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Schedule -->
                    <div class="section-header">
                        <h4>Notification Schedule</h4>
                        <p class="text-muted">Set your preferred notification timing</p>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Quiet Hours</label>
                                <div class="row">
                                    <div class="col-xs-6">
                                        <input type="time" class="form-control" name="quiet_hours_start" placeholder="Start Time">
                                    </div>
                                    <div class="col-xs-6">
                                        <input type="time" class="form-control" name="quiet_hours_end" placeholder="End Time">
                                    </div>
                                </div>
                                <small class="text-muted">During quiet hours, only critical notifications will be sent</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Save Preferences</button>
                        <button type="button" class="btn btn-info" id="previewBtn">Preview Notifications</button>
                    </div>
                </form>

                <!-- Preview Modal -->
                <div class="modal fade" id="previewModal" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                <h4 class="modal-title">Notification Preview</h4>
                            </div>
                            <div class="modal-body">
                                <!-- Preview Controls -->
                                <div class="preview-controls mb-3">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Notification Type</label>
                                                <select class="form-control" id="previewType">
                                                    <option value="inventory_low">Low Stock Alert</option>
                                                    <option value="order_new">New Order</option>
                                                    <option value="system_maintenance">System Maintenance</option>
                                                    <option value="security_login">Security Alert</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Priority</label>
                                                <select class="form-control" id="previewPriority">
                                                    <option value="low">Low</option>
                                                    <option value="medium">Medium</option>
                                                    <option value="high">High</option>
                                                    <option value="critical">Critical</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Preview Tabs -->
                                <ul class="nav nav-tabs">
                                    <li class="active"><a href="#inAppPreview" data-toggle="tab">In-App</a></li>
                                    <li><a href="#emailPreview" data-toggle="tab">Email</a></li>
                                    <li><a href="#smsPreview" data-toggle="tab">SMS</a></li>
                                    <li><a href="#browserPreview" data-toggle="tab">Browser</a></li>
                                </ul>

                                <!-- Preview Content -->
                                <div class="tab-content preview-content">
                                    <!-- In-App Preview -->
                                    <div class="tab-pane active" id="inAppPreview">
                                        <div class="notification-preview in-app">
                                            <div class="notification-item">
                                                <div class="notification-icon">
                                                    <i class="fa fa-bell"></i>
                                                </div>
                                                <div class="notification-content">
                                                    <div class="notification-title"></div>
                                                    <div class="notification-message"></div>
                                                    <div class="notification-time">Just now</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Email Preview -->
                                    <div class="tab-pane" id="emailPreview">
                                        <div class="notification-preview email">
                                            <div class="email-header">
                                                <div><strong>From:</strong> Production Management System</div>
                                                <div><strong>Subject:</strong> <span class="email-subject"></span></div>
                                            </div>
                                            <div class="email-body">
                                                <div class="email-content"></div>
                                                <div class="email-footer">
                                                    <p>Best regards,<br>Production Management System</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- SMS Preview -->
                                    <div class="tab-pane" id="smsPreview">
                                        <div class="notification-preview sms">
                                            <div class="sms-content"></div>
                                        </div>
                                    </div>

                                    <!-- Browser Preview -->
                                    <div class="tab-pane" id="browserPreview">
                                        <div class="notification-preview browser">
                                            <div class="browser-notification">
                                                <div class="browser-icon">
                                                    <i class="fa fa-bell"></i>
                                                </div>
                                                <div class="browser-content">
                                                    <div class="browser-title"></div>
                                                    <div class="browser-message"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Styles -->
                <style>
                .section-header {
                    margin: 30px 0 20px;
                    padding-bottom: 10px;
                    border-bottom: 1px solid #eee;
                }
                .section-header h4 {
                    margin-top: 0;
                    margin-bottom: 5px;
                }
                .panel-title {
                    font-size: 14px;
                    margin: 0;
                }
                .checkbox {
                    margin-top: 5px;
                    margin-bottom: 5px;
                }
                .preview-controls {
                    margin-bottom: 20px;
                }
                .notification-preview {
                    padding: 20px;
                    background: #f8f9fa;
                    border-radius: 4px;
                    margin-top: 15px;
                }
                .email-header {
                    background: #f8f9fa;
                    padding: 10px;
                    border: 1px solid #ddd;
                    border-radius: 4px 4px 0 0;
                }
                .email-body {
                    padding: 15px;
                    border: 1px solid #ddd;
                    border-top: none;
                    border-radius: 0 0 4px 4px;
                    background: white;
                }
                .email-footer {
                    margin-top: 20px;
                    padding-top: 20px;
                    border-top: 1px solid #eee;
                }
                .sms-content {
                    background: #e5e5ea;
                    color: #000;
                    padding: 10px 15px;
                    border-radius: 20px;
                    max-width: 80%;
                    margin: 10px;
                }
                .browser-notification {
                    background: white;
                    padding: 15px;
                    border-radius: 4px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    display: flex;
                    align-items: flex-start;
                }
                .browser-icon {
                    margin-right: 10px;
                }
                .browser-content {
                    flex: 1;
                }
                .browser-title {
                    font-weight: bold;
                    margin-bottom: 5px;
                }
                </style>
            </div>
        </div>
    </div>
</div>

<!-- Include Toastr -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>

<!-- Initialize Toastr -->
<script>
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "timeOut": "5000"
    };
</script>

<!-- Custom JavaScript -->
<script src="custom/js/notification_preferences.js"></script>

<?php require_once 'includes/footer.php'; ?> 