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
                    <i class="fa fa-archive"></i> Notification Archives
                    <div class="pull-right">
                        <button type="button" class="btn btn-success" id="exportBtn">
                            <i class="fa fa-download"></i> Export CSV
                        </button>
                        <button type="button" class="btn btn-danger" id="cleanupBtn">
                            <i class="fa fa-trash"></i> Cleanup Old Archives
                        </button>
                    </div>
                </div>
            </div>
            <div class="panel-body">
                <!-- Statistics Cards -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="small-box bg-primary">
                            <div class="inner">
                                <h3 id="totalNotifications">0</h3>
                                <p>Total Notifications</p>
                            </div>
                            <div class="icon">
                                <i class="fa fa-bell"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3 id="deliveredCount">0</h3>
                                <p>Delivered</p>
                            </div>
                            <div class="icon">
                                <i class="fa fa-check"></i>
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
                    <div class="col-md-3">
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3 id="uniqueUsers">0</h3>
                                <p>Unique Users</p>
                            </div>
                            <div class="icon">
                                <i class="fa fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="filter-section">
                            <form id="archiveFilterForm" class="form-inline">
                                <div class="form-group">
                                    <label>Date Range:</label>
                                    <select class="form-control" name="dateRange">
                                        <option value="7d">Last 7 Days</option>
                                        <option value="30d" selected>Last 30 Days</option>
                                        <option value="90d">Last 90 Days</option>
                                        <option value="1y">Last Year</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Type:</label>
                                    <select class="form-control" name="type">
                                        <option value="">All Types</option>
                                        <option value="inventory">Inventory</option>
                                        <option value="order">Order</option>
                                        <option value="system">System</option>
                                        <option value="financial">Financial</option>
                                        <option value="quality">Quality</option>
                                        <option value="maintenance">Maintenance</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Status:</label>
                                    <select class="form-control" name="status">
                                        <option value="">All Status</option>
                                        <option value="delivered">Delivered</option>
                                        <option value="failed">Failed</option>
                                        <option value="expired">Expired</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Analytics Charts -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h3 class="panel-title">Notifications by Type</h3>
                            </div>
                            <div class="panel-body">
                                <canvas id="notificationTypeChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h3 class="panel-title">Delivery Success Rate</h3>
                            </div>
                            <div class="panel-body">
                                <canvas id="deliverySuccessChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Archives Table -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <table id="archivesTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Type</th>
                                    <th>Title</th>
                                    <th>User</th>
                                    <th>Channel</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Delivered</th>
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

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Notification Details</h4>
            </div>
            <div class="modal-body">
                <div id="notificationDetails"></div>
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
.filter-section {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
}
.filter-section .form-group {
    margin-right: 15px;
}
.mt-4 {
    margin-top: 20px;
}
</style>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Include custom JS -->
<script src="custom/js/notification_archives.js"></script>

<?php require_once 'includes/footer.php'; ?> 