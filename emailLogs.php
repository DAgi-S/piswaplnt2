<?php 
require_once 'php_action/db_connect.php';
require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Home</a></li>
            <li class="active">Email Logs</li>
        </ol>

        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading"> <i class="glyphicon glyphicon-list"></i> Email Logs</div>
            </div>
            <div class="panel-body">
                <table class="table" id="emailLogsTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Quotation #</th>
                            <th>Sent To</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Error Message</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#emailLogsTable').DataTable({
        'ajax': 'php_action/fetchEmailLogs.php',
        'order': [[0, 'desc']],
        'columns': [
            { data: 'sent_at' },
            { data: 'quotation_number' },
            { data: 'sent_to' },
            { data: 'subject' },
            { 
                data: 'status',
                render: function(data) {
                    if(data == 1) {
                        return '<span class="label label-success">Success</span>';
                    }
                    return '<span class="label label-danger">Failed</span>';
                }
            },
            { data: 'error_message' }
        ]
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 