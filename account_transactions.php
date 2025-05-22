<?php 
require_once 'php_action/core.php';
require_once 'php_action/middleware.php';

// Check if user has permission to view transactions
if(!hasPermission('view_transactions')) {
    header('Location: access_denied.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Account Transactions</title>
    <?php include('includes/header.php'); ?>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <ol class="breadcrumb">
                    <li><a href="dashboard.php">Home</a></li>
                    <li class="active">Account Transactions</li>
                </ol>

                <div class="panel panel-default">
                    <div class="panel-heading">
                        <div class="page-heading">
                            <i class="glyphicon glyphicon-credit-card"></i> Account Transactions
                        </div>
                    </div>

                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="accountSelect">Select Account:</label>
                                    <select class="form-control" id="accountSelect">
                                        <option value="">Select an account...</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row" id="transactionPanel" style="display: none;">
                            <div class="col-md-12">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered" id="transactionTable">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Account Owner</th>
                                                <th>Platform</th>
                                                <th>Type</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Action</th>
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
    </div>

    <!-- Transaction Details Modal -->
    <div class="modal fade" id="transactionDetailsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">Transaction Details</h4>
                </div>
                <div class="modal-body">
                    <!-- Transaction details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <?php include('includes/footer.php'); ?>
    <script type="text/javascript" src="custom/js/account-transactions.js"></script>
</body>
</html> 