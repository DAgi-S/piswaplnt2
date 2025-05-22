<?php 
require_once 'php_action/core.php';
require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading"><i class="glyphicon glyphicon-globe"></i> Payment Records</div>
            </div>
            <div class="panel-body">
                <div class="remove-messages"></div>
                
                <!-- Filter Section -->
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-md-3">
                        <select class="form-control" id="typeFilter">
                            <option value="">All Types</option>
                            <option value="deposit">Deposit</option>
                            <option value="withdraw">Withdraw</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control" id="platformFilter">
                            <option value="">All Platforms</option>
                            <option value="Cashapp">Cashapp</option>
                            <option value="Creditcard">Credit Card</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="date" class="form-control" id="dateFilter" placeholder="Filter by Date">
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary" id="filterBtn">
                            <i class="glyphicon glyphicon-filter"></i> Apply Filter
                        </button>
                        <button class="btn btn-default" id="resetBtn">
                            <i class="glyphicon glyphicon-refresh"></i> Reset
                        </button>
                    </div>
                </div>

                <table class="table table-hover table-striped table-bordered" id="managePaymentTable">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Account Name</th>
                            <th>Type</th>
                            <th>Paid On</th>
                            <th>Amount</th>
                            <th>Digital Swap ID</th>
                            <th style="width:15%;">Options</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- View Payment Details Modal -->
<div class="modal fade" id="viewPaymentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="glyphicon glyphicon-eye-open"></i> Payment Details</h4>
            </div>
            <div class="modal-body">
                <div id="payment-details-content"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Paid On Modal -->
<div class="modal fade" id="editPaidOnModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="editPaidOnForm" action="php_action/updatePaidOn.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="glyphicon glyphicon-edit"></i> Update Payment Platform</h4>
                </div>
                <div class="modal-body">
                    <div id="edit-paidon-messages"></div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Platform</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="editPlatform" name="editPlatform" required>
                                <option value="Cashapp">Cashapp</option>
                                <option value="Creditcard">Credit Card</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="paymentId" id="paymentId" />
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="custom/js/paid-on.js"></script>

<?php require_once 'includes/footer.php'; ?> 