<?php
// Check if this file is being accessed directly
if(!defined('BASEPATH')) exit('No direct script access allowed');
?>

<div class="success-messages"></div>

<form class="form-horizontal" method="POST" action="php_action/createOrder.php" id="createOrderForm">
    <div class="form-group">
        <label for="orderDate" class="col-sm-2 control-label">Date</label>
        <div class="col-sm-10">
            <input type="text" class="form-control datepicker" id="orderDate" name="orderDate" autocomplete="off" required />
        </div>
    </div>
    
    <div class="form-group">
        <label for="FsNum" class="col-sm-2 control-label">FS Number</label>
        <div class="col-sm-10">
            <input type="text" class="form-control" id="FsNum" name="FsNum" placeholder="FS Number" autocomplete="off" />
        </div>
    </div>
    
    <div class="form-group">
        <label for="clientId" class="col-sm-2 control-label">Client</label>
        <div class="col-sm-8">
            <select class="form-control" id="clientId" name="clientId" required>
                <option value="">~~SELECT CLIENT~~</option>
                <?php
                $clientSql = "SELECT id, company_name, tin_number, phone FROM clients WHERE status = 1 ORDER BY company_name ASC";
                $clientResult = $connect->query($clientSql);
                while($client = $clientResult->fetch_array()) {
                    echo "<option value='".$client['id']."' 
                          data-tin='".$client['tin_number']."' 
                          data-phone='".$client['phone']."'>"
                          .htmlspecialchars($client['company_name'])."</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-sm-2">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addClientModal">
                <i class="glyphicon glyphicon-plus"></i> Quick Add Client
            </button>
        </div>
    </div>
    
    <div class="form-group">
        <label for="clientContact" class="col-sm-2 control-label">Client Contact</label>
        <div class="col-sm-10">
            <input type="text" class="form-control" id="clientContact" name="clientContact" placeholder="Contact Number" readonly />
        </div>
    </div>
    
    <div class="form-group">
        <label for="clientTin" class="col-sm-2 control-label">Client TIN</label>
        <div class="col-sm-10">
            <input type="text" class="form-control" id="clientTin" name="clientTin" placeholder="Client TIN Number" readonly />
        </div>
    </div>

    <table class="table table-bordered table-hover" id="productTable">
        <thead>
            <tr>
                <th style="width:30%;">Product</th>
                <th style="width:15%;">Price</th>
                <th style="width:15%;">Available Qty</th>
                <th style="width:10%;">Quantity</th>
                <th style="width:15%;">Total</th>
                <th style="width:15%;">Action</th>
            </tr>
        </thead>
        <tbody>
            <tr id="row1">
                <td>
                    <select class="form-control select2" name="productName[]" id="productName1" onchange="getProductData(1)" required>
                        <option value="">~~SELECT~~</option>
                        <?php
                        $productSql = "SELECT product_id, name, current_stock, selling_price 
                                     FROM products 
                                     WHERE status = 'active' 
                                     AND current_stock > 0 
                                     ORDER BY name ASC";
                        $productData = $connect->query($productSql);
                        while($row = $productData->fetch_array()) {
                            echo "<option value='".$row['product_id']."' 
                                  data-price='".$row['selling_price']."' 
                                  data-stock='".$row['current_stock']."'>"
                                  .htmlspecialchars($row['name'])."</option>";
                        }
                        ?>
                    </select>
                </td>
                <td>
                    <input type="text" name="rate[]" id="rate1" class="form-control" readonly />
                    <input type="hidden" name="rateValue[]" id="rateValue1" />
                </td>
                <td>
                    <span id="available_quantity1" class="badge"></span>
                </td>
                <td>
                    <input type="number" name="quantity[]" id="quantity1" onkeyup="getTotal(1)" class="form-control" min="1" required />
                </td>
                <td>
                    <input type="text" name="total[]" id="total1" class="form-control" readonly />
                    <input type="hidden" name="totalValue[]" id="totalValue1" />
                </td>
                <td>
                    <button type="button" class="btn btn-danger removeProductRowBtn" onclick="removeProductRow(1)">
                        <i class="glyphicon glyphicon-trash"></i>
                    </button>
                </td>
            </tr>
        </tbody>
    </table>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="subTotal" class="col-sm-3 control-label">Sub Amount</label>
                <div class="col-sm-9">
                    <input type="text" class="form-control" id="subTotal" name="subTotal" readonly />
                    <input type="hidden" class="form-control" id="subTotalValue" name="subTotalValue" />
                </div>
            </div>

            <div class="form-group">
                <label for="vat" class="col-sm-3 control-label">VAT 15%</label>
                <div class="col-sm-9">
                    <input type="text" class="form-control" id="vat" name="vat" readonly />
                    <input type="hidden" class="form-control" id="vatValue" name="vatValue" />
                </div>
            </div>

            <div class="form-group">
                <label for="withholding" class="col-sm-3 control-label">Withholding Tax</label>
                <div class="col-sm-9">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" id="withholding_enabled" name="withholding_enabled"> Enable 2% Withholding Tax
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-group withholding-amount" style="display:none;">
                <label for="withholding_amount" class="col-sm-3 control-label">Withholding Amount</label>
                <div class="col-sm-9">
                    <input type="text" class="form-control" id="withholding_amount" name="withholding_amount" readonly />
                    <input type="hidden" class="form-control" id="withholdingValue" name="withholdingValue" />
                </div>
            </div>

            <div class="form-group">
                <label for="grandTotal" class="col-sm-3 control-label">Grand Total</label>
                <div class="col-sm-9">
                    <input type="text" class="form-control" id="grandTotal" name="grandTotal" readonly />
                    <input type="hidden" class="form-control" id="grandTotalValue" name="grandTotalValue" />
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="paymentType" class="col-sm-3 control-label">Payment Type</label>
                <div class="col-sm-9">
                    <select class="form-control" name="paymentType" id="paymentType" required>
                        <option value="">~~SELECT~~</option>
                        <option value="Cheque">Cheque</option>
                        <option value="Cash">Cash</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="paymentStatus" class="col-sm-3 control-label">Payment Status</label>
                <div class="col-sm-9">
                    <select class="form-control" name="paymentStatus" id="paymentStatus" required>
                        <option value="">~~SELECT~~</option>
                        <option value="1">Full Payment</option>
                        <option value="2">Advance Payment</option>
                        <option value="3">No Payment</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="note" class="col-sm-3 control-label">Note</label>
                <div class="col-sm-9">
                    <textarea class="form-control" id="note" name="note" rows="3"></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="form-group submitButtonFooter">
        <div class="col-sm-offset-2 col-sm-10">
            <button type="button" class="btn btn-default" onclick="addRow()" id="addRowBtn">
                <i class="glyphicon glyphicon-plus-sign"></i> Add Row
            </button>
            <button type="submit" id="createOrderBtn" data-loading-text="Loading..." class="btn btn-success">
                <i class="glyphicon glyphicon-ok-sign"></i> Save Changes
            </button>
            <button type="reset" class="btn btn-default" onclick="resetOrderForm()">
                <i class="glyphicon glyphicon-erase"></i> Reset
            </button>
        </div>
    </div>
</form>

<!-- Quick Add Client Modal -->
<div class="modal fade" id="addClientModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Quick Add Client</h4>
            </div>
            <div class="modal-body">
                <form id="quickAddClientForm">
                    <div class="form-group">
                        <label for="companyName">Company Name</label>
                        <input type="text" class="form-control" id="companyName" name="companyName" required>
                    </div>
                    <div class="form-group">
                        <label for="tinNumber">TIN Number</label>
                        <input type="text" class="form-control" id="tinNumber" name="tinNumber">
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone">
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="saveQuickClient()">Save Client</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize datepicker with today's date
    $(".datepicker").datepicker({
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

    // Initialize select2 for better dropdown experience
    $('.select2').select2();

    // Client selection change event
    $('#clientId').change(function() {
        var selectedOption = $(this).find('option:selected');
        $('#clientTin').val(selectedOption.data('tin'));
        $('#clientContact').val(selectedOption.data('phone'));
    });

    // Form submission
    $("#createOrderForm").on('submit', function(e) {
        e.preventDefault();
        
        var $submitBtn = $("#createOrderBtn");
        $submitBtn.prop('disabled', true);
        $submitBtn.html('<i class="glyphicon glyphicon-refresh glyphicon-refresh-animate"></i> Loading...');
        
        $.ajax({
            url: 'php_action/createOrder.php',
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
                    text: 'An error occurred while creating the order: ' + error
                });
                $submitBtn.prop('disabled', false);
                $submitBtn.html('<i class="glyphicon glyphicon-ok-sign"></i> Save Changes');
            }
        });
    });
});

function saveQuickClient() {
    var formData = $('#quickAddClientForm').serialize();
    
    $.ajax({
        url: 'php_action/createClient.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                // Add new client to dropdown
                var newOption = new Option(response.client.company_name, response.client.id, true, true);
                $(newOption).data('tin', response.client.tin_number);
                $(newOption).data('phone', response.client.phone);
                $('#clientId').append(newOption).trigger('change');
                
                // Close modal
                $('#addClientModal').modal('hide');
                
                // Show success message
                $('.success-messages').html('<div class="alert alert-success">'+
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                    '<strong><i class="glyphicon glyphicon-ok-sign"></i></strong> Client successfully added</div>');
            } else {
                alert('Error adding client: ' + response.messages);
            }
        },
        error: function(xhr, status, error) {
            alert('Error: ' + error);
        }
    });
}

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
        
        totalSubAmount += Number($("#total" + count).val() || 0);
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
    if(row) {
        var total = Number($("#rate" + row).val()) * Number($("#quantity" + row).val());
        total = total.toFixed(2);
        $("#total" + row).val(total);
        $("#totalValue" + row).val(total);
        
        calculateSubTotal();
    }
}

function removeProductRow(row) {
    $("#row" + row).remove();
    calculateSubTotal();
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
                   AND current_stock > 0 
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

function resetOrderForm() {
    $("#createOrderForm")[0].reset();
    $("#productTable tbody tr:not(:first)").remove();
    $(".withholding-amount").hide();
    calculateSubTotal();
}
</script>

<!-- Add SweetAlert2 CSS and JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.all.min.js"></script>

<!-- Add custom styles for SweetAlert -->
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