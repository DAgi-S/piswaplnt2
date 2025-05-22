<?php require_once 'php_action/db_connect.php' ?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'php_action/emailConfig.php'; ?>
<?php require_once 'vendor/autoload.php'; 
require_once 'php_action/core.php';
require_once 'php_action/middleware.php';

// Check module permission
checkModulePermission('digitalswap');

$title = "Digital Swap Management";


?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <ol class="breadcrumb">
                <li><a href="dashboard.php">Home</a></li>
                <li class="active">Digital Swap</li>
            </ol>

            <div class="alert alert-success alert-dismissible" id="success-alert" style="display:none">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <strong><i class="glyphicon glyphicon-ok-sign"></i></strong> 
                <span id="success-message"></span>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <div class="page-heading"><i class="glyphicon glyphicon-transfer"></i> Manage Digital Swap</div>
                </div>
                <div class="panel-body">
                    <div class="remove-messages"></div>

                    <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                        <div></div> <!-- Empty div for flex spacing -->
                        <button class="btn btn-default button1" data-toggle="modal" id="addDigitalSwapModalBtn" data-target="#addDigitalSwapModal">
                            <i class="glyphicon glyphicon-plus-sign"></i> Add Digital Swap
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" id="manageDigitalSwapTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Name</th>
                                    <th>Platform</th>
                                    <th>Amount</th>
                                    <th>Image</th>
                                    <th>Comment</th>
                                    <th style="width:100px;">Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Digital Swap Modal -->
<div class="modal fade" id="addDigitalSwapModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="submitDigitalSwapForm" action="php_action/createDigitalswap.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Add Digital Swap</h4>
                </div>
                <div class="modal-body">
                    <div id="add-digitalswap-messages"></div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Transaction Date</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" id="transaction_date" name="transaction_date" required 
                                value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Type</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="type" name="type" required>
                                <option value="">~~SELECT~~</option>
                                <option value="deposit">Deposit</option>
                                <option value="withdraw">Withdraw</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="name" name="name" placeholder="Name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Amount</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="amount" name="amount" placeholder="Amount" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Image</label>
                        <div class="col-sm-9">
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Comment</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="comment" name="comment" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Account</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="accountId" name="accountId" required>
                                <option value="">Select Account</option>
                                <?php
                                // Debug: Log the SQL query
                                $accountSql = "SELECT id, account_owner, account_platform FROM accounts ORDER BY account_platform, account_owner";
                                error_log("Account SQL: " . $accountSql);
                                
                                $accountResult = $connect->query($accountSql);
                                
                                if (!$accountResult) {
                                    error_log("Account query error: " . $connect->error);
                                }
                                
                                $currentPlatform = '';
                                while($account = $accountResult->fetch_assoc()) {
                                    // Debug: Log each account
                                    error_log("Processing account: " . print_r($account, true));
                                    
                                    if($currentPlatform != $account['account_platform']) {
                                        if($currentPlatform != '') echo '</optgroup>';
                                        echo '<optgroup label="'.htmlspecialchars($account['account_platform']).'">';
                                        $currentPlatform = $account['account_platform'];
                                    }
                                    echo "<option value='".htmlspecialchars($account['id'])."'>".htmlspecialchars($account['account_owner'])."</option>";
                                }
                                if($currentPlatform != '') echo '</optgroup>';
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="createDigitalSwapBtn">
                        <i class="glyphicon glyphicon-ok-sign"></i> Save changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Remove Digital Swap Modal -->
<div class="modal fade" id="removeDigitalSwapModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="glyphicon glyphicon-trash"></i> Remove Digital Swap</h4>
            </div>
            <div class="modal-body">
                <p>Do you really want to remove this record?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger" id="removeDigitalSwapBtn">Remove</button>
            </div>
        </div>
    </div>
</div>

<!-- View Digital Swap Modal -->
<div class="modal fade" id="viewDigitalSwapModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="glyphicon glyphicon-eye-open"></i> View Digital Swap</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <table class="table table-bordered">
                            <tr>
                                <th>Transaction Date</th>
                                <td id="viewTransactionDate"></td>
                            </tr>
                            <tr>
                                <th>Type</th>
                                <td id="viewType"></td>
                            </tr>
                            <tr>
                                <th>Name</th>
                                <td id="viewName"></td>
                            </tr>
                            <tr>
                                <th>Platform</th>
                                <td id="viewPlatform"></td>
                            </tr>
                            <tr>
                                <th>Amount</th>
                                <td id="viewAmount"></td>
                            </tr>
                            <tr>
                                <th>Image</th>
                                <td id="viewImage"></td>
                            </tr>
                            <tr>
                                <th>Comment</th>
                                <td id="viewComment"></td>
                            </tr>
                            <tr>
                                <th>Date</th>
                                <td id="viewDate"></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Digital Swap Modal -->
<div class="modal fade" id="editDigitalSwapModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="editDigitalSwapForm" action="php_action/updateDigitalswap.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-edit"></i> Edit Digital Swap</h4>
                </div>
                <div class="modal-body">
                    <div id="edit-digitalswap-messages"></div>

                    <input type="hidden" id="editDigitalSwapId" name="id">

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Transaction Date</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" id="editTransactionDate" name="transaction_date" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Type</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="editType" name="type" required>
                                <option value="">~~SELECT~~</option>
                                <option value="deposit">Deposit</option>
                                <option value="withdraw">Withdraw</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="editName" name="name" placeholder="Name" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Amount</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="editAmount" name="amount" placeholder="Amount" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Current Image</label>
                        <div class="col-sm-9" id="currentImage">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">New Image</label>
                        <div class="col-sm-9">
                            <input type="file" class="form-control" id="editImage" name="image" accept="image/*">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Comment</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="editComment" name="comment" rows="3"></textarea>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Account</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="editAccountId" name="accountId" required>
                                <option value="">Select Account</option>
                                <?php
                                $accountSql = "SELECT id, account_owner, account_platform FROM accounts ORDER BY account_platform, account_owner";
                                $accountResult = $connect->query($accountSql);
                                
                                $currentPlatform = '';
                                while($account = $accountResult->fetch_assoc()) {
                                    if($currentPlatform != $account['account_platform']) {
                                        if($currentPlatform != '') echo '</optgroup>';
                                        echo '<optgroup label="'.htmlspecialchars($account['account_platform']).'">';
                                        $currentPlatform = $account['account_platform'];
                                    }
                                    echo "<option value='".htmlspecialchars($account['id'])."'>".htmlspecialchars($account['account_owner'])."</option>";
                                }
                                if($currentPlatform != '') echo '</optgroup>';
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="editDigitalSwapBtn">
                        <i class="glyphicon glyphicon-ok-sign"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Image Preview Modal -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Image Preview</h4>
            </div>
            <div class="modal-body text-center">
                <img id="previewImage" src="" alt="Preview" style="max-width: 100%; max-height: 80vh;">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Add this before the footer -->
<script src="custom/js/digitalswap.js"></script>

<style>
/* DataTable Styling */
.dataTables_wrapper {
    padding: 15px 0;
}

.dataTables_length, .dataTables_filter {
    margin-bottom: 15px;
}

#manageDigitalSwapTable {
    border: 1px solid #ddd;
    border-collapse: collapse;
}

#manageDigitalSwapTable th {
    background-color: #f5f5f5;
    border: 1px solid #ddd;
    padding: 8px;
}

#manageDigitalSwapTable td {
    border: 1px solid #ddd;
    padding: 8px;
    vertical-align: middle;
}

/* Table Hover Effect */
#manageDigitalSwapTable tbody tr:hover {
    background-color: #f9f9f9;
}

/* Pagination Styling */
.dataTables_paginate .paginate_button {
    padding: 5px 10px;
    margin: 0 2px;
    border: 1px solid #ddd;
    border-radius: 3px;
    cursor: pointer;
}

.dataTables_paginate .paginate_button.current {
    background-color: #337ab7;
    color: white !important;
    border-color: #337ab7;
}

.dataTables_paginate .paginate_button:hover:not(.current) {
    background-color: #f5f5f5;
}

/* Search Box Styling */
.dataTables_filter input {
    border: 1px solid #ddd;
    padding: 5px 10px;
    border-radius: 3px;
    margin-left: 5px;
}

/* Show Entries Dropdown */
.dataTables_length select {
    border: 1px solid #ddd;
    padding: 5px;
    border-radius: 3px;
    margin: 0 5px;
}

/* Dropdown Styling */
.btn-group {
    position: relative;
}

.dropdown-menu {
    min-width: 130px;
    border-radius: 3px;
    box-shadow: 0 2px 5px rgba(0,0,0,.1);
}

.dropdown-menu > li > a {
    padding: 6px 20px;
    color: #333;
}

.dropdown-menu > li > a:hover {
    background-color: #f5f5f5;
}

.dropdown-menu > li > a i {
    margin-right: 8px;
    color: #666;
}

.action-dropdown {
    background-color: #fff;
    border: 1px solid #ccc;
    padding: 4px 8px;
    font-size: 13px;
}
</style>

<?php require_once 'includes/footer.php'; ?>