<?php require_once 'includes/header.php'; ?>

<?php 
// Get active account info if set
$activeAccount = null;
if (isset($_SESSION['active_account'])) {
    $sql = "SELECT * FROM accounts WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $_SESSION['active_account']);
    $stmt->execute();
    $activeAccount = $stmt->get_result()->fetch_assoc();
}
?>

<div class="row">
    <div class="col-md-12">
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Home</a></li>
            <li class="active">Accounts</li>
        </ol>

        <?php if ($activeAccount): ?>
        <div class="alert alert-info">
            <strong>Active Account:</strong> <?php echo htmlspecialchars($activeAccount['account_owner']); ?> - 
            <?php echo htmlspecialchars($activeAccount['account_platform']); ?> 
            (<?php echo htmlspecialchars($activeAccount['Currency']); ?>)
        </div>
        <?php endif; ?>

        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading"> <i class="glyphicon glyphicon-credit-card"></i> Manage Accounts</div>
            </div>
            <div class="panel-body">
                <div class="remove-messages"></div>

                <div class="div-action pull-right" style="padding-bottom:20px;">
                    <button class="btn btn-default button1" data-toggle="modal" data-target="#addAccountModal">
                        <i class="glyphicon glyphicon-plus-sign"></i> Add Account
                    </button>
                </div>

                <table class="table" id="manageAccountsTable">
                    <thead>
                        <tr>
                            <th>Account Owner</th>
                            <th>Platform</th>
                            <th>Currency</th>
                            <th>Current Balance</th>
                            <th>Number of Transactions</th>
                            <th>Created At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Account Modal -->
<div class="modal fade" id="addAccountModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="submitAccountForm" action="php_action/createAccount.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Add Account</h4>
                </div>
                <div class="modal-body">
                    <div id="add-account-messages"></div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Account Owner</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="accountOwner" name="accountOwner" placeholder="Account Owner" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Platform</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="accountPlatform" name="accountPlatform" placeholder="Platform" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Currency</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="accountCurrency" name="accountCurrency" required>
                                <option value="">Select Currency</option>
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                                <option value="GBP">GBP</option>
                                <option value="BTC">BTC</option>
                                <option value="ETH">ETH</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="createAccountBtn">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Account Modal -->
<div class="modal fade" id="editAccountModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="editAccountForm" action="php_action/editAccount.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-edit"></i> Edit Account</h4>
                </div>
                <div class="modal-body">
                    <div id="edit-account-messages"></div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Account Owner</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="editAccountOwner" name="accountOwner" placeholder="Account Owner" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Platform</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="editAccountPlatform" name="accountPlatform" placeholder="Platform" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Currency</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="editAccountCurrency" name="accountCurrency" required>
                                <option value="">Select Currency</option>
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                                <option value="GBP">GBP</option>
                                <option value="BTC">BTC</option>
                                <option value="ETH">ETH</option>
                                <option value="ETB">ETB</option>
                            </select>
                        </div>
                    </div>
                    <input type="hidden" id="editAccountId" name="accountId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="custom/js/accounts.js"></script>
<script>
    var activeAccountId = <?php echo isset($_SESSION['active_account']) ? $_SESSION['active_account'] : 'null'; ?>;
</script>
<?php require_once 'includes/footer.php'; ?> 