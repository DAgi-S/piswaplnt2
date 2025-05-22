<?php
define('BASEPATH', true);
// First include all necessary PHP files
require_once 'php_action/db_connect.php';
require_once 'php_action/core.php';
require_once 'php_action/functions.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize order-related permissions
$permissions = array(
    // Core Order Permissions
    'view' => hasPermission('order.view') || hasPermission('view_orders'),
    'create' => hasPermission('order.create') || hasPermission('create_order'),
    'edit' => hasPermission('order.edit') || hasPermission('edit_order'),
    'delete' => hasPermission('order.delete') || hasPermission('delete_order'),
    
    // Order Management
    'bulk_update' => hasPermission('order.bulk_update'),
    'process' => hasPermission('order.process'),
    'status_update' => hasPermission('order.status.update'),
    
    // Payment Management
    'payment' => array(
        'view' => hasPermission('order.payment.view'),
        'manage' => hasPermission('order.payment.manage'),
        'process' => hasPermission('order.payment.process')
    ),
    
    // Shipping Management
    'shipping' => array(
        'view' => hasPermission('order.shipping.view'),
        'manage' => hasPermission('order.shipping.manage')
    ),
    
    // Reports and Analytics
    'reports' => array(
        'view' => hasPermission('order.report.view'),
        'export' => hasPermission('order.report.export')
    )
);

// Check base access permission
if (!$permissions['view']) {
    $_SESSION['error'] = "You don't have permission to access orders.";
    header('Location: access_denied.php');
    exit();
}

// Only include header after permission check
require_once 'includes/header.php';



if($_GET['o'] == 'add') { 
	echo "<div class='div-request div-hide'>add</div>";
} else if($_GET['o'] == 'manord') { 
	echo "<div class='div-request div-hide'>manord</div>";
} else if($_GET['o'] == 'editOrd') { 
	echo "<div class='div-request div-hide'>editOrd</div>";
} // /else manage order


?>

<ol class="breadcrumb">
  <li><a href="dashboard.php">Home</a></li>
  <li>Order</li>
  <li class="active">
  	<?php if($_GET['o'] == 'add') { ?>
  		Add Quote
		<?php } else if($_GET['o'] == 'manord') { ?>
			Manage Order
		<?php } else if($_GET['o'] == 'editOrd') { ?>
			Edit Order
		<?php } ?>
  </li>
</ol>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <div class="page-heading">
                        <i class="glyphicon glyphicon-edit"></i> 
                        <?php if($_GET['o'] == 'add') { ?>
                            Add Order
                        <?php } else if($_GET['o'] == 'manord') { ?>
                            Manage Order
                            <a href="orders.php?o=add" class="btn btn-primary pull-right">
                                <i class="glyphicon glyphicon-plus-sign"></i> Add Order
                            </a>
                        <?php } else if($_GET['o'] == 'editOrd') { ?>
                            Edit Order
                        <?php } ?>
                    </div>
                </div>
                <div class="panel-body">
                    <?php if($_GET['o'] == 'add') { 
                        include('includes/add_order.php');
                    } else if($_GET['o'] == 'manord') { ?>
                        <div class="success-messages"></div>
                        <div class="row">
                            <div class="col-md-12">
                                <table class="table" id="manageOrderTable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Order Date</th>
                                            <th>FS Number</th>
                                            <th>Client Name</th>
                                            <th>Contact</th>
                                            <th>Grand Total</th>
                                            <th>Payment Status</th>
                                            <th>Options</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>

                        <?php if($_GET['o'] == 'manord') { ?>
                        <!-- Load DataTable initialization only for manage order page -->
                        <script>
                        // Function to print order
                        function printOrder(orderId) {
                            $.ajax({
                                url: 'php_action/printOrder.php',
                                type: 'POST',
                                data: {orderId: orderId},
                                success: function(response) {
                                    var win = window.open('', '_blank');
                                    win.document.write(response);
                                    win.document.close();
                                    win.print();
                                },
                                error: function(xhr, status, error) {
                                    alert('Error printing order: ' + error);
                                }
                            });
                        }

                        // Function to view order details
                        function viewOrder(orderId) {
                            console.log('Fetching order details for ID:', orderId);
                            $.ajax({
                                url: 'php_action/fetchOrderDetails.php',
                                type: 'POST',
                                data: {orderId: orderId},
                                dataType: 'json',
                                success: function(response) {
                                    console.log('Response received:', response);
                                    if(response.success) {
                                        // Populate modal with order details
                                        $('#orderDate').text(response.order.order_date);
                                        $('#fsNumber').text(response.order.fsnum || 'N/A');
                                        $('#clientName').text(response.order.client_name);
                                        $('#clientContact').text(response.order.client_contact);
                                        $('#clientTin').text(response.order.gstn || 'N/A');
                                        $('#paymentType').text(response.order.payment_type);
                                        
                                        // Clear and populate products table
                                        var productsHtml = '';
                                        if(response.items && response.items.length > 0) {
                                            console.log('Items found:', response.items.length);
                                            response.items.forEach(function(item) {
                                                productsHtml += '<tr>' +
                                                    '<td>' + (item.name || 'N/A') + '</td>' +
                                                    '<td>' + (item.quantity || '0') + '</td>' +
                                                    '<td>' + (item.rate || '0.00') + '</td>' +
                                                    '<td>' + (item.total || '0.00') + '</td>' +
                                                    '</tr>';
                                            });
                                        } else {
                                            console.log('No items found');
                                            productsHtml = '<tr><td colspan="4" class="text-center">No items found</td></tr>';
                                        }
                                        $('#orderItemsTable tbody').html(productsHtml);
                                        
                                        // Set payment status
                                        var paymentStatus = '';
                                        switch(response.order.payment_status) {
                                            case '1': paymentStatus = 'Full Payment'; break;
                                            case '2': paymentStatus = 'Advance Payment'; break;
                                            case '3': paymentStatus = 'No Payment'; break;
                                            default: paymentStatus = 'Unknown';
                                        }
                                        $('#paymentStatus').text(paymentStatus);
                                        
                                        // Set amounts
                                        $('#subTotal').text(parseFloat(response.order.sub_total).toFixed(2));
                                        $('#vat').text(parseFloat(response.order.vat).toFixed(2));
                                        
                                        if(response.order.withholding_tax_enabled == 1) {
                                            $('.withholding-row').show();
                                            $('#withholdingAmount').text(parseFloat(response.order.withholding_tax_amount).toFixed(2));
                                        } else {
                                            $('.withholding-row').hide();
                                        }
                                        
                                        $('#grandTotal').text(parseFloat(response.order.grand_total).toFixed(2));
                                        
                                        // Show modal
                                        $('#viewOrderModal').modal('show');
                                    } else {
                                        console.error('Error:', response.messages);
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error!',
                                            text: response.messages
                                        });
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error('Ajax error:', error);
                                    console.error('Status:', status);
                                    console.error('Response:', xhr.responseText);
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error!',
                                        text: 'Failed to fetch order details: ' + error
                                    });
                                }
                            });
                        }

                        // Function to remove order
                        function removeOrder(orderId) {
                            if(confirm('Are you sure you want to remove this order?')) {
                                $.ajax({
                                    url: 'php_action/removeOrder.php',
                                    type: 'POST',
                                    data: {orderId: orderId},
                                    dataType: 'json',
                                    success: function(response) {
                                        if(response.success) {
                                            manageOrderTable.ajax.reload(null, false);
                                            $('.remove-messages').html('<div class="alert alert-success">'+
                                                '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                                                '<strong><i class="glyphicon glyphicon-ok-sign"></i></strong> '+ response.messages +
                                                '</div>');
                                        } else {
                                            $('.remove-messages').html('<div class="alert alert-danger">'+
                                                '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                                                '<strong><i class="glyphicon glyphicon-remove-sign"></i></strong> '+ response.messages +
                                                '</div>');
                                        }
                                    },
                                    error: function(xhr, status, error) {
                                        $('.remove-messages').html('<div class="alert alert-danger">'+
                                            '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                                            '<strong><i class="glyphicon glyphicon-remove-sign"></i></strong> Error removing order: '+ error +
                                            '</div>');
                                    }
                                });
                            }
                        }
                        </script>

                        <!-- Load the DataTable initialization -->
                        <script src="custom/js/manageOrder.js"></script>
                        <?php } ?>

                        <!-- Payment Status Modal -->
                        <div class="modal fade" tabindex="-1" role="dialog" id="paymentOrderModal">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        <h4 class="modal-title"><i class="glyphicon glyphicon-edit"></i> Update Payment Status</h4>
                                    </div>
                                    <div class="modal-body">
                                        <div class="paymentOrderMessages"></div>
                                        <div class="form-group">
                                            <label for="paymentStatus" class="col-sm-3 control-label">Payment Status</label>
                                            <div class="col-sm-9">
                                                <select class="form-control" name="paymentStatus" id="paymentStatus">
                                                    <option value="">~~SELECT~~</option>
                                                    <option value="1">Full Payment</option>
                                                    <option value="2">Advance Payment</option>
                                                    <option value="3">No Payment</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default" data-dismiss="modal"><i class="glyphicon glyphicon-remove-sign"></i> Close</button>
                                        <button type="button" class="btn btn-primary" id="updatePaymentStatusBtn" data-loading-text="Loading..."><i class="glyphicon glyphicon-ok-sign"></i> Save Changes</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- View Order Modal -->
                        <div class="modal fade" id="viewOrderModal" tabindex="-1" role="dialog">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        <h4 class="modal-title"><i class="glyphicon glyphicon-eye-open"></i> Order Details</h4>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <table class="table table-bordered">
                                                    <tr>
                                                        <th>Order Date</th>
                                                        <td id="orderDate"></td>
                                                    </tr>
                                                    <tr>
                                                        <th>FS Number</th>
                                                        <td id="fsNumber"></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Client Name</th>
                                                        <td id="clientName"></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Contact</th>
                                                        <td id="clientContact"></td>
                                                    </tr>
                                                    <tr>
                                                        <th>TIN Number</th>
                                                        <td id="clientTin"></td>
                                                    </tr>
                                                </table>
                                            </div>
                                            <div class="col-md-6">
                                                <table class="table table-bordered">
                                                    <tr>
                                                        <th>Payment Type</th>
                                                        <td id="paymentType"></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Payment Status</th>
                                                        <td id="paymentStatus"></td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-12">
                                                <h4>Order Items</h4>
                                                <table class="table table-bordered" id="orderItemsTable">
                                                    <thead>
                                                        <tr>
                                                            <th>Product</th>
                                                            <th>Quantity</th>
                                                            <th>Rate</th>
                                                            <th>Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody></tbody>
                                                </table>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 col-md-offset-6">
                                                <table class="table table-bordered">
                                                    <tr>
                                                        <th>Sub Total</th>
                                                        <td id="subTotal"></td>
                                                    </tr>
                                                    <tr>
                                                        <th>VAT (15%)</th>
                                                        <td id="vat"></td>
                                                    </tr>
                                                    <tr class="withholding-row">
                                                        <th>Withholding Tax (2%)</th>
                                                        <td id="withholdingAmount"></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Grand Total</th>
                                                        <td id="grandTotal"></td>
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

                    <?php 
                    // /else manage order
                    } else if($_GET['o'] == 'editOrd') {
                        // get order
                        include('includes/edit_order.php');
                    } // /get order else  ?>

                </div> <!--/panel-->	
            </div> <!--/panel-->	
        </div>
    </div>
</div>


<!-- edit order -->
<div class="modal fade" tabindex="-1" role="dialog" id="paymentOrderModal">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><i class="glyphicon glyphicon-edit"></i> Edit Payment</h4>
      </div>      

      <div class="modal-body form-horizontal" style="max-height:500px; overflow:auto;" >

      	<div class="paymentOrderMessages"></div>

      	     				 				 
			  <div class="form-group">
			    <label for="due" class="col-sm-3 control-label">Due Amount</label>
			    <div class="col-sm-9">
			      <input type="text" class="form-control" id="due" name="due" disabled="true" />					
			    </div>
			  </div> <!--/form-group-->		
			  <div class="form-group">
			    <label for="payAmount" class="col-sm-3 control-label">Pay Amount</label>
			    <div class="col-sm-9">
			      <input type="text" class="form-control" id="payAmount" name="payAmount"/>					      
			    </div>
			  </div> <!--/form-group-->		
			  <div class="form-group">
			    <label for="clientContact" class="col-sm-3 control-label">Payment Type</label>
			    <div class="col-sm-9">
			      <select class="form-control" name="paymentType" id="paymentType" >
			      	<option value="">~~SELECT~~</option>
			      	<option value="1">Cheque</option>
			      	<option value="2">Cash</option>
			      	<option value="3">Credit Card</option>
			      </select>
			    </div>
			  </div> <!--/form-group-->							  
			  <div class="form-group">
			    <label for="clientContact" class="col-sm-3 control-label">Payment Status</label>
			    <div class="col-sm-9">
			      <select class="form-control" name="paymentStatus" id="paymentStatus">
			      	<option value="">~~SELECT~~</option>
			      	<option value="1">Full Payment</option>
			      	<option value="2">Advance Payment</option>
			      	<option value="3">No Payment</option>
			      </select>
			    </div>
			  </div> <!--/form-group-->							  				  
      	        
      </div> <!--/modal-body-->
      <div class="modal-footer">
      	<button type="button" class="btn btn-default" data-dismiss="modal"> <i class="glyphicon glyphicon-remove-sign"></i> Close</button>
        <button type="button" class="btn btn-primary" id="updatePaymentOrderBtn" data-loading-text="Loading..."> <i class="glyphicon glyphicon-ok-sign"></i> Save changes</button>	
      </div>           
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<!-- /edit order-->

<!-- remove order -->
<div class="modal fade" tabindex="-1" role="dialog" id="removeOrderModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><i class="glyphicon glyphicon-trash"></i> Remove Order</h4>
      </div>
      <div class="modal-body">

      	<div class="removeOrderMessages"></div>

        <p>Do you really want to remove ?</p>
      </div>
      <div class="modal-footer removeProductFooter">
        <button type="button" class="btn btn-default" data-dismiss="modal"> <i class="glyphicon glyphicon-remove-sign"></i> Close</button>
        <button type="button" class="btn btn-primary" id="removeOrderBtn" data-loading-text="Loading..."> <i class="glyphicon glyphicon-ok-sign"></i> Save changes</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<!-- /remove order-->


<script>
var productList = '<?php 
    $productSql = "SELECT * FROM products WHERE status = 1 AND quantity > 0";
    $productData = $connect->query($productSql);
    while($row = $productData->fetch_array()) {
        echo '<option value="'.$row['product_id'].'">'.$row['product_name'].'</option>';
    }
?>';
</script>
<script src="custom/js/order.js"></script>

<script>
$(document).ready(function() {
    // Date picker
    $("#orderDate").datepicker({
        dateFormat: 'yy-mm-dd',
        autoclose: true
    });

    // Set default date to today
    var today = new Date();
    var dd = String(today.getDate()).padStart(2, '0');
    var mm = String(today.getMonth() + 1).padStart(2, '0');
    var yyyy = today.getFullYear();
    today = yyyy + '-' + mm + '-' + dd;
    $('#orderDate').val(today);
});
</script>

<script>
function calculateSubTotal() {
    var tableProductLength = $("#productTable tbody tr").length;
    var totalSubAmount = 0;
    
    for(var x = 0; x < tableProductLength; x++) {
        var tr = $("#productTable tbody tr")[x];
        var count = $(tr).attr('id');
        count = count.substring(3);
        
        var total = $("#total"+count).val() || 0;
        totalSubAmount = Number(totalSubAmount) + Number(total);
    }

    // Sub total
    $("#subTotal").val(totalSubAmount.toFixed(2));
    $("#subTotalValue").val(totalSubAmount.toFixed(2));

    // VAT 15%
    var vat = (totalSubAmount * 0.15);
    $("#vat").val(vat.toFixed(2));
    $("#vatValue").val(vat.toFixed(2));

    // Total Amount (subtotal + VAT)
    var totalAmount = totalSubAmount + vat;
    $("#totalAmount").val(totalAmount.toFixed(2));
    $("#totalAmountValue").val(totalAmount.toFixed(2));

    // Grand Total (same as total amount)
    $("#grandTotal").val(totalAmount.toFixed(2));
    $("#grandTotalValue").val(totalAmount.toFixed(2));
}

function getProductData(row = null) {
    if(row) {
        var productId = $("#productName"+row).val();      
        
        if(productId == "") {
            $("#rate"+row).val("");
            $("#rateValue"+row).val("");
            $("#quantity"+row).val("");
            $("#total"+row).val("");
            $("#totalValue"+row).val("");
        } else {
            $.ajax({
                url: 'php_action/fetchSelectedProduct.php',
                type: 'post',
                data: {productId: productId},
                dataType: 'json',
                success: function(response) {
                    $("#rate"+row).val(response.rate);
                    $("#rateValue"+row).val(response.rate);
                    $("#quantity"+row).val(1);
                    $("#available_quantity"+row).text(response.quantity);
                    
                    var total = Number(response.rate) * 1;
                    $("#total"+row).val(total);
                    $("#totalValue"+row).val(total);
                    
                    calculateSubTotal();
                },
                error: function(xhr, status, error) {
                    console.error("Error:", error);
                    alert("Error fetching product data");
                }
            });
        }
    }
}

function getTotal(row = null) {
    if(row) {
        var total = Number($("#rate"+row).val()) * Number($("#quantity"+row).val());
        total = total.toFixed(2);
        $("#total"+row).val(total);
        $("#totalValue"+row).val(total);
        
        calculateSubTotal();
    }
}
function addRow() {
    var tableLength = $("#productTable tbody tr").length;
    var tableRow = tableLength + 1;
    
    var tr = '<tr id="row'+tableRow+'" class="'+tableRow+'">'+
        '<td>'+
        '<select class="form-control" name="productName[]" id="productName'+tableRow+'" onchange="getProductData('+tableRow+')" >'+
        '<option value="">~~SELECT~~</option>'+
        <?php
        $productSql = "SELECT * FROM products WHERE status = 1 AND quantity > 0";
        $productData = $connect->query($productSql);
        while($row = $productData->fetch_array()) {
            echo "'<option value=\"".$row['product_id']."\">".$row['name']."</option>'+";
        }
        ?>
        '</select>'+
        '</td>'+
        '<td style="padding-left:20px;">'+
        '<input type="text" name="rate[]" id="rate'+tableRow+'" autocomplete="off" class="form-control" disabled="true" />'+
        '<input type="hidden" name="rateValue[]" id="rateValue'+tableRow+'" autocomplete="off" class="form-control" />'+
        '</td>'+
        '<td style="padding-left:20px;"><p id="available_quantity'+tableRow+'"></p></td>'+
        '<td style="padding-left:20px;">'+
        '<input type="number" name="quantity[]" id="quantity'+tableRow+'" onkeyup="getTotal('+tableRow+')" class="form-control" min="1" />'+
        '</td>'+
        '<td style="padding-left:20px;">'+
        '<input type="text" name="total[]" id="total'+tableRow+'" autocomplete="off" class="form-control" disabled="true" />'+
        '<input type="hidden" name="totalValue[]" id="totalValue'+tableRow+'" autocomplete="off" class="form-control" />'+
        '</td>'+
        '<td>'+
        '<button class="btn btn-danger removeProductRowBtn" type="button" onclick="removeProductRow('+tableRow+')"><i class="glyphicon glyphicon-trash"></i></button>'+
        '</td>'+
        '</tr>';
    
    if(tableLength > 0) {
        $("#productTable tbody tr:last").after(tr);
    } else {
        $("#productTable tbody").append(tr);
    }
}
</script>


<style>
/* Table Container and Panel */
.panel-default {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin: 20px 0;
    background: #fff;
}

.panel-heading {
    background: #f8f9fa !important;
    border-bottom: 2px solid #e9ecef;
    padding: 15px 20px;
    position: relative;
}

.panel-body {
    padding: 20px;
}

/* DataTable Styling */
#manageOrderTable {
    width: 100% !important;
    margin-bottom: 0;
    border-collapse: separate;
    border-spacing: 0;
}

#manageOrderTable thead th {
    background-color: #f8f9fa;
    color: #495057;
    font-weight: 600;
    padding: 12px 8px;
    border-bottom: 2px solid #dee2e6;
    white-space: nowrap;
    font-size: 13px;
}

#manageOrderTable tbody td {
    padding: 10px 8px;
    vertical-align: middle;
    border-bottom: 1px solid #eee;
    font-size: 13px;
}

#manageOrderTable tbody tr:hover {
    background-color: rgba(0,0,0,0.02);
}

/* DataTable Controls */
.dataTables_wrapper .dataTables_filter {
    margin-bottom: 15px;
}

.dataTables_wrapper .dataTables_filter input {
    border: 1px solid #dce4ec;
    border-radius: 4px;
    padding: 6px 12px;
    margin-left: 8px;
    width: 200px;
}

.dataTables_wrapper .dataTables_length select {
    border: 1px solid #dce4ec;
    border-radius: 4px;
    padding: 6px 30px 6px 10px;
    margin: 0 5px;
}

/* Pagination */
.dataTables_wrapper .dataTables_paginate {
    margin-top: 15px;
}

.dataTables_wrapper .dataTables_paginate .paginate_button {
    padding: 5px 10px;
    margin: 0 2px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    background: #fff;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: #007bff;
    border-color: #007bff;
    color: #fff !important;
}

.dataTables_wrapper .dataTables_paginate .paginate_button:hover:not(.current) {
    background: #e9ecef;
    border-color: #dee2e6;
    color: #000 !important;
}

/* Status Labels */
.label {
    display: inline-block;
    padding: 4px 8px;
    font-size: 12px;
    font-weight: 500;
    line-height: 1;
    border-radius: 3px;
    text-align: center;
    white-space: nowrap;
}

.label-success { background-color: #28a745; color: #fff; }
.label-warning { background-color: #ffc107; color: #000; }
.label-danger { background-color: #dc3545; color: #fff; }

/* Action Buttons */
.btn-action {
    padding: 4px 8px;
    margin: 0 2px;
    font-size: 12px;
    border-radius: 3px;
    border: 1px solid transparent;
}

.btn-action:hover {
    opacity: 0.85;
}

/* Responsive Design */
@media screen and (max-width: 768px) {
    .container-fluid {
        padding-right: 15px;
        padding-left: 15px;
    }

    .panel-body {
        padding: 15px;
    }

    #manageOrderTable {
        display: block;
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    #manageOrderTable thead th,
    #manageOrderTable tbody td {
        white-space: nowrap;
        min-width: 100px;
    }

    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        float: none;
        text-align: left;
        margin-bottom: 10px;
    }

    .dataTables_wrapper .dataTables_filter input {
        width: calc(100% - 60px);
        margin-left: 5px;
    }

    .dataTables_wrapper .dataTables_paginate {
        text-align: center;
        margin-top: 10px;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 4px 8px;
        margin: 0 1px;
    }
}

/* Amount Formatting */
.amount-column {
    text-align: right;
    font-family: 'Roboto Mono', monospace;
}

/* Options Column */
.options-column {
    white-space: nowrap;
    text-align: center;
}

/* Search Box Enhancement */
.dataTables_filter input:focus {
    border-color: #80bdff;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}
</style>

<!-- Notification Popup Div -->
<div id="notification-popup" class="notification-popup">
    <span id="notification-message"></span>
</div>

<!-- Consolidated JavaScript -->
<script>
$(document).ready(function() {
    // Format numbers in amount columns
    $('.amount-column').each(function() {
        var num = parseFloat($(this).text());
        if(!isNaN(num)) {
            $(this).text(num.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }));
        }
    });

    // Enhanced hover effects
    $('#manageOrderTable tbody tr').hover(
        function() { $(this).addClass('hover'); },
        function() { $(this).removeClass('hover'); }
    );
});

function showNotification(message, type = 'success') {
    const popup = $('#notification-popup');
    const messageSpan = $('#notification-message');
    
    popup.removeClass('notification-success notification-error')
         .addClass(type === 'success' ? 'notification-success' : 'notification-error');
    
    messageSpan.html(message);
    popup.fadeIn(300);
    
    setTimeout(() => popup.fadeOut(300), 3000);
}

// Update the edit form submission
$('#editOrderForm').submit(function(e) {
    e.preventDefault();
    
    $.ajax({
        url: 'php_action/editOrder.php',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                showNotification('Order updated successfully', 'success');
                setTimeout(function() {
                    window.location.href = 'orders.php?o=manord';
                }, 1500);
            } else {
                showNotification(response.messages, 'error');
            }
        },
        error: function(xhr, status, error) {
            showNotification('Error updating order: ' + error, 'error');
        }
    });
});

// Update the remove order function
function removeOrder(orderId) {
    if(confirm('Are you sure you want to remove this order?')) {
        $.ajax({
            url: 'php_action/removeOrder.php',
            type: 'post',
            data: {orderId: orderId},
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    showNotification('Order successfully removed', 'success');
                    manageCategoriesTable.ajax.reload(null, false);
                } else {
                    showNotification(response.messages, 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotification('Error removing order: ' + error, 'error');
            }
        });
    }
}
</script>

<script src="custom/js/product-main.js"></script>

<?php require_once 'includes/footer.php'; ?>
</body>
</html> 

	