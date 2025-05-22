<?php
require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Home</a></li>
            <li class="active">Manage Suppliers</li>
        </ol>

        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="fa fa-truck"></i> Manage Suppliers
                    <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#addSupplierModal">
                        <i class="fa fa-plus"></i> Add Supplier
                    </button>
                </div>
            </div>

            <div class="panel-body">
                <div class="remove-messages"></div>

                <div class="table-responsive">
                    <table class="table" id="manageSupplierTable">
                        <thead>
                            <tr>
                                <th>Company Name</th>
                                <th>Contact Person</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Address</th>
                                <th>TIN</th>
                                <th>Status</th>
                                <th>Options</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Table data will be loaded dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Supplier Modal -->
<div class="modal fade" id="addSupplierModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="submitSupplierForm" action="php_action/createSupplierMain.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Add Supplier</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Company Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="companyName" name="companyName" placeholder="Company Name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Contact Person</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="contactPerson" name="contactPerson" placeholder="Contact Person Name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Phone</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="phone" name="phone" placeholder="Phone Number" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Email</label>
                        <div class="col-sm-9">
                            <input type="email" class="form-control" id="email" name="email" placeholder="Email Address">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">TIN</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="tin" name="tin" placeholder="Tax Identification Number" maxlength="20">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Address</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="address" name="address" rows="3" placeholder="Full Address"></textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Status</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="status" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="createSupplierBtn">Save changes</button>
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
                    <div class="edit-messages"></div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Company Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="editCompanyName" name="editCompanyName" placeholder="Company Name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Contact Person</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="editContactPerson" name="editContactPerson" placeholder="Contact Person Name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Phone</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="editPhone" name="editPhone" placeholder="Phone Number" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Email</label>
                        <div class="col-sm-9">
                            <input type="email" class="form-control" id="editEmail" name="editEmail" placeholder="Email Address">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">TIN</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="editTin" name="editTin" placeholder="Tax Identification Number" maxlength="20">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Address</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="editAddress" name="editAddress" rows="3" placeholder="Full Address"></textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Status</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="editStatus" name="editStatus" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
                <input type="hidden" name="supplierId" id="supplierId">
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
                <h4 class="modal-title"><i class="fa fa-trash"></i> Remove Supplier</h4>
            </div>
            <div class="modal-body">
                <p>Do you really want to remove this supplier?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger" id="removeSupplierBtn">Remove</button>
            </div>
        </div>
    </div>
</div>

<!-- View Supplier Modal -->
<div class="modal fade" id="viewSupplierModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-eye"></i> View Supplier Details</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <table class="table table-bordered table-striped">
                            <tr>
                                <th style="width:30%">Company Name</th>
                                <td id="view_company_name"></td>
                            </tr>
                            <tr>
                                <th>Contact Person</th>
                                <td id="view_contact_person"></td>
                            </tr>
                            <tr>
                                <th>Phone</th>
                                <td id="view_phone"></td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td id="view_email"></td>
                            </tr>
                            <tr>
                                <th>TIN</th>
                                <td id="view_tin"></td>
                            </tr>
                            <tr>
                                <th>Address</th>
                                <td id="view_address"></td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td id="view_status"></td>
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

<!-- Add required CSS and JS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs/dt-1.11.5/b-2.2.2/b-html5-2.2.2/b-print-2.2.2/r-2.2.9/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap.min.css"/>
 
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/v/bs/dt-1.11.5/b-2.2.2/b-html5-2.2.2/b-print-2.2.2/r-2.2.9/datatables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.bootstrap.min.js"></script>

<!-- Custom CSS -->
<style>
.table-responsive {
    padding: 20px;
}
#manageSupplierTable_wrapper .dt-buttons {
    margin-bottom: 15px;
}
.remove-messages {
    margin: 10px 0;
}
.label {
    display: inline-block;
    padding: 5px 10px;
    font-size: 12px;
    margin: 3px;
}

/* Supplier-specific DataTable styling */
#manageSupplierTable_wrapper .dt-buttons {
    text-align: center;
    margin-bottom: 15px;
}

#manageSupplierTable_wrapper .dt-buttons .btn {
    margin: 0 2px;
}

#manageSupplierTable_wrapper .dataTables_filter {
    margin-bottom: 10px;
}

#manageSupplierTable_wrapper .dataTables_filter input {
    width: 250px;
    height: 34px;
    padding: 6px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

#manageSupplierTable_wrapper .dataTables_length select {
    width: 75px;
    height: 34px;
    padding: 6px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

#manageSupplierTable tbody td {
    vertical-align: middle;
}

#manageSupplierTable .btn-group {
    display: flex;
    justify-content: center;
}

#manageSupplierTable .label {
    display: inline-block;
    min-width: 60px;
    text-align: center;
}

/* Modal styling */
.supplier-modal .modal-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.supplier-modal .form-group {
    margin-bottom: 15px;
}

.supplier-modal .control-label {
    font-weight: 600;
}

.supplier-modal .modal-footer {
    background-color: #f8f9fa;
    border-top: 1px solid #dee2e6;
}

/* Alert message styling */
.remove-messages .alert,
.edit-messages .alert {
    margin-bottom: 15px;
    border-radius: 4px;
}

/* Status label styling */
#manageSupplierTable .label-success {
    background-color: #28a745;
}

#manageSupplierTable .label-danger {
    background-color: #dc3545;
}

/* Responsive styling */
@media (max-width: 768px) {
    #manageSupplierTable_wrapper .dt-buttons {
        text-align: center;
        margin-bottom: 10px;
    }

    #manageSupplierTable_wrapper .dataTables_filter {
        text-align: center;
    }

    #manageSupplierTable_wrapper .dataTables_filter input {
        width: 100%;
    }
}
</style>

<!-- Include custom JavaScript -->
<script src="custom/js/supplier.js"></script>

<?php require_once 'includes/footer.php'; ?> 