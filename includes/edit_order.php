<?php
// Check if this file is being accessed directly
if(!defined('BASEPATH')) exit('No direct script access allowed');

$orderId = $_GET['i'];

// Get order details with user information
$sql = "SELECT o.*, u.username 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.user_id 
        WHERE o.order_id = ?";

$stmt = $connect->prepare($sql);
$stmt->bind_param("i", $orderId);
$stmt->execute();
$result = $stmt->get_result();
$orderData = $result->fetch_assoc();

if(!$orderData) {
    echo "<div class='alert alert-danger'>";
    echo "<button type='button' class='close' data-dismiss='alert'>&times;</button>";
    echo "<strong>Error!</strong> Order not found.";
    echo "</div>";
    exit();
}

// Get order items with product information
$orderItemSql = "SELECT oi.*, p.name as product_name, p.product_id, p.current_stock as available_quantity, p.selling_price as rate
                FROM order_items oi
                INNER JOIN products p ON oi.product_id = p.product_id 
                WHERE oi.order_id = ?";

try {
    $itemStmt = $connect->prepare($orderItemSql);
    if (!$itemStmt) {
        throw new Exception("Prepare failed: " . $connect->error);
    }
    
    $itemStmt->bind_param("i", $orderId);
    $itemStmt->execute();
    $orderItems = $itemStmt->get_result();

    // Fetch all products for the dropdown
    $productSql = "SELECT product_id, name, current_stock, selling_price 
                   FROM products 
                   WHERE status = 'active'
                   ORDER BY name ASC";
    $allProducts = $connect->query($productSql);
    $productList = array();
    while($prod = $allProducts->fetch_assoc()) {
        $productList[] = $prod;
    }

} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<button type='button' class='close' data-dismiss='alert'>&times;</button>";
    echo "<strong>Error!</strong> " . $e->getMessage();
    echo "</div>";
    error_log("Order items query error: " . $e->getMessage());
    exit();
}

// If no order items exist, create an empty row
if($orderItems->num_rows == 0) {
    $emptyRow = true;
} else {
    $emptyRow = false;
}
?>

<div class="success-messages"></div>

<form class="form-horizontal" method="POST" action="php_action/editOrder.php" id="editOrderForm">
    <div class="form-group">
        <label for="orderDate" class="col-sm-2 control-label">Order Date</label>
        <div class="col-sm-10">
            <input type="text" class="form-control" id="orderDate" name="orderDate" 
                   value="<?php echo isset($orderData['order_date']) ? htmlspecialchars($orderData['order_date']) : ''; ?>" 
                   autocomplete="off" />
        </div>
    </div>

    <div class="form-group">
        <label for="clientName" class="col-sm-2 control-label">Client Name</label>
        <div class="col-sm-10">
            <input type="text" class="form-control" id="clientName" name="clientName" 
                   value="<?php echo isset($orderData['client_name']) ? htmlspecialchars($orderData['client_name']) : ''; ?>" 
                   autocomplete="off" />
        </div>
    </div>

    <div class="form-group">
        <label for="clientContact" class="col-sm-2 control-label">Client Contact</label>
        <div class="col-sm-10">
            <input type="text" class="form-control" id="clientContact" name="clientContact" 
                   value="<?php echo isset($orderData['client_contact']) ? htmlspecialchars($orderData['client_contact']) : ''; ?>" 
                   autocomplete="off" />
        </div>
    </div>

    <div class="form-group">
        <label for="clientTin" class="col-sm-2 control-label">Client TIN</label>
        <div class="col-sm-10">
            <input type="text" class="form-control" id="clientTin" name="clientTin" 
                   value="<?php echo isset($orderData['gstn']) ? htmlspecialchars($orderData['gstn']) : ''; ?>" 
                   autocomplete="off" />
        </div>
    </div>

    <table class="table" id="productTable">
        <thead>
            <tr>
                <th style="width:40%;">Product</th>
                <th style="width:20%;">Price</th>
                <th style="width:15%;">Available Quantity</th>
                <th style="width:15%;">Quantity</th>
                <th style="width:15%;">Total</th>
                <th style="width:10%;"></th>
            </tr>
        </thead>
        <tbody>
            <?php
            if($emptyRow) {
                // Display empty row if no items exist
                $arrayNumber = 0;
                ?>
                <tr id="row<?php echo $arrayNumber; ?>">
                    <td style="margin-left:20px;">
                        <div class="form-group">
                            <select class="form-control" name="productName[]" id="productName<?php echo $arrayNumber; ?>" onchange="getProductData(<?php echo $arrayNumber; ?>)">
                                <option value="">~~SELECT~~</option>
                                <?php
                                foreach($productList as $product) {
                                    echo "<option value='".$product['product_id']."' 
                                          data-price='".$product['selling_price']."' 
                                          data-stock='".$product['current_stock']."'>".
                                         htmlspecialchars($product['name'])."</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </td>
                    <td style="padding-left:20px;">
                        <input type="text" name="rate[]" id="rate<?php echo $arrayNumber; ?>" class="form-control" readonly />
                        <input type="hidden" name="rateValue[]" id="rateValue<?php echo $arrayNumber; ?>" />
                    </td>
                    <td style="padding-left:20px;">
                        <div class="form-group">
                            <p id="available_quantity<?php echo $arrayNumber; ?>"></p>
                        </div>
                    </td>
                    <td style="padding-left:20px;">
                        <div class="form-group">
                            <input type="number" name="quantity[]" id="quantity<?php echo $arrayNumber; ?>" 
                                   onkeyup="getTotal(<?php echo $arrayNumber; ?>)" class="form-control" min="1" />
                        </div>
                    </td>
                    <td style="padding-left:20px;">
                        <input type="text" name="total[]" id="total<?php echo $arrayNumber; ?>" class="form-control" readonly />
                        <input type="hidden" name="totalValue[]" id="totalValue<?php echo $arrayNumber; ?>" />
                    </td>
                    <td>
                        <button class="btn btn-danger removeProductRowBtn" type="button" onclick="removeProductRow(<?php echo $arrayNumber; ?>)">
                            <i class="glyphicon glyphicon-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php
            } else {
                $arrayNumber = 0;
                while($row = $orderItems->fetch_assoc()) { ?>
                    <tr id="row<?php echo $arrayNumber; ?>">
                        <td style="margin-left:20px;">
                            <div class="form-group">
                                <select class="form-control" name="productName[]" id="productName<?php echo $arrayNumber; ?>" onchange="getProductData(<?php echo $arrayNumber; ?>)">
                                    <option value="">~~SELECT~~</option>
                                    <?php
                                    foreach($productList as $product) {
                                        $selected = ($product['product_id'] == $row['product_id']) ? 'selected' : '';
                                        echo "<option value='".$product['product_id']."' ".$selected." 
                                              data-price='".$product['selling_price']."' 
                                              data-stock='".$product['current_stock']."'>".
                                             htmlspecialchars($product['name'])."</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </td>
                        <td style="padding-left:20px;">
                            <input type="text" name="rate[]" id="rate<?php echo $arrayNumber; ?>" class="form-control" value="<?php echo $row['rate']; ?>" readonly />
                            <input type="hidden" name="rateValue[]" id="rateValue<?php echo $arrayNumber; ?>" value="<?php echo $row['rate']; ?>" />
                        </td>
                        <td style="padding-left:20px;">
                            <div class="form-group">
                                <p id="available_quantity<?php echo $arrayNumber; ?>"><?php echo $row['available_quantity']; ?></p>
                            </div>
                        </td>
                        <td style="padding-left:20px;">
                            <div class="form-group">
                                <input type="number" name="quantity[]" id="quantity<?php echo $arrayNumber; ?>" 
                                       onkeyup="getTotal(<?php echo $arrayNumber; ?>)" class="form-control"
                                       value="<?php echo $row['quantity']; ?>" min="1" />
                            </div>
                        </td>
                        <td style="padding-left:20px;">
                            <input type="text" name="total[]" id="total<?php echo $arrayNumber; ?>" 
                                   class="form-control" value="<?php echo $row['total']; ?>" readonly />
                            <input type="hidden" name="totalValue[]" id="totalValue<?php echo $arrayNumber; ?>" 
                                   value="<?php echo $row['total']; ?>" />
                        </td>
                        <td>
                            <button class="btn btn-danger removeProductRowBtn" type="button" onclick="removeProductRow(<?php echo $arrayNumber; ?>)">
                                <i class="glyphicon glyphicon-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php
                $arrayNumber++;
                }
            }
            ?>
        </tbody>
    </table>

    <div class="col-md-6">
        <div class="form-group">
            <label for="subTotal" class="col-sm-3 control-label">Sub Amount</label>
            <div class="col-sm-9">
                <input type="text" class="form-control" id="subTotal" name="subTotal" disabled="true" 
                       value="<?php echo isset($orderData['sub_total']) ? $orderData['sub_total'] : ''; ?>" />
                <input type="hidden" class="form-control" id="subTotalValue" name="subTotalValue" 
                       value="<?php echo isset($orderData['sub_total']) ? $orderData['sub_total'] : ''; ?>" />
            </div>
        </div>

        <div class="form-group">
            <label for="totalAmount" class="col-sm-3 control-label">Total Amount</label>
            <div class="col-sm-9">
                <input type="text" class="form-control" id="totalAmount" name="totalAmount" disabled="true" 
                       value="<?php echo isset($orderData['total_amount']) ? $orderData['total_amount'] : ''; ?>" />
                <input type="hidden" class="form-control" id="totalAmountValue" name="totalAmountValue" 
                       value="<?php echo isset($orderData['total_amount']) ? $orderData['total_amount'] : ''; ?>" />
            </div>
        </div>

        <div class="form-group">
            <label for="vat" class="col-sm-3 control-label">VAT 15%</label>
            <div class="col-sm-9">
                <input type="text" class="form-control" id="vat" name="vat" disabled="true" 
                       value="<?php echo isset($orderData['vat']) ? $orderData['vat'] : ''; ?>" />
                <input type="hidden" class="form-control" id="vatValue" name="vatValue" 
                       value="<?php echo isset($orderData['vat']) ? $orderData['vat'] : ''; ?>" />
            </div>
        </div>

        <div class="form-group">
            <label for="withholding" class="col-sm-3 control-label">Withholding Tax</label>
            <div class="col-sm-9">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" id="withholding_enabled" name="withholding_enabled" 
                               <?php echo isset($orderData['withholding_tax_enabled']) && $orderData['withholding_tax_enabled'] ? 'checked' : ''; ?>> 
                        Enable 2% Withholding Tax
                    </label>
                </div>
            </div>
        </div>

        <div class="form-group withholding-amount" style="display:<?php echo isset($orderData['withholding_tax_enabled']) && $orderData['withholding_tax_enabled'] ? 'block' : 'none'; ?>;">
            <label for="withholding_amount" class="col-sm-3 control-label">Withholding Amount</label>
            <div class="col-sm-9">
                <input type="text" class="form-control" id="withholding_amount" name="withholding_amount" readonly="true" 
                       value="<?php echo isset($orderData['withholding_tax_amount']) ? $orderData['withholding_tax_amount'] : '0.00'; ?>" />
                <input type="hidden" class="form-control" id="withholdingValue" name="withholdingValue" 
                       value="<?php echo isset($orderData['withholding_tax_amount']) ? $orderData['withholding_tax_amount'] : '0.00'; ?>" />
            </div>
        </div>

        <div class="form-group">
            <label for="grandTotal" class="col-sm-3 control-label">Grand Total</label>
            <div class="col-sm-9">
                <input type="text" class="form-control" id="grandTotal" name="grandTotal" disabled="true" 
                       value="<?php echo isset($orderData['grand_total']) ? $orderData['grand_total'] : ''; ?>" />
                <input type="hidden" class="form-control" id="grandTotalValue" name="grandTotalValue" 
                       value="<?php echo isset($orderData['grand_total']) ? $orderData['grand_total'] : ''; ?>" />
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="paymentType" class="col-sm-3 control-label">Payment Type</label>
            <div class="col-sm-9">
                <select class="form-control" name="paymentType" id="paymentType">
                    <option value="">~~SELECT~~</option>
                    <option value="Cheque" <?php echo isset($orderData['payment_type']) && $orderData['payment_type'] == 'Cheque' ? 'selected' : ''; ?>>Cheque</option>
                    <option value="Cash" <?php echo isset($orderData['payment_type']) && $orderData['payment_type'] == 'Cash' ? 'selected' : ''; ?>>Cash</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="paymentStatus" class="col-sm-3 control-label">Payment Status</label>
            <div class="col-sm-9">
                <select class="form-control" name="paymentStatus" id="paymentStatus">
                    <option value="">~~SELECT~~</option>
                    <option value="1" <?php echo isset($orderData['payment_status']) && $orderData['payment_status'] == 1 ? 'selected' : ''; ?>>Full Payment</option>
                    <option value="2" <?php echo isset($orderData['payment_status']) && $orderData['payment_status'] == 2 ? 'selected' : ''; ?>>Advance Payment</option>
                    <option value="3" <?php echo isset($orderData['payment_status']) && $orderData['payment_status'] == 3 ? 'selected' : ''; ?>>No Payment</option>
                </select>
            </div>
        </div>
    </div>

    <div class="form-group editButtonFooter">
        <div class="col-sm-offset-2 col-sm-10">
            <button type="button" class="btn btn-default" onclick="addRow()" id="addRowBtn" data-loading-text="Loading...">
                <i class="glyphicon glyphicon-plus-sign"></i> Add Row
            </button>

            <input type="hidden" name="orderId" id="orderId" value="<?php echo $_GET['i']; ?>" />

            <button type="submit" id="editOrderBtn" data-loading-text="Loading..." class="btn btn-success">
                <i class="glyphicon glyphicon-ok-sign"></i> Save Changes
            </button>
        </div>
    </div>
</form>

<script>
function getProductData(row) {
    var productSelect = $("#productName" + row);
    var selectedOption = productSelect.find('option:selected');
    
    if(selectedOption.val() == "") {
        $("#rate" + row).val("");
        $("#rateValue" + row).val("");
        $("#available_quantity" + row).text("");
        $("#quantity" + row).val("");
        $("#total" + row).val("");
        $("#totalValue" + row).val("");
    } else {
        var price = selectedOption.data('price');
        var stock = selectedOption.data('stock');
        
        $("#rate" + row).val(price);
        $("#rateValue" + row).val(price);
        $("#available_quantity" + row).text(stock);
        $("#quantity" + row).val(1);
        
        var total = price * 1;
        $("#total" + row).val(total.toFixed(2));
        $("#totalValue" + row).val(total.toFixed(2));
        
        calculateSubTotal();
    }
}

function calculateSubTotal() {
    var tableProductLength = $("#productTable tbody tr").length;
    var totalSubAmount = 0;
    
    for(var x = 0; x < tableProductLength; x++) {
        var tr = $("#productTable tbody tr")[x];
        var count = $(tr).attr('id');
        count = count.substring(3);
        
        totalSubAmount += Number($("#total" + count).val());
    }

    // Sub total
    $("#subTotal").val(totalSubAmount.toFixed(2));
    $("#subTotalValue").val(totalSubAmount.toFixed(2));

    // VAT 15%
    var vat = (totalSubAmount * 0.15);
    $("#vat").val(vat.toFixed(2));
    $("#vatValue").val(vat.toFixed(2));

    // Total Amount
    var totalAmount = totalSubAmount + vat;
    $("#totalAmount").val(totalAmount.toFixed(2));
    $("#totalAmountValue").val(totalAmount.toFixed(2));

    // Check if withholding tax is enabled
    if($("#withholding_enabled").is(":checked")) {
        var withholdingAmount = totalSubAmount * 0.02;
        $("#withholding_amount").val(withholdingAmount.toFixed(2));
        $("#withholdingValue").val(withholdingAmount.toFixed(2));
        totalAmount -= withholdingAmount;
    }

    // Grand Total
    $("#grandTotal").val(totalAmount.toFixed(2));
    $("#grandTotalValue").val(totalAmount.toFixed(2));
}

function getTotal(row) {
    var total = Number($("#rate" + row).val()) * Number($("#quantity" + row).val());
    total = total.toFixed(2);
    $("#total" + row).val(total);
    $("#totalValue" + row).val(total);
    
    calculateSubTotal();
}

// Add event listener for withholding tax checkbox
$("#withholding_enabled").change(function() {
    if($(this).is(":checked")) {
        $(".withholding-amount").show();
    } else {
        $(".withholding-amount").hide();
        $("#withholding_amount").val("0.00");
        $("#withholdingValue").val("0.00");
    }
    calculateSubTotal();
});

// Initialize datepicker
$(document).ready(function() {
    $("#orderDate").datepicker({
        dateFormat: 'yy-mm-dd'
    });

    // Form submission
    $("#editOrderForm").submit(function(e) {
        e.preventDefault();
        
        var $submitBtn = $("#editOrderBtn");
        $submitBtn.prop('disabled', true);
        $submitBtn.html('<i class="glyphicon glyphicon-refresh glyphicon-refresh-animate"></i> Loading...');
        
        $.ajax({
            url: 'php_action/editOrder.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    // Show success message using SweetAlert
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.messages,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(function() {
                        // Redirect to orders page
                        window.location.href = 'orders.php?o=manord';
                    });
                } else {
                    // Show error message
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.messages
                    });
                    $submitBtn.prop('disabled', false);
                    $submitBtn.html('<i class="glyphicon glyphicon-ok-sign"></i> Save Changes');
                }
            },
            error: function(xhr, status, error) {
                // Show error message
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred while updating the order: ' + error
                });
                $submitBtn.prop('disabled', false);
                $submitBtn.html('<i class="glyphicon glyphicon-ok-sign"></i> Save Changes');
            }
        });
    });
});

function removeProductRow(row = null) {
    if(row) {
        $("#row"+row).remove();
        calculateSubTotal();
    }
}

function addRow() {
    var tableLength = $("#productTable tbody tr").length;
    var tableRow = tableLength + 1;
    
    var tr = '<tr id="row'+tableRow+'">'+
        '<td style="margin-left:20px;">'+
        '<div class="form-group">'+
        '<select class="form-control" name="productName[]" id="productName'+tableRow+'" onchange="getProductData('+tableRow+')" >'+
        '<option value="">~~SELECT~~</option>';
        
    <?php
    $productSql = "SELECT product_id, name, current_stock, selling_price 
                   FROM products 
                   WHERE status = 'active'
                   ORDER BY name ASC";
    $productData = $connect->query($productSql);
    while($row = $productData->fetch_array()) {
        echo 'tr += \'<option value="'.$row['product_id'].'" data-price="'.$row['selling_price'].'" data-stock="'.$row['current_stock'].'">'.$row['name'].'</option>\';';
    }
    ?>

    tr += '</select>'+
        '</div>'+
        '</td>'+
        '<td style="padding-left:20px;">'+
        '<input type="text" name="rate[]" id="rate'+tableRow+'" class="form-control" readonly />'+
        '<input type="hidden" name="rateValue[]" id="rateValue'+tableRow+'" />'+
        '</td>'+
        '<td style="padding-left:20px;">'+
        '<div class="form-group">'+
        '<p id="available_quantity'+tableRow+'"></p>'+
        '</div>'+
        '</td>'+
        '<td style="padding-left:20px;">'+
        '<div class="form-group">'+
        '<input type="number" name="quantity[]" id="quantity'+tableRow+'" onkeyup="getTotal('+tableRow+')" class="form-control" min="1" />'+
        '</div>'+
        '</td>'+
        '<td style="padding-left:20px;">'+
        '<input type="text" name="total[]" id="total'+tableRow+'" class="form-control" readonly />'+
        '<input type="hidden" name="totalValue[]" id="totalValue'+tableRow+'" />'+
        '</td>'+
        '<td>'+
        '<button class="btn btn-danger removeProductRowBtn" type="button" onclick="removeProductRow('+tableRow+')">'+
        '<i class="glyphicon glyphicon-trash"></i>'+
        '</button>'+
        '</td>'+
        '</tr>';
    
    if(tableLength > 0) {
        $("#productTable tbody tr:last").after(tr);
    } else {
        $("#productTable tbody").append(tr);
    }
}
</script>

<!-- Add SweetAlert2 CSS and JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.all.min.js"></script>

<!-- Add custom styles for SweetAlert and loading animation -->
<style>
.swal2-popup {
    font-size: 1.6rem !important;
}
.swal2-title {
    font-size: 2rem !important;
}
.swal2-content {
    font-size: 1.6rem !important;
}
.swal2-styled {
    font-size: 1.4rem !important;
}

.glyphicon-refresh-animate {
    animation: spin .7s infinite linear;
    -webkit-animation: spin2 .7s infinite linear;
}

@-webkit-keyframes spin2 {
    from { -webkit-transform: rotate(0deg);}
    to { -webkit-transform: rotate(360deg);}
}

@keyframes spin {
    from { transform: scale(1) rotate(0deg);}
    to { transform: scale(1) rotate(360deg);}
}
</style> 