<?php
require_once 'includes/header.php';
require_once 'php_action/core.php';

// Check if user has permission to manage guests
if (!hasPermission('manage_guests')) {
    header('location: access_denied.php');
    exit();
}
?>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="fa fa-users"></i> Manage Guest Users
                    <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#addGuestModal">
                        <i class="fa fa-plus"></i> Add New Guest
                    </button>
                </div>
            </div>
            <div class="panel-body">
                <div class="remove-messages"></div>
                <table class="table table-striped table-bordered" id="guestTable">
                    <thead>
                        <tr>
                            <th>Guest ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Account</th>
                            <th>Currency</th>
                            <th>Access Level</th>
                            <th>Status</th>
                            <th>Expiry Date</th>
                            <th>Last Login</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Guest Modal -->
<div class="modal fade" id="addGuestModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="submitGuestForm" action="php_action/createGuest.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Add New Guest</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Username</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="username" name="username" placeholder="Guest Username" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Password</label>
                        <div class="col-sm-9">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Guest Password" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Full Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="fullName" name="fullName" placeholder="Guest Full Name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Linked Account</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="linkedAccount" name="linkedAccount" required>
                                <option value="">Select Account</option>
                                <?php
                                $sql = "SELECT * FROM accounts WHERE satstus = 1";
                                $result = $connect->query($sql);
                                while($row = $result->fetch_assoc()) {
                                    echo "<option value='".$row['id']."'>".$row['account_owner']." - ".$row['account_platform']." (".$row['Currency'].")</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Access Level</label>
                        <div class="col-sm-9">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="access[]" value="transactions"> View Transactions
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="access[]" value="reports"> View Reports
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="access[]" value="profile"> Edit Profile
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Expiry Date</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" id="expiryDate" name="expiryDate" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="createGuestBtn" data-loading-text="Creating...">
                        <i class="fa fa-save"></i> Create Guest
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Guest Modal -->
<div class="modal fade" id="editGuestModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="editGuestForm" action="php_action/editGuest.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><i class="fa fa-edit"></i> Edit Guest</h4>
                </div>
                <div class="modal-body">
                    <div class="edit-guest-messages"></div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Username</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="editUsername" name="editUsername" placeholder="Guest Username" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Password</label>
                        <div class="col-sm-9">
                            <input type="password" class="form-control" id="editPassword" name="editPassword" placeholder="Leave blank to keep current password">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Full Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="editFullName" name="editFullName" placeholder="Guest Full Name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Access Level</label>
                        <div class="col-sm-9">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="editAccess[]" value="transactions"> View Transactions
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="editAccess[]" value="reports"> View Reports
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="editAccess[]" value="profile"> Edit Profile
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Expiry Date</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" id="editExpiryDate" name="editExpiryDate" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Status</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="editStatus" name="editStatus" required>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="guestId" id="guestId">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="editGuestBtn" data-loading-text="Saving...">
                        <i class="fa fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="custom/js/guest.js"></script>

<?php require_once 'includes/footer.php'; ?> 