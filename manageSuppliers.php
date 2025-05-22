<?php require_once 'includes/header.php'; ?>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading"><i class="glyphicon glyphicon-briefcase"></i> Manage Suppliers</div>
            </div>
            <div class="panel-body">
                <div class="remove-messages"></div>

                <div class="div-action pull-right" style="padding-bottom:20px;">
                    <button class="btn btn-success" data-toggle="modal" data-target="#addSupplierModal">
                        <i class="glyphicon glyphicon-plus"></i> Add Supplier
                    </button>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="row">
                                    <div class="col-sm-3">
                                        <button class="btn btn-default" id="copyBtn">Copy</button>
                                        <button class="btn btn-default" id="csvBtn">CSV</button>
                                        <button class="btn btn-default" id="printBtn">Print</button>
                                    </div>
                                    <div class="col-sm-6"></div>
                                    <div class="col-sm-3">
                                        <input type="search" class="form-control input-sm" placeholder="Search" aria-controls="manageSupplierTable">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <table class="table table-hover table-striped table-bordered" id="manageSupplierTable">
                            <thead>
                                <tr>
                                    <th>Company Name</th>
                                    <th>Contact Person</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Address</th>
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

<!-- Add Supplier Modal -->
<div class="modal fade" id="addSupplierModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="submitSupplierForm" action="php_action/createSupplier.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Add Supplier</h4>
                </div>
                <div class="modal-body">
                    <div id="add-supplier-messages"></div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Company Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="companyName" name="companyName" placeholder="Company Name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Contact Person</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="contactPerson" name="contactPerson" placeholder="Contact Person">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Email</label>
                        <div class="col-sm-9">
                            <input type="email" class="form-control" id="email" name="email" placeholder="Email">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Phone</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="phone" name="phone" placeholder="Phone">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Address</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="address" name="address" placeholder="Address" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="createSupplierBtn" data-loading-text="Loading...">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Supplier Modal -->
<div class="modal fade" id="editSupplierModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="editSupplierForm" action="php_action/editSupplier.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-edit"></i> Edit Supplier</h4>
                </div>
                <div class="modal-body">
                    <div id="edit-supplier-messages"></div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Company Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="editCompanyName" name="editCompanyName" placeholder="Company Name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Contact Person</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="editContactPerson" name="editContactPerson" placeholder="Contact Person">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Email</label>
                        <div class="col-sm-9">
                            <input type="email" class="form-control" id="editEmail" name="editEmail" placeholder="Email">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Phone</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="editPhone" name="editPhone" placeholder="Phone">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Address</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="editAddress" name="editAddress" placeholder="Address" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Status</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="editActive" name="editActive">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="supplierId" id="supplierId" />
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Remove Supplier Modal -->
<div class="modal fade" id="removeSupplierModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="glyphicon glyphicon-trash"></i> Remove Supplier</h4>
            </div>
            <div class="modal-body">
                <p>Do you really want to remove this supplier?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger" id="removeSupplierBtn" data-loading-text="Loading...">Save changes</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Table Styles */
.table {
    font-size: 11px !important;
}

.table > thead > tr > th {
    background-color: #337ab7;
    color: white;
    font-weight: normal;
    vertical-align: middle !important;
    border-bottom: 0 !important;
}

.table-striped > tbody > tr:nth-of-type(odd) {
    background-color: #f9f9f9;
}

.table-striped > tbody > tr:nth-of-type(even) {
    background-color: #ffffff;
}

.table > tbody > tr > td {
    vertical-align: middle;
    padding: 4px 8px;
}

/* Button Styles */
.btn {
    padding: 2px 6px;
    font-size: 10px;
}

.btn-group {
    display: flex;
    gap: 2px;
}

/* Action Column */
.action-buttons {
    white-space: nowrap;
}

/* Status Labels */
.label {
    font-size: 9px;
    padding: 3px 6px;
}

/* Search and Length Menu */
.dataTables_length select {
    height: 25px;
    font-size: 10px;
    padding: 2px;
}

.dataTables_filter input {
    height: 25px;
    font-size: 10px;
    padding: 2px 6px;
}

/* Pagination */
.pagination > li > a {
    padding: 4px 8px;
    font-size: 10px;
}

/* Status Badge Styles */
.badge-active {
    background-color: #5cb85c;
}
.badge-inactive {
    background-color: #d9534f;
}
</style>

<!-- Custom button handlers -->
<script>
$(document).ready(function() {
    // Initialize custom export buttons
    $('#copyBtn').on('click', function() {
        $('.buttons-copy').click();
    });
    $('#csvBtn').on('click', function() {
        $('.buttons-csv').click();
    });
    $('#printBtn').on('click', function() {
        $('.buttons-print').click();
    });
});
</script>

<script src="custom/js/supplier.js"></script>

<?php require_once 'includes/footer.php'; ?> 