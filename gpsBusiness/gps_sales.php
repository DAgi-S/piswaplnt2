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
                <div class="page-heading">
                    <i class="fas fa-shopping-cart"></i> GPS Sales
                    <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#addSaleModal">
                        <i class="fas fa-plus"></i> Add Sale
                    </button>
                </div>
            </div>
            <div class="panel-body">
                <div class="remove-messages"></div>

                <table class="table table-hover table-striped table-bordered" id="manageSaleTable">
                    <thead>
                        <tr>
                            <th>Sale #</th>
                            <th>Date</th>
                            <th>Buyer</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                            <th>Currency</th>
                            <th>Rate</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Sale Modal -->
<div class="modal fade" id="addSaleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="submitSaleForm" action="php_action/createSale.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Add Sale</h4>
                </div>
                <div class="modal-body">
                    <div id="add-sale-messages"></div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Buyer Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="buyerName" name="buyerName" placeholder="Buyer Name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Contact</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="contact" name="contact" placeholder="Contact Number">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Sales Type</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="salesType" name="salesType" required>
                                <option value="">Select Type</option>
                                <option value="Sales">Sales</option>
                                <option value="Distribution">Distribution</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Unit Price</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" id="unitPrice" name="unitPrice" step="0.01" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Currency</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="currency" name="currency" required>
                                <option value="ETB">ETB</option>
                                <option value="USD">USD</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Rate</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" id="rate" name="rate" step="0.01" value="1.00">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Quantity</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" id="quantity" name="quantity" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Total</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" id="total" name="total" step="0.01" readonly>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Sale</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Sale Modal -->
<div class="modal fade" id="editSaleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="editSaleForm" action="php_action/editSale.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-edit"></i> Edit Sale</h4>
                </div>
                <div class="modal-body">
                    <div id="edit-sale-messages"></div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Buyer Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="editBuyerName" name="buyerName" placeholder="Buyer Name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Contact</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="editContact" name="contact" placeholder="Contact Number">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Sales Type</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="editSalesType" name="salesType" required>
                                <option value="">Select Type</option>
                                <option value="Sales">Sales</option>
                                <option value="Distribution">Distribution</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Unit Price</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" id="editUnitPrice" name="unitPrice" step="0.01" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Currency</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="editCurrency" name="currency" required>
                                <option value="ETB">ETB</option>
                                <option value="USD">USD</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Rate</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" id="editRate" name="rate" step="0.01" value="1.00">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Quantity</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" id="editQuantity" name="quantity" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Total</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" id="editTotal" name="total" step="0.01" readonly>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="saleId" id="editSaleId">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Sale</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Sale Modal -->
<div class="modal fade" id="viewSaleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-eye"></i> Sale Details</h4>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <tr>
                        <th>Sale Number</th>
                        <td id="viewSaleNumber"></td>
                    </tr>
                    <tr>
                        <th>Date</th>
                        <td id="viewSaleDate"></td>
                    </tr>
                    <tr>
                        <th>Buyer</th>
                        <td id="viewBuyerName"></td>
                    </tr>
                    <tr>
                        <th>Contact</th>
                        <td id="viewContact"></td>
                    </tr>
                    <tr>
                        <th>Sales Type</th>
                        <td id="viewSalesType"></td>
                    </tr>
                    <tr>
                        <th>Unit Price</th>
                        <td id="viewUnitPrice"></td>
                    </tr>
                    <tr>
                        <th>Currency</th>
                        <td id="viewCurrency"></td>
                    </tr>
                    <tr>
                        <th>Rate</th>
                        <td id="viewRate"></td>
                    </tr>
                    <tr>
                        <th>Quantity</th>
                        <td id="viewQuantity"></td>
                    </tr>
                    <tr>
                        <th>Total</th>
                        <td id="viewTotal"></td>
                    </tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Add moment.js before your script -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
<script src="js/gps_sales.js"></script>

<?php require_once 'includes/footer.php'; ?> 