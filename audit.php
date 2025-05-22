<?php 
require_once 'php_action/core.php';
require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Home</a></li>
            <li class="active">Audit Log</li>
        </ol>

        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="glyphicon glyphicon-list-alt"></i> System Audit Log
                </div>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-12">
                        <!-- Filter Section -->
                        <div class="form-group">
                            <div class="col-md-6">
                                <label>Date Range</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="daterange" name="daterange">
                                    <span class="input-group-addon">
                                        <i class="glyphicon glyphicon-calendar"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label>Activity Type</label>
                                <select class="form-control" id="activityType">
                                    <option value="">All Activities</option>
                                    <option value="purchase">Purchases</option>
                                    <option value="sales">Sales</option>
                                    <option value="quotation">Quotations</option>
                                    <option value="stock">Stock Changes</option>
                                    <option value="price">Price Updates</option>
                                    <option value="product">Product Updates</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row" style="margin-top: 15px;">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-primary" id="filterAudit">
                            <i class="glyphicon glyphicon-search"></i> Filter
                        </button>
                        <button type="button" class="btn btn-default" id="resetFilter">
                            <i class="glyphicon glyphicon-refresh"></i> Reset
                        </button>
                    </div>
                </div>
                <div class="row" style="margin-top: 15px;">
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table" id="auditTable">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>User</th>
                                        <th>Activity Type</th>
                                        <th>Description</th>
                                        <th>Old Value</th>
                                        <th>New Value</th>
                                        <th>IP Address</th>
                                        <th>Reference ID</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script type="text/javascript">
$(document).ready(function() {
    // Initialize date range picker
    $('#daterange').daterangepicker({
        startDate: moment().startOf('month'),
        endDate: moment().endOf('month'),
        opens: 'left',
        ranges: {
           'Today': [moment(), moment()],
           'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
           'Last 7 Days': [moment().subtract(6, 'days'), moment()],
           'Last 30 Days': [moment().subtract(29, 'days'), moment()],
           'This Month': [moment().startOf('month'), moment().endOf('month')],
           'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        },
        locale: {
            format: 'YYYY-MM-DD'
        }
    });

    // Initialize DataTable
    var auditTable = $('#auditTable').DataTable({
        'ajax': {
            'url': 'php_action/fetchAuditLog.php',
            'type': 'POST',
            'data': function(d) {
                return {
                    draw: d.draw,
                    start: d.start,
                    length: d.length,
                    daterange: $('#daterange').val(),
                    activityType: $('#activityType').val()
                };
            }
        },
        'processing': true,
        'serverSide': true,
        'pageLength': 25,
        'order': [[0, 'desc']],
        'columns': [
            {data: 'timestamp'},
            {data: 'username'},
            {data: 'activity_type'},
            {data: 'description'},
            {data: 'old_value'},
            {data: 'new_value'},
            {data: 'ip_address'},
            {data: 'reference_id'}
        ],
        'dom': 'lBfrtip',
        'buttons': ['copy', 'csv', 'excel', 'pdf', 'print']
    });

    // Filter button click handler
    $('#filterAudit').on('click', function() {
        auditTable.ajax.reload();
    });

    // Reset filter button click handler
    $('#resetFilter').on('click', function() {
        $('#daterange').data('daterangepicker').setStartDate(moment().startOf('month'));
        $('#daterange').data('daterangepicker').setEndDate(moment().endOf('month'));
        $('#activityType').val('');
        auditTable.ajax.reload();
    });

    // Apply date range filter automatically when date changes
    $('#daterange').on('apply.daterangepicker', function(ev, picker) {
        auditTable.ajax.reload();
    });
});
</script> 