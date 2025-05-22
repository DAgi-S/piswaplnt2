<?php
require_once 'php_action/core.php';
require_once 'includes/header.php';

// Check if user has admin privileges
if (!isset($_SESSION['userRole']) || $_SESSION['userRole'] !== 'admin') {
    header('location: dashboard.php');
    exit();
}
?>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="fa fa-bell"></i> Notification Queue Monitor
                    <div class="pull-right">
                        <button type="button" class="btn btn-primary" id="processQueueBtn">
                            <i class="fa fa-play"></i> Process Queue
                        </button>
                        <button type="button" class="btn btn-warning" id="retryFailedBtn">
                            <i class="fa fa-refresh"></i> Retry Failed
                        </button>
                    </div>
                </div>
            </div>
            <div class="panel-body">
                <!-- Queue Statistics -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="small-box bg-primary">
                            <div class="inner">
                                <h3 id="pendingCount">0</h3>
                                <p>Pending Notifications</p>
                            </div>
                            <div class="icon">
                                <i class="fa fa-clock-o"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3 id="completedCount">0</h3>
                                <p>Completed</p>
                            </div>
                            <div class="icon">
                                <i class="fa fa-check"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3 id="processingCount">0</h3>
                                <p>Processing</p>
                            </div>
                            <div class="icon">
                                <i class="fa fa-refresh"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="small-box bg-danger">
                            <div class="inner">
                                <h3 id="failedCount">0</h3>
                                <p>Failed</p>
                            </div>
                            <div class="icon">
                                <i class="fa fa-times"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Queue Items Table -->
                <div class="row">
                    <div class="col-md-12">
                        <table id="queueTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Type</th>
                                    <th>User</th>
                                    <th>Channel</th>
                                    <th>Status</th>
                                    <th>Attempts</th>
                                    <th>Created</th>
                                    <th>Last Attempt</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Error Details Modal -->
<div class="modal fade" id="errorModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Error Details</h4>
            </div>
            <div class="modal-body">
                <pre id="errorDetails"></pre>
            </div>
        </div>
    </div>
</div>

<!-- Custom Styles -->
<style>
.small-box {
    border-radius: 4px;
    position: relative;
    display: block;
    margin-bottom: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,0.1);
    padding: 20px;
}
.small-box .inner {
    padding: 10px;
}
.small-box h3 {
    font-size: 38px;
    margin: 0 0 10px 0;
    white-space: nowrap;
    color: #fff;
}
.small-box p {
    font-size: 15px;
    color: #fff;
}
.bg-primary { background-color: #3c8dbc !important; }
.bg-success { background-color: #00a65a !important; }
.bg-warning { background-color: #f39c12 !important; }
.bg-danger { background-color: #dd4b39 !important; }
</style>

<!-- Include custom JS -->
<script src="custom/js/notification_queue_monitor.js"></script>

<?php require_once 'includes/footer.php'; ?> 