<?php 
require_once 'php_action/db_connect.php';
require_once 'includes/header.php';

if(!isset($_GET['id'])) {
    header('location: manageQuotations.php');
    exit();
}

$quotationId = $_GET['id'];
?>

<div class="row">
    <div class="col-md-12">
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Home</a></li>
            <li><a href="manageQuotations.php">Manage Quotations</a></li>
            <li class="active">Edit Quotation</li>
        </ol>

        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading"> <i class="glyphicon glyphicon-edit"></i> Edit Quotation</div>
            </div>
            <div class="panel-body">
                <div id="edit-quotation-messages"></div>

                <form class="form-horizontal" id="editQuotationForm" action="php_action/editQuotation.php" method="POST">
                    <input type="hidden" name="quotationId" id="quotationId" value="<?php echo $quotationId; ?>">
                    
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Client</label>
                        <div class="col-sm-8">
                            <select class="form-control" id="clientId" name="clientId" required>
                                <option value="">Select Client</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">Products</label>
                        <div class="col-sm-8">
                            <table class="table table-bordered" id="productTable">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                        <th>Action</th>
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
                                            <input type="number" class="form-control product-price" name="price[]" step="0.01" required>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control product-quantity" name="quantity[]" min="1" required>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control product-total" name="total[]" step="0.01" readonly>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-danger btn-sm remove-row" onclick="removeRow(this)">
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
                            <button type="submit" class="btn btn-primary">Update Quotation</button>
                            <a href="manageQuotations.php" class="btn btn-default">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="custom/js/editQuotation.js"></script>
<?php require_once 'includes/footer.php'; ?> 