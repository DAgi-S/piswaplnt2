<?php 
require_once 'php_action/db_connect.php';
require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Home</a></li>
            <li><a href="manageQuotations.php">Manage Quotations</a></li>
            <li class="active">Email Logs</li>
        </ol>

        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading"> <i class="glyphicon glyphicon-envelope"></i> Email Logs</div>
            </div>
            <div class="panel-body">
                <table class="table" id="emailLogsTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Quotation #</th>
                            <th>Client</th>
                            <th>Sent To</th>
                            <th>Subject</th>
                            <th>Status</th>
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
            { 
                data: 'sent_at',
                render: function(data) {
                    return moment(data).format('DD MMM YYYY HH:mm');
                }
            },
            { data: 'quotation_number' },
            { data: 'company_name' },
            { data: 'sent_to' },
            { data: 'subject' },
            { 
                data: 'status',
                render: function(data) {
                    return data === 1 ? 
                        '<span class="label label-success">Sent</span>' : 
                        '<span class="label label-danger">Failed</span>';
                }
            }
        ]
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 