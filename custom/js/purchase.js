var manageProductTable;
var rowCount = 1;

$(document).ready(function() {
    // Initialize select2
    $('.product-select').select2({
        placeholder: 'Select a product',
        allowClear: true
    });
    
    $('#supplier').select2({
        placeholder: 'Select a supplier',
        allowClear: true
    });

    // Initialize DataTable
    manageProductTable = $('#managePurchaseTable').DataTable({
        'ajax': {
            'url': 'php_action/fetchPurchases.php',
            'type': 'GET',
            'error': function(xhr, error, thrown) {
                console.error('DataTables error:', error);
                console.error('Server response:', xhr.responseText);
                
                // Show error message in table
                $('#managePurchaseTable tbody').html(
                    '<tr><td colspan="9" class="text-center text-danger">' +
                    '<i class="glyphicon glyphicon-warning-sign"></i> ' +
                    'Error loading data. Please check the console for details or try refreshing the page.' +
                    '</td></tr>'
                );
                
                // Show error message above table
                $('.remove-messages').html(
                    '<div class="alert alert-danger">' +
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                    '<strong><i class="glyphicon glyphicon-warning-sign"></i></strong> ' +
                    'Failed to load purchase data. Error: ' + error +
                    '</div>'
                );
            }
        },
        'order': [[0, 'desc']],
        'columns': [
            { data: 'purchase_number' },
            { data: 'purchase_date' },
            { data: 'supplier_name' },
            { 
                data: 'sub_total',
                render: function(data) {
                    return data ? Number(data).toFixed(2) : '0.00';
                },
                className: 'text-right'
            },
            { 
                data: 'vat',
                render: function(data) {
                    return data ? Number(data).toFixed(2) : '0.00';
                },
                className: 'text-right'
            },
            { 
                data: 'withholding_tax_amount',
                render: function(data, type, row) {
                    return row.withholding_tax_enabled == 1 ? Number(data).toFixed(2) : '0.00';
                },
                className: 'text-right'
            },
            { 
                data: 'grand_total',
                render: function(data) {
                    return data ? Number(data).toFixed(2) : '0.00';
                },
                className: 'text-right'
            },
            { 
                data: 'payment_status',
                render: function(data) {
                    var badge = '';
                    switch(data.toLowerCase()) {
                        case 'paid':
                            badge = '<span class="label label-success">Paid</span>';
                            break;
                        case 'partial':
                            badge = '<span class="label label-warning">Partial</span>';
                            break;
                        case 'unpaid':
                            badge = '<span class="label label-danger">Unpaid</span>';
                            break;
                        default:
                            badge = '<span class="label label-default">' + data + '</span>';
                    }
                    return badge;
                }
            },
            { 
                data: 'id',
                orderable: false,
                className: 'text-center',
                render: function(data, type, row) {
                    var buttons = '<div class="btn-group btn-group-sm">';
                    
                    // Edit button
                    buttons += '<button type="button" class="btn btn-default" onclick="editPurchase(' + data + ')" title="Edit Purchase">' +
                              '<i class="glyphicon glyphicon-edit"></i></button>';
                    
                    // Print button
                    buttons += '<button type="button" class="btn btn-info" onclick="printPurchase(' + data + ')" title="Print Purchase">' +
                              '<i class="glyphicon glyphicon-print"></i></button>';
                    
                    // Payment History button
                    buttons += '<button type="button" class="btn btn-primary" onclick="viewPaymentHistory(' + data + ')" title="Payment History">' +
                              '<i class="glyphicon glyphicon-list"></i></button>';
                    
                    // Delete button
                    buttons += '<button type="button" class="btn btn-danger" onclick="removePurchase(' + data + ')" title="Delete Purchase">' +
                              '<i class="glyphicon glyphicon-trash"></i></button>';
                    
                    // Payment status dropdown (only show if not Paid)
                    if(row.payment_status.toLowerCase() !== 'paid') {
                        buttons += '<div class="btn-group btn-group-sm">' +
                                 '<button type="button" class="btn btn-warning dropdown-toggle" data-toggle="dropdown" title="Update Payment">' +
                                 '<i class="glyphicon glyphicon-usd"></i> <span class="caret"></span></button>' +
                                 '<ul class="dropdown-menu dropdown-menu-right">' +
                                 '<li><a href="javascript:void(0)" onclick="updatePaymentStatus(' + data + ', \'Paid\')"><i class="glyphicon glyphicon-ok"></i> Mark as Paid</a></li>' +
                                 '<li><a href="javascript:void(0)" onclick="updatePaymentStatus(' + data + ', \'Partial\')"><i class="glyphicon glyphicon-time"></i> Add Partial Payment</a></li>' +
                                 '<li><a href="javascript:void(0)" onclick="updatePaymentStatus(' + data + ', \'Unpaid\')"><i class="glyphicon glyphicon-remove"></i> Mark as Unpaid</a></li>' +
                                 '</ul></div>';
                    }
                    
                    buttons += '</div>';
                    return buttons;
                }
            }
        ],
        'pageLength': 10,
        'responsive': true,
        'processing': true,
        'dom': 'Bfrtip',
        'buttons': [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    });

    // Add withholding tax checkbox handler
    $("#withholding_enabled").change(function() {
        if($(this).is(":checked")) {
            $(".withholding-amount").show();
        } else {
            $(".withholding-amount").hide();
        }
        calculateTotal();
    });

    // Handle edit purchase form submission
    $(document).on('submit', '#editPurchaseForm', function(e) {
        e.preventDefault();
        var form = $(this);

        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success === true) {
                    // Close the modal
                    $("#editPurchaseModal").modal('hide');
                    
                    // Reload the purchases table
                    manageProductTable.ajax.reload(null, false);
                    
                    // Show notification popup
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.messages,
                        showConfirmButton: false,
                        timer: 2000
                    });
                } else {
                    // Show error in the modal
                    $("#edit-purchase-messages").html('<div class="alert alert-danger">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> ' + response.messages +
                        '</div>');
                }
            },
            error: function(xhr, status, error) {
                // Show error in the modal
                $("#edit-purchase-messages").html('<div class="alert alert-danger">' +
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                    '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> An error occurred while updating the purchase' +
                    '</div>');
            }
        });
    });

    // Submit purchase form
    $("#submitPurchaseForm").unbind('submit').bind('submit', function() {
        var form = $(this);

        // Client-side validation
        if(!$("#supplier").val()) {
            $("#add-purchase-messages").html('<div class="alert alert-warning">' +
                '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> Please select a supplier' +
                '</div>');
            return false;
        }

        if(!$("#purchaseDate").val()) {
            $("#add-purchase-messages").html('<div class="alert alert-warning">' +
                '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> Please select a purchase date' +
                '</div>');
            return false;
        }

        // Check if at least one product is added
        var productCount = 0;
        $('.product-select').each(function() {
            if($(this).val()) productCount++;
        });

        if(productCount === 0) {
            $("#add-purchase-messages").html('<div class="alert alert-warning">' +
                '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> Please add at least one product' +
                '</div>');
            return false;
        }

        // Check if all product details are filled
        var isValid = true;
        $('#productTable tbody tr').each(function() {
            var product = $(this).find('.product-select').val();
            var quantity = $(this).find('.quantity').val();
            var rate = $(this).find('.rate').val();

            if((product && (!quantity || !rate)) || (quantity && (!product || !rate)) || (rate && (!product || !quantity))) {
                $("#add-purchase-messages").html('<div class="alert alert-warning">' +
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                    '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> Please fill in all product details' +
                    '</div>');
                isValid = false;
                return false;
            }
        });

        if(!isValid) return false;

        // Show loading state
        $("#createPurchaseBtn").button('loading');

        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: form.serialize(),
            dataType: 'json',
            success:function(response) {
                if(response.success === true) {
                    // Show success message
                    var successAlert = $(`
                        <div id="success-alert" class="alert alert-success" style="display: none;">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <strong><i class="glyphicon glyphicon-ok-sign"></i></strong> 
                            ${response.messages}
                        </div>
                    `);

                    // Remove any existing alerts
                    $("#success-alert").remove();
                    
                    // Add to body and show with animation
                    $("body").append(successAlert);
                    successAlert.fadeIn('slow');
                    
                    // Auto hide after 3 seconds
                    setTimeout(function() {
                        successAlert.fadeOut('slow', function() {
                            $(this).remove();
                        });
                    }, 3000);

                    // Reset form
                    $("#submitPurchaseForm")[0].reset();
                    
                    // Reset product rows
                    $("#productTable tbody tr:not(:first)").remove();
                    $("#productTable tbody tr:first").find('select,input').val('');
                    $('.product-select').select2();
                    
                    // Reset totals
                    $("#subTotal").val('');
                    $("#subTotalValue").val('');
                    $("#vat").val('');
                    $("#vatValue").val('');
                    $("#grandTotal").val('');
                    $("#grandTotalValue").val('');
                    
                    // Reload the manage table
                    manageProductTable.ajax.reload(null, false);

                    // Close modal
                    $("#addPurchaseModal").modal('hide');

                } else {
                    $("#add-purchase-messages").html('<div class="alert alert-warning">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> ' + response.messages +
                        '</div>');
                }
            },
            error: function(xhr, status, error) {
                $("#add-purchase-messages").html('<div class="alert alert-danger">' +
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                    '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> An error occurred while saving the purchase' +
                    '</div>');
            },
            complete: function() {
                // Reset button state
                $("#createPurchaseBtn").button('reset');
            }
        });

        return false;
    });

    // Submit supplier form
    $("#submitSupplierForm").unbind('submit').bind('submit', function() {
        var form = $(this);

        // Client-side validation
        if(!$("#companyName").val()) {
            $("#add-supplier-messages").html('<div class="alert alert-warning">' +
                '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> Please enter company name' +
                '</div>');
            return false;
        }

        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: form.serialize(),
            dataType: 'json',
            success:function(response) {
                if(response.success === true) {
                    // Reset form
                    $("#submitSupplierForm")[0].reset();
                    
                    // Add new supplier to select
                    var newOption = new Option(response.supplier.company_name, response.supplier.id, true, true);
                    $('#supplier').append(newOption).trigger('change');

                    // Show success message
                    $("#add-supplier-messages").html('<div class="alert alert-success">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        '<strong><i class="glyphicon glyphicon-ok-sign"></i></strong> ' + response.messages +
                        '</div>');

                    // Close modal after 1.5 seconds
                    setTimeout(function() {
                        $("#addSupplierModal").modal('hide');
                    }, 1500);

                } else {
                    $("#add-supplier-messages").html('<div class="alert alert-warning">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> ' + response.messages +
                        '</div>');
                }
            }
        });

        return false;
    });

    // Handle withholding tax checkbox
    $('#withholdingTaxEnabled').change(function() {
        calculateTotals();
    });

    // Add new item row
    $('#addItemBtn').click(function() {
        addItemRow();
    });

    // Remove item row
    $(document).on('click', '.removeItem', function() {
        if ($('#purchaseItems tbody tr').length > 1) {
            $(this).closest('tr').remove();
        } else {
            // If it's the last row, just clear the inputs
            $(this).closest('tr').find('input').val('');
            $(this).closest('tr').find('select').val('').trigger('change');
        }
        calculateTotals();
    });

    // Calculate line total when quantity or rate changes
    $(document).on('change keyup', '.quantity, .rate', function() {
        calculateLineTotal($(this).closest('tr'));
        calculateTotals();
    });

    // Handle product selection change
    $(document).on('change', '.product-select', function() {
        var row = $(this).closest('tr');
        calculateLineTotal(row);
        calculateTotals();
    });

    // Handle form submission
    $('#purchaseForm').on('submit', function(e) {
        e.preventDefault();
        createPurchase();
    });
});

function addRow() {
    rowCount++;
    var html = '<tr id="row' + rowCount + '">' +
        '<td>' +
        '<select class="form-control product" name="product[]" id="product' + rowCount + '" required>' +
        $('#product1').html() +
        '</select>' +
        '</td>' +
        '<td><input type="number" class="form-control quantity" name="quantity[]" id="quantity' + rowCount + '" onkeyup="getTotal(' + rowCount + ')" required></td>' +
        '<td><input type="number" step="0.01" class="form-control rate" name="rate[]" id="rate' + rowCount + '" onkeyup="getTotal(' + rowCount + ')" required></td>' +
        '<td>' +
        '<input type="text" class="form-control total" name="total[]" id="total' + rowCount + '" disabled>' +
        '<input type="hidden" class="form-control total" name="totalValue[]" id="totalValue' + rowCount + '">' +
        '</td>' +
        '<td><button type="button" class="btn btn-default removeProductRowBtn" onclick="removeProductRow(' + rowCount + ')"><i class="glyphicon glyphicon-trash"></i></button></td>' +
        '</tr>';

    $('#productTable tbody').append(html);
    $('#product' + rowCount).select2();
}

function removeProductRow(row) {
    $('#row' + row).remove();
    subAmount();
}

function getTotal(row = null) {
    if(row) {
        var total = Number($("#rate" + row).val()) * Number($("#quantity" + row).val());
        total = total.toFixed(2);
        $("#total" + row).val(total);
        $("#totalValue" + row).val(total);
    }
    calculateTotal();
}

function subAmount() {
    var tableProductLength = $("#productTable tbody tr").length;
    var totalSubAmount = 0;
    
    for(x = 0; x < tableProductLength; x++) {
        var tr = $("#productTable tbody tr")[x];
        var count = $(tr).attr('id');
        var total = $("#totalValue" + count.replace(/[^\d.]/g, '')).val();
        
        totalSubAmount += Number(total);
    }

    totalSubAmount = totalSubAmount.toFixed(2);
    $("#subTotal").val(totalSubAmount);
    $("#subTotalValue").val(totalSubAmount);
    
    var vat = (totalSubAmount * 0.15).toFixed(2);
    $("#vat").val(vat);
    $("#vatValue").val(vat);
    
    var grandTotal = (Number(totalSubAmount) + Number(vat)).toFixed(2);
    $("#grandTotal").val(grandTotal);
    $("#grandTotalValue").val(grandTotal);
}

function editPurchase(purchaseId = null) {
    if (purchaseId) {
        console.log('Editing purchase:', purchaseId);
        
        // Reset form and error messages
        $('#edit-purchase-messages').empty();
        
        $.ajax({
            url: 'php_action/fetchSelectedPurchase.php',
            type: 'post',
            data: {purchaseId: purchaseId},
            dataType: 'json',
            success: function(response) {
                console.log('Edit purchase response:', response);
                
                if (response.success) {
                    // Populate modal with form
                    $('#editPurchaseModal .modal-body').html(response.html);
                    
                    // Initialize select2 for dropdowns
                    $('.product-select').select2({
                        width: '100%',
                        placeholder: 'Select a product'
                    });
                    
                    // Initialize datepicker
                    $('#purchaseDate').datepicker({
                        format: 'yyyy-mm-dd',
                        autoclose: true,
                        todayHighlight: true
                    });
                    
                    // Bind event handlers for calculations
                    $('#editPurchaseForm').on('change', '.quantity, .rate', function() {
                        var row = $(this).closest('tr');
                        calculateLineTotal(row);
                        calculateTotals();
                    });
                    
                    // Bind withholding tax checkbox
                    $('#withholdingTaxEnabled').on('change', function() {
                        calculateTotals();
                    });
                    
                    // Bind save changes button
                    $('#saveChangesBtn').off('click').on('click', function() {
                        submitEditPurchase();
                    });
                    
                    // Show modal
                    $('#editPurchaseModal').modal('show');
                    
                    // Calculate initial totals
                    calculateTotals();
                } else {
                    $('#edit-purchase-messages').html(
                        '<div class="alert alert-danger">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> '+ 
                        response.messages +
                        '</div>'
                    );
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                $('#edit-purchase-messages').html(
                    '<div class="alert alert-danger">'+
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                    '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> '+
                    'Error fetching purchase data: ' + error +
                    '</div>'
                );
            }
        });
    }
}

function submitEditPurchase() {
    var form = $('#editPurchaseForm');
    var formData = new FormData(form[0]);
    
    // Validation
    if (!validatePurchaseForm()) {
        return false;
    }
    
    // Show loading message
    $('#edit-purchase-messages').html(
        '<div class="alert alert-info">'+
        '<i class="glyphicon glyphicon-refresh"></i> Updating purchase...'+
        '</div>'
    );
    
    $.ajax({
        url: 'php_action/editPurchase.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        cache: false,
        contentType: false,
        processData: false,
        success: function(response) {
            console.log('Edit purchase response:', response);
            
            if (response.success) {
                $('#editPurchaseModal').modal('hide');
                
                // Reload the purchases table
                manageProductTable.ajax.reload(null, false);
                
                $('.remove-messages').html(
                    '<div class="alert alert-success">'+
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                    '<strong><i class="glyphicon glyphicon-ok-sign"></i></strong> '+ 
                    response.messages +
                    '</div>'
                );
            } else {
                $('#edit-purchase-messages').html(
                    '<div class="alert alert-danger">'+
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                    '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> '+ 
                    response.messages +
                    '</div>'
                );
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            $('#edit-purchase-messages').html(
                '<div class="alert alert-danger">'+
                '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> '+
                'Error updating purchase: ' + error +
                '</div>'
            );
        }
    });
}

function validatePurchaseForm() {
    var isValid = true;
    var messages = [];
    
    // Check supplier
    if (!$('#supplier').val()) {
        messages.push('Please select a supplier');
        isValid = false;
    }
    
    // Check purchase date
    if (!$('#purchaseDate').val()) {
        messages.push('Please select a purchase date');
        isValid = false;
    }
    
    // Check if at least one product is selected
    var hasProducts = false;
    $('.product-select').each(function() {
        if ($(this).val()) {
            hasProducts = true;
            return false;
        }
    });
    
    if (!hasProducts) {
        messages.push('Please select at least one product');
        isValid = false;
    }
    
    // Check quantities and rates
    $('.quantity, .rate').each(function() {
        var value = parseFloat($(this).val());
        if ($(this).closest('tr').find('.product-select').val() && (isNaN(value) || value <= 0)) {
            messages.push('Please enter valid quantity and rate for all products');
            isValid = false;
            return false;
        }
    });
    
    if (!isValid) {
        $('#edit-purchase-messages').html(
            '<div class="alert alert-danger">'+
            '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
            '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> '+
            messages.join('<br>') +
            '</div>'
        );
    }
    
    return isValid;
}

function removePurchase(purchaseId) {
    if (confirm('Are you sure you want to delete this purchase?')) {
        $.ajax({
            url: 'php_action/removePurchase.php',
            type: 'post',
            data: {purchaseId: purchaseId},
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Refresh the table
                    manageProductTable.ajax.reload(null, false);
                    // Show success message
                    $('.remove-messages').html('<div class="alert alert-success">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        '<strong><i class="glyphicon glyphicon-ok-sign"></i></strong> ' + response.messages +
                        '</div>');

                    $('.alert-success').delay(500).show(10, function() {
                        $(this).delay(3000).hide(10, function() {
                            $(this).remove();
                        });
                    });
                } else {
                    // Show error message
                    $('.remove-messages').html('<div class="alert alert-danger">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        '<strong><i class="glyphicon glyphicon-remove-sign"></i></strong> ' + response.messages +
                        '</div>');
                }
            },
            error: function() {
                $('.remove-messages').html('<div class="alert alert-danger">' +
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                    '<strong><i class="glyphicon glyphicon-remove-sign"></i></strong> Error occurred while deleting the purchase.' +
                    '</div>');
            }
        });
    }
}

function addItemRow() {
    var firstRow = $('#purchaseItems tbody tr:first').clone();
    firstRow.find('input').val('');
    firstRow.find('select').val('').trigger('change');
    $('#purchaseItems tbody').append(firstRow);
    
    // Reinitialize select2 for the new row
    firstRow.find('.product-select').select2({
        width: '100%',
        placeholder: 'Select a product'
    });
}

function calculateLineTotal(row) {
    var quantity = parseFloat(row.find('.quantity').val()) || 0;
    var rate = parseFloat(row.find('.rate').val()) || 0;
    var amount = quantity * rate;
    row.find('.amount').val(amount.toFixed(2));
}

function calculateTotals() {
    var subTotal = 0;
    
    // Calculate subtotal
    $('#purchaseItems tbody tr').each(function() {
        subTotal += parseFloat($(this).find('.amount').val()) || 0;
    });
    
    // Calculate VAT (15%)
    var vat = subTotal * 0.15;
    
    // Calculate withholding tax if enabled (2%)
    var withholdingAmount = 0;
    if($('#withholdingTaxEnabled').is(':checked')) {
        withholdingAmount = subTotal * 0.02;
    }
    
    // Calculate grand total
    var grandTotal = subTotal + vat + withholdingAmount;
    
    // Update form fields
    $('#subTotal').val(subTotal.toFixed(2));
    $('#vat').val(vat.toFixed(2));
    $('#withholdingAmount').val(withholdingAmount.toFixed(2));
    $('#grandTotal').val(grandTotal.toFixed(2));
}

function createPurchase() {
    // Show loading state
    $('#saveBtn').prop('disabled', true).html('<i class="glyphicon glyphicon-refresh"></i> Saving...');
    
    // Validate required fields
    var isValid = true;
    var errorMessage = '';
    
    if (!$('#supplier').val()) {
        isValid = false;
        errorMessage += 'Please select a supplier<br>';
    }
    
    if (!$('#purchaseDate').val()) {
        isValid = false;
        errorMessage += 'Please select a purchase date<br>';
    }

    // Check if at least one product is added
    var hasProducts = false;
    $('.product-select').each(function() {
        if ($(this).val()) {
            hasProducts = true;
            return false; // break the loop
        }
    });
    
    if (!hasProducts) {
        isValid = false;
        errorMessage += 'Please add at least one product<br>';
    }
    
    // Validate product rows
    $('#purchaseItems tbody tr').each(function() {
        var $row = $(this);
        var productId = $row.find('.product-select').val();
        var quantity = $row.find('.quantity').val();
        var rate = $row.find('.rate').val();
        
        if (productId && (!quantity || !rate)) {
            isValid = false;
            errorMessage += 'Please fill in all product details (quantity and rate)<br>';
            return false; // break the loop
        }
    });
    
    if (!isValid) {
        $('#messages').html(`
            <div class="alert alert-danger">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong>
                ${errorMessage}
            </div>
        `);
        $('#saveBtn').prop('disabled', false).html('<i class="glyphicon glyphicon-save"></i> Save Purchase');
        return;
    }
    
    // Get form data
    var formData = $('#purchaseForm').serialize();
    
    $.ajax({
        url: 'php_action/createPurchase.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                // Show success message
                $('#messages').html(`
                    <div class="alert alert-success">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <strong><i class="glyphicon glyphicon-ok-sign"></i></strong> ${response.messages}
                    </div>
                `);
                
                // Reset form
                $('#purchaseForm')[0].reset();
                $('#purchaseItems tbody tr:not(:first)').remove();
                $('#purchaseItems tbody tr:first').find('select,input').val('');
                $('.product-select').select2('destroy').select2({
                    placeholder: 'Select a product',
                    allowClear: true
                });
                
                // Reset totals
                calculateTotals();
                
                // Reload the purchases table if it exists
                if (typeof manageProductTable !== 'undefined') {
                    manageProductTable.ajax.reload(null, false);
                }
                
                // Offer to print
                if(response.purchase_id) {
                    if(confirm('Purchase created successfully. Do you want to print the purchase order?')) {
                        window.open(`printPurchase.php?id=${response.purchase_id}`, '_blank');
                    }
                }
            } else {
                // Show error message
                $('#messages').html(`
                    <div class="alert alert-danger">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> ${response.messages}
                    </div>
                `);
            }
        },
        error: function(xhr, status, error) {
            // Show error message
            $('#messages').html(`
                <div class="alert alert-danger">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> Error creating purchase. Please try again.
                </div>
            `);
            console.error('Error:', error);
            console.error('Response:', xhr.responseText);
        },
        complete: function() {
            // Reset button state
            $('#saveBtn').prop('disabled', false).html('<i class="glyphicon glyphicon-save"></i> Save Purchase');
        }
    });
}

// Add the print preview function
function printPurchase(purchaseId) {
    if(purchaseId) {
        window.open('printPurchase.php?id=' + purchaseId, '_blank');
    }
}

// Update the payment status update function
function updatePaymentStatus(purchaseId, status) {
    if(!purchaseId || !status) {
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'Invalid parameters for payment status update'
        });
        return;
    }

    if(status === 'Partial') {
        // Show partial payment modal
        $('#partialPaymentModal').modal('show');
        $('#purchaseId').val(purchaseId);
        $('#paymentDate').val(new Date().toISOString().split('T')[0]);
        return;
    }

    // For other statuses, show confirmation dialog
    Swal.fire({
        title: 'Update Payment Status',
        text: `Are you sure you want to mark this purchase as ${status}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, update it!'
    }).then((result) => {
        if (result.isConfirmed) {
            processPaymentUpdate(purchaseId, status);
        }
    });
}

function processPaymentUpdate(purchaseId, status, amount = null, paymentData = null) {
    // Show loading state
    Swal.fire({
        title: 'Updating...',
        text: 'Please wait while we update the payment status',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    let data = {
        purchaseId: purchaseId,
        status: status
    };

    if (paymentData) {
        data = {...data, ...paymentData};
    }

    $.ajax({
        url: 'php_action/updatePaymentStatus.php',
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                // Refresh the table
                manageProductTable.ajax.reload(null, false);
                
                // Close modal if open
                $('#partialPaymentModal').modal('hide');
                
                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.messages,
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: response.messages
                });
            }
        },
        error: function(xhr, status, error) {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An error occurred while updating payment status: ' + error
            });
        }
    });
}

function viewPaymentHistory(purchaseId) {
    $.ajax({
        url: 'php_action/getPaymentHistory.php',
        type: 'POST',
        data: { purchaseId: purchaseId },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                let payments = response.payments;
                let tableHtml = `
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Reference</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>`;
                
                payments.forEach(payment => {
                    tableHtml += `
                        <tr>
                            <td>${payment.payment_date}</td>
                            <td>${parseFloat(payment.amount).toFixed(2)}</td>
                            <td>${payment.payment_method}</td>
                            <td>${payment.reference_number || ''}</td>
                            <td>${payment.notes || ''}</td>
                        </tr>`;
                });
                
                tableHtml += `</tbody></table>`;
                
                $('#paymentHistoryBody').html(tableHtml);
                $('#paymentHistoryModal').modal('show');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: response.messages
                });
            }
        },
        error: function(xhr, status, error) {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Error fetching payment history: ' + error
            });
        }
    });
}

// Partial Payment Modal Submit Handler
$('#partialPaymentForm').on('submit', function(e) {
    e.preventDefault();
    
    let purchaseId = $('#purchaseId').val();
    let paymentData = {
        payment_date: $('#paymentDate').val(),
        amount: $('#paymentAmount').val(),
        payment_method: $('#paymentMethod').val(),
        reference_number: $('#referenceNumber').val(),
        notes: $('#paymentNotes').val()
    };
    
    processPaymentUpdate(purchaseId, 'Partial', null, paymentData);
});

// Add action button for payment history
function renderActionButtons(data, type, row) {
    var buttons = '<div class="btn-group btn-group-sm">';
    
    // Edit button
    buttons += '<button type="button" class="btn btn-default" onclick="editPurchase(' + data + ')" title="Edit Purchase">' +
              '<i class="glyphicon glyphicon-edit"></i></button>';
    
    // Print button
    buttons += '<button type="button" class="btn btn-info" onclick="printPurchase(' + data + ')" title="Print Purchase">' +
              '<i class="glyphicon glyphicon-print"></i></button>';
    
    // Payment History button
    buttons += '<button type="button" class="btn btn-primary" onclick="viewPaymentHistory(' + data + ')" title="Payment History">' +
              '<i class="glyphicon glyphicon-list"></i></button>';
    
    // Delete button
    buttons += '<button type="button" class="btn btn-danger" onclick="removePurchase(' + data + ')" title="Delete Purchase">' +
              '<i class="glyphicon glyphicon-trash"></i></button>';
    
    // Payment status dropdown (only show if not Paid)
    if(row.payment_status.toLowerCase() !== 'paid') {
        buttons += '<div class="btn-group btn-group-sm">' +
                 '<button type="button" class="btn btn-warning dropdown-toggle" data-toggle="dropdown" title="Update Payment">' +
                 '<i class="glyphicon glyphicon-usd"></i> <span class="caret"></span></button>' +
                 '<ul class="dropdown-menu dropdown-menu-right">' +
                 '<li><a href="javascript:void(0)" onclick="updatePaymentStatus(' + data + ', \'Paid\')"><i class="glyphicon glyphicon-ok"></i> Mark as Paid</a></li>' +
                 '<li><a href="javascript:void(0)" onclick="updatePaymentStatus(' + data + ', \'Partial\')"><i class="glyphicon glyphicon-time"></i> Add Partial Payment</a></li>' +
                 '<li><a href="javascript:void(0)" onclick="updatePaymentStatus(' + data + ', \'Unpaid\')"><i class="glyphicon glyphicon-remove"></i> Mark as Unpaid</a></li>' +
                 '</ul></div>';
    }
    
    buttons += '</div>';
    return buttons;
}