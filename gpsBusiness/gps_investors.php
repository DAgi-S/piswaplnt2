<?php
require_once '../php_action/core.php';

// Initialize the database connection if not already done
if (!isset($connect)) {
    require_once '../php_action/db_connect.php';
}

require_once 'includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    header('location: ../index.php');
    exit();
}

// Fetch investors for dropdown
$investorsSql = "SELECT id, name FROM gps_investors ORDER BY name ASC";
$investorsResult = $connect->query($investorsSql);
?>

<style>
    /* Form Styles */
    .form-group {
        margin-bottom: 8px;
    }
    
    .form-control {
        font-size: 11px;
        height: 30px;
        padding: 5px 10px;
    }
    
    .control-label {
        font-size: 11px;
        padding-top: 5px;
    }
    
    .modal-body {
        padding: 15px;
    }
    
    .row {
        margin-bottom: 5px;
    }
    
    /* Image Preview */
    .payment-image-preview {
        max-width: 100%;
        max-height: 200px;
        margin-top: 10px;
    }
    
    /* Modal Size */
    .modal-dialog {
        width: 600px;
    }
    
    /* Button Styles */
    .btn {
        font-size: 11px;
        padding: 4px 8px;
    }
</style>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading"><i class="fas fa-users"></i> GPS Business Investors</div>
            </div>
            <div class="panel-body">
                <div class="remove-messages"></div>

                <div class="div-action pull-right" style="padding-bottom:20px;">
                    <button class="btn btn-primary" data-toggle="modal" data-target="#addGpsInvestorModal">
                        <i class="fas fa-plus"></i> Add Investor
                    </button>
                </div>

                <table class="table table-hover table-striped table-bordered" id="gpsInvestorsTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Share (%)</th>
                            <th>Investment</th>
                            <th>Account Type</th>
                            <th>Balance</th>
                            <th>Credit Amount</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add GPS Investor Modal -->
<div class="modal fade" id="addGpsInvestorModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="submitGpsInvestorForm" action="php_action/createGpsInvestor.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Add Investor</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="name" name="name" placeholder="Investor Name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Share (%)</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="sharePercentage" name="sharePercentage" placeholder="Share Percentage" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Investment</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="investment" name="investment" placeholder="Investment Amount" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Account Type</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="accountType" name="accountType" required>
                                <option value="ETB">ETB</option>
                                <option value="USD">USD</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Balance</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="balance" name="balance" placeholder="Current Balance" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Credit Amount</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="creditAmount" name="creditAmount" placeholder="Credit Amount" value="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit GPS Investor Modal -->
<div class="modal fade" id="editGpsInvestorModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="editGpsInvestorForm" action="php_action/editGpsInvestor.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-edit"></i> Edit Investor</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="editName" name="editName" placeholder="Investor Name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Share (%)</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="editSharePercentage" name="editSharePercentage" placeholder="Share Percentage" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Investment</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="editInvestment" name="editInvestment" placeholder="Investment Amount" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Account Type</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="editAccountType" name="editAccountType" required>
                                <option value="ETB">ETB</option>
                                <option value="USD">USD</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Balance</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="editBalance" name="editBalance" placeholder="Current Balance" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Credit Amount</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="editCreditAmount" name="editCreditAmount" placeholder="Credit Amount">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="investorId" id="investorId">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="js/gps_investors.js"></script>

<?php require_once 'includes/footer.php'; ?> 