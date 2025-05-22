<?php
require_once '../includes/core.php';
?>

<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title"><i class="fa fa-edit"></i> Edit Supplier</h4>
</div>

<form class="form-horizontal" id="editSupplierForm" action="php_action/editSupplier.php" method="POST">
    <div class="modal-body">
        <div class="edit-messages"></div>

        <div class="form-group">
            <label for="editCompanyName" class="control-label col-sm-3">Company Name:</label>
            <div class="col-sm-9">
                <input type="text" class="form-control" id="editCompanyName" name="editCompanyName" placeholder="Enter company name" required>
            </div>
        </div>
        <div class="form-group">
            <label for="editContactPerson" class="control-label col-sm-3">Contact Person:</label>
            <div class="col-sm-9">
                <input type="text" class="form-control" id="editContactPerson" name="editContactPerson" placeholder="Enter contact person">
            </div>
        </div>
        <div class="form-group">
            <label for="editPhone" class="control-label col-sm-3">Phone:</label>
            <div class="col-sm-9">
                <input type="text" class="form-control" id="editPhone" name="editPhone" placeholder="Enter phone number">
            </div>
        </div>
        <div class="form-group">
            <label for="editEmail" class="control-label col-sm-3">Email:</label>
            <div class="col-sm-9">
                <input type="email" class="form-control" id="editEmail" name="editEmail" placeholder="Enter email">
            </div>
        </div>
        <div class="form-group">
            <label for="editAddress" class="control-label col-sm-3">Address:</label>
            <div class="col-sm-9">
                <textarea class="form-control" id="editAddress" name="editAddress" placeholder="Enter address"></textarea>
            </div>
        </div>
        <div class="form-group">
            <label for="editTin" class="control-label col-sm-3">TIN:</label>
            <div class="col-sm-9">
                <input type="text" class="form-control" id="editTin" name="editTin" placeholder="Enter Tax Identification Number">
            </div>
        </div>
        <div class="form-group">
            <label for="editStatus" class="control-label col-sm-3">Status:</label>
            <div class="col-sm-9">
                <select class="form-control" id="editStatus" name="editStatus">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="hidden" id="supplierId" name="supplierId">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Save changes</button>
    </div>
</form> 