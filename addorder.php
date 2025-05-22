<?php 
require_once 'php_action/core.php';
require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <!-- Breadcrumb -->
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Home</a></li>
            <li><a href="orders.php?o=manord">Order</a></li>
            <li class="active">Add Order</li>
        </ol>

        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="glyphicon glyphicon-plus-sign"></i> Add New Order
            </div>
            <div class="panel-body">
                <form id="addOrderForm" action="php_action/createNewOrder.php" method="POST">
                    <!-- Client Information Section -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Date</label>
                                <input type="text" class="form-control" id="orderDate" name="orderDate" readonly>
                            </div>
                            <div class="form-group">
                                <label>FS Number</label>
                                <input type="text" class="form-control" name="fsNumber" required>
                            </div>
                            <div class="form-group">
                                <label>Client Name</label>
                                <input type="text" class="form-control" name="clientName" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Client Contact</label>
                                <input type="text" class="form-control" name="clientContact" required>
                            </div>
                            <div class="form-group">
                                <label>Client TIN</label>
                                <input type="text" class="form-control" name="clientTin">
                            </div>
                        </div>
                    </div>

                    <!-- Products Section -->
                    <div class="row" style="margin-top: 20px;">
                        <div class="col-md-12">
                            <table class="table" id="productTable">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Available Qty</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr id="row1">
                                        <td>
                                            <select class="form-control product" name="productName[]" id="productName1" required>
                                                <option value="">Select Product</option>
                                                <?php
                                                $sql = "SELECT * FROM products WHERE status = 1 AND quantity > 0";
                                                $result = $connect->query($sql);
                                                while($row = $result->fetch_array()) {
                                                    echo "<option value='".$row['product_id']."'>".$row['name']."</option>";
                                                }
                                                ?>
                                            </select>
                                        </td>
                                        <td><input type="number" step="0.01" class="form-control price" name="price[]" id="price1" required></td>
                                        <td><span id="available1"></span> (<span id="purchased1">0</span> purchased)</td>
                                        <td><input type="number" class="form-control quantity" name="quantity[]" id="quantity1" min="1"></td>
                                        <td><input type="text" class="form-control total" name="total[]" id="total1" readonly></td>
                                        <td><button type="button" class="btn btn-danger" onclick="removeRow(1)">Remove</button></td>
                                    </tr>
                                </tbody>
                            </table>
                            <button type="button" class="btn btn-primary" id="addRow">Add Product</button>
                        </div>
                    </div>

                    <!-- Totals Section -->
                    <div class="row" style="margin-top: 20px;">
                        <div class="col-md-offset-6 col-md-6">
                            <div class="form-group">
                                <label>Sub Total</label>
                                <input type="text" class="form-control" id="subTotal" name="subTotal" readonly>
                            </div>
                            <div class="form-group">
                                <label>VAT (15%)</label>
                                <input type="text" class="form-control" id="vat" name="vat" readonly>
                            </div>
                            <div class="form-group">
                                <label>Grand Total</label>
                                <input type="text" class="form-control" id="grandTotal" name="grandTotal" readonly>
                            </div>
                            <div class="form-group">
                                <label>Payment Type</label>
                                <select class="form-control" name="paymentType" required>
                                    <option value="">Select Payment Type</option>
                                    <option value="1">Cash</option>
                                    <option value="2">Check</option>
                                    <option value="3">Credit Card</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="row" style="margin-top: 20px;">
                        <div class="col-md-12 text-right">
                            <a href="orders.php?o=manord" class="btn btn-default">Cancel</a>
                            <button type="submit" class="btn btn-success">Save Order</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<!-- Additional Scripts -->
<script src="custom/js/addOrder.js"></script> 