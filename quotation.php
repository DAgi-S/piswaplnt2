<?php 
require_once 'php_action/db_connect.php';
require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Home</a></li>
            <li><a href="manageQuotations.php">Manage Quotations</a></li>
            <li class="active">Create Quotation</li>
        </ol>

        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading"> <i class="glyphicon glyphicon-plus"></i> Create New Quotation</div>
            </div>
            <div class="panel-body">
                <div id="messages"></div>

                <form class="form-horizontal" id="createQuotationForm" action="php_action/createQuotation.php" method="POST">
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Client</label>
                        <div class="col-sm-8">
                            <div class="input-group">
                                <select class="form-control" id="clientId" name="clientId" required>
                                    <option value="">Select Client</option>
                                </select>
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createClientModal">
                                        <i class="glyphicon glyphicon-plus"></i> New Client
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Products</label>
                        <div class="col-sm-8">
                            <table class="table table-bordered" id="productTable">
                                <thead>
                                    <tr>
                                        <th style="width:40%">Product</th>
                                        <th style="width:20%">Price</th>
                                        <th style="width:15%">Quantity</th>
                                        <th style="width:15%">Total</th>
                                        <th style="width:10%">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr id="row1">
                                        <td>
                                            <select class="form-control product-select" name="productId[]" required>
                                                <option value="">Select Product</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control product-price" name="price[]" step="0.01" min="0" required>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control product-quantity" name="quantity[]" min="0" required>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control product-total" name="total[]" step="0.01" readonly>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-danger btn-sm remove-row">
                                                <i class="glyphicon glyphicon-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <button type="button" class="btn btn-success" onclick="addRow()">
                                <i class="glyphicon glyphicon-plus"></i> Add Product
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Sub Total</label>
                        <div class="col-sm-8">
                            <input type="number" class="form-control" id="subTotal" name="subTotal" step="0.01" readonly>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">VAT (15%)</label>
                        <div class="col-sm-8">
                            <input type="number" class="form-control" id="vatAmount" name="vatAmount" step="0.01" readonly>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Withholding Tax</label>
                        <div class="col-sm-8">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" id="withholdingEnabled" name="withholdingEnabled"> Enable Withholding Tax (2%)
                                </label>
                            </div>
                            <input type="number" class="form-control" id="withholdingAmount" name="withholdingAmount" step="0.01" readonly>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Grand Total</label>
                        <div class="col-sm-8">
                            <input type="number" class="form-control" id="grandTotal" name="grandTotal" step="0.01" readonly>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Note</label>
                        <div class="col-sm-8">
                            <textarea class="form-control" id="note" name="note" rows="3"></textarea>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-8">
                            <button type="submit" class="btn btn-primary">Create Quotation</button>
                            <a href="manageQuotations.php" class="btn btn-default">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Create Client Modal -->
<div class="modal fade" id="createClientModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="glyphicon glyphicon-plus"></i> Create New Client</h4>
            </div>
            <form id="createClientForm">
                <div class="modal-body">
                    <div id="create-client-messages"></div>

                    <div class="form-group">
                        <label>Company Name *</label>
                        <input type="text" class="form-control" id="companyName" name="companyName" required>
                    </div>

                    <div class="form-group">
                        <label>TIN Number</label>
                        <input type="text" class="form-control" id="tinNumber" name="tinNumber">
                    </div>

                    <div class="form-group">
                        <label>Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone">
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Create Client</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="custom/js/quotation.js"></script>
<?php require_once 'includes/footer.php'; ?> 