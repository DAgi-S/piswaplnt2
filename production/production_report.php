<?php 
require_once 'includes/header.php';

// Initialize filter variables
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$status = isset($_GET['status']) ? $_GET['status'] : 'all';

// Base query for production orders
$query = "
    SELECT 
        po.*,
        p.name as product_name,
        COALESCE(pp.completed_quantity, 0) as actual_completed,
        u.username as created_by_name
    FROM production_orders po
    LEFT JOIN products p ON po.product_id = p.product_id
    LEFT JOIN (
        SELECT production_order_id, SUM(quantity) as completed_quantity 
        FROM production_progress 
        GROUP BY production_order_id
    ) pp ON po.id = pp.production_order_id
    LEFT JOIN users u ON po.created_by = u.user_id
    WHERE po.start_date BETWEEN ? AND ?";

if ($status !== 'all') {
    $query .= " AND po.status = ?";
}
$query .= " ORDER BY po.start_date DESC";

// Prepare and execute the query
$stmt = $connect->prepare($query);

if ($status !== 'all') {
    $stmt->bind_param("sss", $start_date, $end_date, $status);
} else {
    $stmt->bind_param("ss", $start_date, $end_date);
}

$stmt->execute();
$result = $stmt->get_result();

// Calculate summary statistics
$total_orders = 0;
$completed_orders = 0;
$in_progress_orders = 0;
$delayed_orders = 0;
$total_quantity = 0;
$completed_quantity = 0;

while ($row = $result->fetch_assoc()) {
    $total_orders++;
    $total_quantity += $row['target_quantity'];
    $completed_quantity += $row['actual_completed'];
    
    if ($row['status'] === 'completed') {
        $completed_orders++;
    } elseif ($row['status'] === 'in_progress') {
        $in_progress_orders++;
        if (strtotime($row['expected_completion_date']) < time()) {
            $delayed_orders++;
        }
    }
}

// Reset result pointer
$result->data_seek(0);
?>

<div class="row">
    <div class="col-md-12">
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Home</a></li>
            <li class="active">Production Report</li>
        </ol>

        <!-- Filters -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-filter"></i> Filter Report</h3>
            </div>
            <div class="panel-body">
                <form method="GET" class="form-horizontal">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="col-sm-4 control-label">Start Date</label>
                                <div class="col-sm-8">
                                    <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="col-sm-4 control-label">End Date</label>
                                <div class="col-sm-8">
                                    <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="col-sm-4 control-label">Status</label>
                                <div class="col-sm-8">
                                    <select name="status" class="form-control">
                                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 text-center">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                            <a href="production_report.php" class="btn btn-default">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="row">
            <div class="col-md-3">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-xs-3">
                                <i class="fa fa-tasks fa-4x"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div class="huge"><?php echo $total_orders; ?></div>
                                <div>Total Orders</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel panel-green">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-xs-3">
                                <i class="fa fa-check-circle fa-4x"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div class="huge"><?php echo $completed_orders; ?></div>
                                <div>Completed Orders</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel panel-yellow">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-xs-3">
                                <i class="fa fa-spinner fa-4x"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div class="huge"><?php echo $in_progress_orders; ?></div>
                                <div>In Progress</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel panel-red">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-xs-3">
                                <i class="fa fa-warning fa-4x"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div class="huge"><?php echo $delayed_orders; ?></div>
                                <div>Delayed Orders</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Production Orders Table -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-md-6">
                        <h3 class="panel-title"><i class="fa fa-list"></i> Production Orders</h3>
                    </div>
                    <div class="col-md-6 text-right">
                        <button onclick="exportToPDF()" class="btn btn-danger btn-sm"><i class="fa fa-file-pdf-o"></i> Export PDF</button>
                        <button onclick="exportToExcel()" class="btn btn-success btn-sm"><i class="fa fa-file-excel-o"></i> Export Excel</button>
                    </div>
                </div>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Product</th>
                                <th>Target Qty</th>
                                <th>Completed Qty</th>
                                <th>Progress</th>
                                <th>Start Date</th>
                                <th>Expected Completion</th>
                                <th>Status</th>
                                <th>Created By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($order = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $order['order_number']; ?></td>
                                    <td><?php echo $order['product_name']; ?></td>
                                    <td><?php echo number_format($order['target_quantity']); ?></td>
                                    <td><?php echo number_format($order['actual_completed']); ?></td>
                                    <td>
                                        <?php 
                                        $progress = ($order['actual_completed'] / $order['target_quantity']) * 100;
                                        $progress_class = $progress < 50 ? 'danger' : ($progress < 80 ? 'warning' : 'success');
                                        ?>
                                        <div class="progress">
                                            <div class="progress-bar progress-bar-<?php echo $progress_class; ?>" 
                                                 role="progressbar" 
                                                 aria-valuenow="<?php echo $progress; ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100" 
                                                 style="width: <?php echo $progress; ?>%">
                                                <?php echo round($progress); ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($order['start_date'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($order['expected_completion_date'])); ?></td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        switch($order['status']) {
                                            case 'completed':
                                                $status_class = 'success';
                                                break;
                                            case 'in_progress':
                                                $status_class = 'primary';
                                                break;
                                            case 'pending':
                                                $status_class = 'warning';
                                                break;
                                            case 'cancelled':
                                                $status_class = 'danger';
                                                break;
                                        }
                                        ?>
                                        <span class="label label-<?php echo $status_class; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $order['created_by_name']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom CSS -->
<style>
    .huge {
        font-size: 40px;
    }
    .panel-green {
        border-color: #5cb85c;
    }
    .panel-green .panel-heading {
        border-color: #5cb85c;
        color: white;
        background-color: #5cb85c;
    }
    .panel-yellow {
        border-color: #f0ad4e;
    }
    .panel-yellow .panel-heading {
        border-color: #f0ad4e;
        color: white;
        background-color: #f0ad4e;
    }
    .panel-red {
        border-color: #d9534f;
    }
    .panel-red .panel-heading {
        border-color: #d9534f;
        color: white;
        background-color: #d9534f;
    }
    .progress {
        margin-bottom: 0;
    }
</style>

<script src="js/production_report.js"></script>

<?php 
$stmt->close();
require_once 'includes/footer.php'; 
?> 