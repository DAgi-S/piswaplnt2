var manageProductTable;
var row = 1;

$(document).ready(function() {
    // Initialize select picker
    $('.selectpicker').selectpicker();

    // Load suppliers
    loadSuppliers();
    
    // Load raw materials
    loadRawMaterials();

    // Handle VAT changes
    $("#vat").on('change keyup', function() {
        calculateGrandTotal();
    });

    // Handle quantity and rate changes for all rows
    $(document).on('change keyup', '[id^=quantity], [id^=rate]', function() {
        var rowId = $(this).attr('id').replace('quantity', '').replace('rate', '');
        getTotal(rowId);
    });

    // Initialize hidden fields if they don't exist
    if (!$("#vatAmount").length) {
        $('<input>').attr({
            type: 'hidden',
            id: 'vatAmount',
            name: 'vat_amount'
        }).appendTo('#submitPurchaseForm');
    }

    if (!$("#subTotalValue").length) {
        $('<input>').attr({
            type: 'hidden',
            id: 'subTotalValue',
            name: 'sub_total'
        }).appendTo('#submitPurchaseForm');
    }

    if (!$("#grandTotalValue").length) {
        $('<input>').attr({
            type: 'hidden',
            id: 'grandTotalValue',
            name: 'grand_total'
        }).appendTo('#submitPurchaseForm');
    }

    // Handle warehouse selection
    $('#warehouse').on('change', function() {
        var warehouseId = $(this).val();
        if(warehouseId) {
            loadLocations(warehouseId);
        } else {
            $('[id^=location]').html('<option value="">~~SELECT~~</option>');
            $('.selectpicker').selectpicker('refresh');
        }
    });

    // Handle supplier form submission
    $("#submitSupplierForm").on("submit", function(e) {
        e.preventDefault();
        
        var form = $(this);
        var formData = form.serialize();

        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: formData,
            dataType: 'json',
            success: function(response) {
                if(response.success == true) {
                    // Close modal
                    $("#addSupplierModal").modal('hide');
                    
                    // Reset form
                    form[0].reset();
                    
                    // Reload suppliers
                    loadSuppliers();
                    
                    // Show success message
                    $(".remove-messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                        '<strong><i class="glyphicon glyphicon-ok-sign"></i></strong> '+ response.messages +
                    '</div>');

                    // Scroll to message
                    $('html, body').animate({
                        scrollTop: $(".remove-messages").offset().top - 100
                    }, 1000);
                } else {
                    // Show error message
                    $(".remove-messages").html('<div class="alert alert-danger alert-dismissible" role="alert">'+
                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                        '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> '+ response.messages +
                    '</div>');
                }

                // Auto hide alert after 3 seconds
                setTimeout(function() {
                    $(".alert").fadeOut('slow');
                }, 3000);
            },
            error: function(xhr, status, error) {
                // Show error message
                $(".remove-messages").html('<div class="alert alert-danger alert-dismissible" role="alert">'+
                    '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                    '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> Error occurred while adding supplier'+
                '</div>');
            }
        });
    });

    // Handle purchase form submission
    $("#submitPurchaseForm").unbind('submit').bind('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        
        // Get and validate purchase date
        var purchaseDate = $("#purchase_date").val();
        if(!purchaseDate) {
            toastr.error("Please select a purchase date");
            return false;
        }

        // Validate date format (YYYY-MM-DD)
        if(!/^\d{4}-\d{2}-\d{2}$/.test(purchaseDate)) {
            toastr.error("Invalid date format. Please use the date picker.");
            return false;
        }

        // Validate form
        if(!validateForm()) {
            return false;
        }

        // Calculate totals before submission
        calculateSubTotal();
        calculateGrandTotal();

        // Create FormData object
        var formData = new FormData();
        
        // Add all form fields
        var formArray = form.serializeArray();
        $.each(formArray, function(i, field) {
            formData.append(field.name, field.value);
        });

        // Explicitly add calculated values
        formData.append('sub_total', $("#subTotalValue").val());
        formData.append('vat_amount', $("#vatAmount").val());
        formData.append('vat', $("#vat").val());
        formData.append('grand_total', $("#grandTotalValue").val());
        formData.append('purchase_date', purchaseDate);
        formData.append('withholding_enabled', $("#withholding_enabled").is(':checked') ? 1 : 0);
        formData.append('withholding_amount', $("#withholding_amount").val() || 0);

        // Debug log
        console.log('Form Data:', {
            sub_total: $("#subTotalValue").val(),
            vat_amount: $("#vatAmount").val(),
            vat: $("#vat").val(),
            grand_total: $("#grandTotalValue").val(),
            withholding_enabled: $("#withholding_enabled").is(':checked') ? 1 : 0,
            withholding_amount: $("#withholding_amount").val() || 0
        });

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            dataType: 'json',
            processData: false,
            contentType: false,
            success:function(response) {
                if(response.success == true) {
                    // Reset form
                    form[0].reset();

                    // Close modal
                    $("#addPurchaseModal").modal('hide');

                    // Reload table
                    manageProductTable.ajax.reload(null, false);

                    // Show success message
                    toastr.success(response.messages);
                } else {
                    toastr.error(response.messages);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                console.error('Form Data:', formData);
                toastr.error('Error occurred while saving purchase order');
            }
        });

        return false;
    });

    // Initialize DataTable
    manageProductTable = $("#managePurchaseTable").DataTable({
        'ajax': 'php_action/fetchPurchases.php',
        'order': [[3, 'desc']], // Sort by purchase date descending
        'columns': [
            { // #
                'data': null,
                'orderable': false,
                'searchable': false,
                'render': function (data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            { 'data': 'purchase_number' }, // Purchase #
            { 'data': 'company_name' }, // Supplier
            { // Purchase Date
                'data': 'purchase_date',
                'render': function(data, type, row) {
                    if (type === 'display') {
                        if (data === '0000-00-00' || !data) {
                            return '';
                        }
                        return moment(data).format('YYYY-MM-DD');
                    }
                    return data;
                }
            },
            { // Sub Total
                'data': 'sub_total',
                'render': function(data, type, row) {
                    if (type === 'display') {
                        return parseFloat(data).toFixed(2);
                    }
                    return data;
                }
            },
            { // VAT
                'data': 'vat',
                'render': function(data, type, row) {
                    if (type === 'display') {
                        return parseFloat(data).toFixed(2);
                    }
                    return data;
                }
            },
            { // Grand Total
                'data': 'grand_total',
                'render': function(data, type, row) {
                    if (type === 'display') {
                        return parseFloat(data).toFixed(2);
                    }
                    return data;
                }
            },
            { // Payment Status
                'data': 'payment_status',
                'render': function(data, type, row) {
                    if (type === 'display') {
                    var statusClass = '';
                    var statusText = data || 'Unpaid';
                    
                    switch(statusText.toLowerCase()) {
                        case 'paid':
                            statusClass = 'success';
                            break;
                            case 'partially_paid':
                                statusClass = 'warning';
                                break;
                            default:
                                statusClass = 'danger';
                                statusText = 'Unpaid';
                        }
                        
                        return '<span class="label label-' + statusClass + '">' + statusText + '</span>';
                    }
                    return data;
                }
            },
            { // Action buttons
                'data': null,
                'orderable': false,
                'searchable': false,
                'render': function(data, type, row) {
                    if (type === 'display') {
                        var actions = '<div class="btn-group">' +
                            '<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' +
                            'Action <span class="caret"></span>' +
                            '</button>' +
                            '<ul class="dropdown-menu dropdown-menu-right">';
                        
                        // View action - always available if user can access the page
                        actions += '<li><a href="javascript:void(0);" onclick="viewPurchase(\'' + row.purchase_number + '\')"><i class="fa fa-eye"></i> View</a></li>';
                        
                        // Edit action
                        if (permissions.canEdit) {
                            actions += '<li><a href="javascript:void(0);" onclick="editPurchase(\'' + row.purchase_number + '\')"><i class="fa fa-edit"></i> Edit</a></li>';
                        }
                        
                        // Payment actions
                        if (permissions.canAddPayment) {
                            actions += '<li><a href="javascript:void(0);" onclick="updatePaymentStatus(\'' + row.purchase_number + '\')"><i class="fa fa-money"></i> Update Payment</a></li>';
                        }
                        
                        if (permissions.canViewPayment) {
                            actions += '<li><a href="javascript:void(0);" onclick="viewPaymentHistory(\'' + row.purchase_number + '\')"><i class="fa fa-history"></i> Payment History</a></li>';
                        }
                        
                        // Delete action
                        if (permissions.canDelete) {
                            actions += '<li><a href="javascript:void(0);" onclick="removePurchase(\'' + row.purchase_number + '\')"><i class="fa fa-trash"></i> Remove</a></li>';
                        }
                        
                        actions += '</ul></div>';
                        return actions;
                    }
                    return null;
                }
            }
        ],
        'responsive': true,
        'pageLength': 10,
        'lengthMenu': [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]]
    });

    // Handle raw material selection
    $(document).on('change', '[id^=productName]', function() {
        var row = $(this).attr('id').replace('productName', '');
        var selectedOption = $(this).find('option:selected');
        
        // Get the unit and cost from data attributes
        var unit = selectedOption.data('unit');
        var cost = selectedOption.data('cost');
        
        // Update the rate field with the cost
        $('#rate' + row).val(cost);
        
        // Update quantity field placeholder with the unit
        $('#quantity' + row).attr('placeholder', 'Quantity in ' + unit);
        
        // Recalculate total for this row
        getTotal(row);
    });
});

// Load suppliers into dropdown
function loadSuppliers() {
    $.ajax({
        url: 'php_action/fetchSuppliers.php',
        type: 'post',
        dataType: 'json',
        success: function(response) {
            if(response.success == true) {
                var html = '<option value="">~~SELECT~~</option>';
                response.data.forEach(function(supplier) {
                    html += '<option value="'+supplier.id+'">'+supplier.company_name+'</option>';
                });
                $("#supplier").html(html);
                $("#supplier").selectpicker('refresh');
            } else {
                $("#supplier").html('<option value="">Error loading suppliers</option>');
                $("#supplier").selectpicker('refresh');
            }
        },
        error: function() {
            $("#supplier").html('<option value="">Error loading suppliers</option>');
            $("#supplier").selectpicker('refresh');
        }
    });
}

// Load raw materials into dropdown
function loadRawMaterials() {
    $.ajax({
        url: 'php_action/fetchRawMaterialsForSelect.php',
        type: 'post',
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                var html = '<option value="">~~SELECT~~</option>';
                response.data.forEach(function(material) {
                    html += '<option value="'+material.id+'" data-unit="'+material.unit+'" data-cost="'+material.cost_per_unit+'">'+material.text+'</option>';
                });
                $("[id^=productName]").html(html);
                $('.selectpicker').selectpicker('refresh');
            } else {
                $("[id^=productName]").html('<option value="">Error loading materials</option>');
                $('.selectpicker').selectpicker('refresh');
                console.error("Error loading raw materials:", response.messages);
            }
        },
        error: function(xhr, status, error) {
            $("[id^=productName]").html('<option value="">Error loading materials</option>');
            $('.selectpicker').selectpicker('refresh');
            console.error("AJAX Error:", error);
        }
    });
}

// Load locations for a warehouse
function loadLocations(warehouseId) {
    $.ajax({
        url: 'php_action/fetchLocations.php',
        type: 'post',
        data: {warehouseId: warehouseId},
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                var html = '<option value="">~~SELECT~~</option>';
                response.data.forEach(function(location) {
                    html += '<option value="'+location.id+'">'+location.location_code+' ('+location.zone_name+')</option>';
                });
                $("[id^=location]").html(html);
                $('.selectpicker').selectpicker('refresh');
            } else {
                $("[id^=location]").html('<option value="">Error loading locations</option>');
                $('.selectpicker').selectpicker('refresh');
                console.error("Error loading locations:", response.messages);
            }
        },
        error: function(xhr, status, error) {
            $("[id^=location]").html('<option value="">Error loading locations</option>');
            $('.selectpicker').selectpicker('refresh');
            console.error("AJAX Error:", error);
        }
    });
}

// Add new row to product table
function addRow() {
    row++;
    var html = '<tr id="row'+row+'">'+
        '<td><select class="form-control selectpicker" name="productName[]" id="productName'+row+'" required data-live-search="true"><option value="">~~SELECT~~</option></select></td>'+
        '<td><input type="number" class="form-control" name="quantity[]" id="quantity'+row+'" onkeyup="getTotal('+row+')" min="1" required /></td>'+
        '<td><input type="number" class="form-control" name="rate[]" id="rate'+row+'" onkeyup="getTotal('+row+')" min="0" step="0.01" required /></td>'+
        '<td><input type="text" class="form-control" name="total[]" id="total'+row+'" disabled /></td>'+
        '<td><button type="button" class="btn btn-default" onclick="removeRow('+row+')"><i class="fa fa-trash"></i></button></td>'+
    '</tr>';
    
    $("#productTable tbody").append(html);
    loadRawMaterials();
}

// Remove row from product table
function removeRow(rowId) {
    if(row > 1) {
        $("#row"+rowId).remove();
        calculateSubTotal();
    }
}

// Calculate total for a row
function getTotal(rowId) {
    var quantity = parseFloat($("#quantity"+rowId).val() || 0);
    var rate = parseFloat($("#rate"+rowId).val() || 0);
    var total = quantity * rate;
    $("#total"+rowId).val(total.toFixed(2));
    calculateSubTotal();
}

// Calculate subtotal
function calculateSubTotal() {
    var subTotal = 0;
    $("input[name='total[]']").each(function() {
        subTotal += parseFloat($(this).val() || 0);
    });
    $("#subTotal").val(subTotal.toFixed(2));
    $("#subTotalValue").val(subTotal.toFixed(2));
    calculateGrandTotal();
}

// Calculate grand total
function calculateGrandTotal() {
    var subTotal = parseFloat($("#subTotalValue").val()) || 0;
    var vat = parseFloat($("#vat").val()) || 0;
    var vatAmount = (subTotal * vat) / 100;
    
    // Update VAT amount field
    $("#vatAmount").val(vatAmount.toFixed(2));
    
    var total = subTotal + vatAmount;
    
    // Handle withholding tax (2% of subtotal)
    if($("#withholding_enabled").is(':checked')) {
        var withholdingAmount = (subTotal * 0.02); // 2% of subtotal
        $("#withholding_amount").prop('disabled', false);
        $("#withholding_amount").val(withholdingAmount.toFixed(2));
        total -= withholdingAmount;
    } else {
        $("#withholding_amount").prop('disabled', true).val('0.00');
    }
    
    // Update displayed and hidden grand total
    $("#grandTotal").val(formatNumber(total));
    $("#grandTotalValue").val(total.toFixed(2));
}

// Helper function to format numbers with commas
function formatNumber(num) {
    return num.toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,')
}

// Edit purchase
function editPurchase(purchaseNumber) {
    if(purchaseNumber) {
        // Reset form and messages
        $('#editPurchaseForm')[0].reset();
        $('#edit-purchase-messages').html('');
        
        // Load suppliers for edit form
        loadSuppliersForEdit();
        
        $.ajax({
            url: 'php_action/fetchSelectedPurchase.php',
            type: 'post',
            data: {purchaseNumber: purchaseNumber},
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    // Set purchase ID
                    $('#editPurchaseId').val(response.data.id);
                    
                    // Set supplier
                    $('#editSupplier').val(response.data.supplier_id);
                    $('.selectpicker').selectpicker('refresh');
                    
                    // Set purchase date
                    $('#editPurchaseDate').val(response.data.purchase_date);
                    
                    // Set warehouse
                    $('#editWarehouse').val(response.data.warehouse_id);
                    $('.selectpicker').selectpicker('refresh');
                    
                    // Clear existing rows
                    $('#editProductTable tbody').empty();
                    
                    // Add product rows
                    if(response.data.items && response.data.items.length > 0) {
                        response.data.items.forEach(function(item, index) {
                            addEditRow();
                            var rowCount = $('#editProductTable tbody tr').length;
                            
                            // Wait for raw materials to load before setting values
                            setTimeout(function() {
                                $('#editProductName'+rowCount).val(item.product_id);
                                $('#editQuantity'+rowCount).val(item.quantity);
                                $('#editRate'+rowCount).val(item.rate);
                                $('#editTotal'+rowCount).val(item.total);
                                $('.selectpicker').selectpicker('refresh');
                                getEditTotal(rowCount);
                            }, 500);
                        });
                    } else {
                        addEditRow();
                    }
                    
                    // Set other values
                    $('#editVat').val(response.data.vat);
                    $('#editWithholdingEnabled').prop('checked', response.data.withholding_tax_enabled == 1);
                    $('#editWithholdingAmount').val(response.data.withholding_tax_amount);
                    $('#editWithholdingAmount').prop('disabled', !response.data.withholding_tax_enabled);
                    $('#editNote').val(response.data.note);
                    
                    // Calculate totals
                    calculateEditSubTotal();
                    
                    // Show modal
                    $('#editPurchaseModal').modal('show');
                } else {
                    alert('Error loading purchase details: ' + response.messages);
                }
            },
            error: function(xhr, status, error) {
                alert('Error loading purchase details');
                console.error(error);
            }
        });
    }
}

// Load suppliers for edit form
function loadSuppliersForEdit() {
    $.ajax({
        url: 'php_action/fetchSuppliers.php',
        type: 'post',
        dataType: 'json',
        success: function(response) {
            if(response.success == true) {
                var html = '<option value="">~~SELECT~~</option>';
                response.data.forEach(function(supplier) {
                    html += '<option value="'+supplier.id+'">'+supplier.company_name+'</option>';
                });
                $("#editSupplier").html(html);
                $("#editSupplier").selectpicker('refresh');
            }
        }
    });
}

// Load locations for edit form
function loadLocationsForEdit(warehouseId) {
    $.ajax({
        url: 'php_action/fetchLocations.php',
        type: 'post',
        data: {warehouseId: warehouseId},
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                var html = '<option value="">~~SELECT~~</option>';
                response.data.forEach(function(location) {
                    html += '<option value="'+location.id+'">'+location.location_code+' ('+location.zone_name+')</option>';
                });
                $("#editProductTable [name='location[]']").html(html);
                $('.selectpicker').selectpicker('refresh');
            }
        }
    });
}

// Add new row to edit product table
function addEditRow() {
    var rowCount = $('#editProductTable tbody tr').length;
    rowCount++;
    
    var html = '<tr id="editRow'+rowCount+'">'+
        '<td><select class="form-control selectpicker" name="productName[]" id="editProductName'+rowCount+'" required data-live-search="true"><option value="">~~SELECT~~</option></select></td>'+
        '<td><input type="number" class="form-control" name="quantity[]" id="editQuantity'+rowCount+'" onkeyup="getEditTotal('+rowCount+')" min="1" required /></td>'+
        '<td><input type="number" class="form-control" name="rate[]" id="editRate'+rowCount+'" onkeyup="getEditTotal('+rowCount+')" min="0" step="0.01" required /></td>'+
        '<td><input type="text" class="form-control" name="total[]" id="editTotal'+rowCount+'" disabled /></td>'+
        '<td><button type="button" class="btn btn-default" onclick="removeEditRow('+rowCount+')"><i class="fa fa-trash"></i></button></td>'+
    '</tr>';
    
    $("#editProductTable tbody").append(html);
    loadRawMaterialsForEdit(rowCount);
    $('.selectpicker').selectpicker('refresh');
}

// Remove row from edit product table
function removeEditRow(rowId) {
    if($('#editProductTable tbody tr').length > 1) {
        $("#editRow"+rowId).remove();
        calculateEditSubTotal();
    }
}

// Calculate total for a row in edit form
function getEditTotal(rowId) {
    var quantity = parseFloat($("#editQuantity"+rowId).val() || 0);
    var rate = parseFloat($("#editRate"+rowId).val() || 0);
    var total = quantity * rate;
    $("#editTotal"+rowId).val(total.toFixed(2));
    calculateEditSubTotal();
}

// Calculate subtotal in edit form
function calculateEditSubTotal() {
    var subTotal = 0;
    $("#editProductTable input[name='total[]']").each(function() {
        subTotal += parseFloat($(this).val() || 0);
    });
    $("#editSubTotal").val(subTotal.toFixed(2));
    $("#editSubTotalValue").val(subTotal.toFixed(2));
    calculateEditGrandTotal();
}

// Calculate grand total in edit form
function calculateEditGrandTotal() {
    var subTotal = parseFloat($("#editSubTotalValue").val()) || 0;
    var vat = parseFloat($("#editVat").val()) || 0;
    var vatAmount = (subTotal * vat) / 100;
    
    var total = subTotal + vatAmount;
    
    // Handle withholding tax (2% of subtotal)
    if($("#editWithholdingEnabled").is(':checked')) {
        var withholdingAmount = (subTotal * 0.02); // 2% of subtotal
        $("#editWithholdingAmount").prop('disabled', false);
        $("#editWithholdingAmount").val(withholdingAmount.toFixed(2));
        total -= withholdingAmount;
    } else {
        $("#editWithholdingAmount").prop('disabled', true).val('0.00');
    }
    
    // Update displayed and hidden grand total
    $("#editGrandTotal").val(formatNumber(total));
    $("#editGrandTotalValue").val(total.toFixed(2));
}

// Load raw materials for edit form
function loadRawMaterialsForEdit(rowId) {
    $.ajax({
        url: 'php_action/fetchRawMaterialsForSelect.php',
        type: 'post',
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                var html = '<option value="">~~SELECT~~</option>';
                response.data.forEach(function(material) {
                    html += '<option value="'+material.id+'" data-unit="'+material.unit+'" data-cost="'+material.cost_per_unit+'">'+material.text+'</option>';
                });
                $("#editProductName"+rowId).html(html);
                $('.selectpicker').selectpicker('refresh');
            } else {
                console.error("Error loading raw materials:", response.messages);
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error:", error);
        }
    });
}

// Handle edit form submission
$('#editPurchaseForm').submit(function(e) {
    e.preventDefault();
    
    // Validate form
    if(!validateEditForm()) {
        return false;
    }
    
    // Calculate totals before submission
    calculateEditSubTotal();
    calculateEditGrandTotal();
    
    // Get form data
    var formData = new FormData();
    
    // Add basic form fields
    formData.append('purchaseId', $('#editPurchaseId').val());
    formData.append('supplier', $('#editSupplier').val());
    formData.append('purchaseDate', $('#editPurchaseDate').val());
    formData.append('warehouse', $('#editWarehouse').val());
    formData.append('subTotalValue', $('#editSubTotalValue').val());
    formData.append('vat', $('#editVat').val());
    formData.append('grandTotalValue', $('#editGrandTotalValue').val());
    formData.append('withholding_enabled', $('#editWithholdingEnabled').is(':checked') ? 1 : 0);
    formData.append('withholding_amount', $('#editWithholdingAmount').val() || 0);
    formData.append('note', $('#editNote').val());
    
    // Add product data arrays
    $("#editProductTable tbody tr").each(function() {
        var productName = $(this).find('[name="productName[]"]').val();
        if(productName) {
            formData.append('productName[]', productName);
            formData.append('quantity[]', $(this).find('[name="quantity[]"]').val());
            formData.append('rate[]', $(this).find('[name="rate[]"]').val());
            formData.append('total[]', $(this).find('[name="total[]"]').val().replace(/,/g, ''));
        }
    });
    
    // Debug log
    console.log('Form Data:', {
        purchaseId: $('#editPurchaseId').val(),
        supplier: $('#editSupplier').val(),
        purchaseDate: $('#editPurchaseDate').val(),
        warehouse: $('#editWarehouse').val(),
        subTotal: $('#editSubTotalValue').val(),
        vat: $('#editVat').val(),
        grandTotal: $('#editGrandTotalValue').val(),
        withholding_enabled: $('#editWithholdingEnabled').is(':checked'),
        withholding_amount: $('#editWithholdingAmount').val()
    });
    
    $.ajax({
        url: 'php_action/editPurchase.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        cache: false,
        contentType: false,
        processData: false,
        success: function(response) {
            if(response.success) {
                // Close modal
                $('#editPurchaseModal').modal('hide');
                
                // Reload table
                manageProductTable.ajax.reload(null, false);
                
                // Show success message
                toastr.success(response.messages);
            } else {
                // Show error message
                toastr.error(response.messages);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            console.error('Response:', xhr.responseText);
            toastr.error('Error occurred while saving purchase');
        }
    });
    
    return false;
});

// Validate edit form
function validateEditForm() {
    var isValid = true;
    
    // Check supplier
    if(!$("#editSupplier").val()) {
        alert("Please select a supplier");
        isValid = false;
    }

    // Check warehouse
    if(!$("#editWarehouse").val()) {
        alert("Please select a warehouse");
        isValid = false;
    }
    
    // Check if at least one product is selected
    var hasProducts = false;
    $("#editProductTable [name='productName[]']").each(function() {
        if($(this).val()) hasProducts = true;
    });
    
    if(!hasProducts) {
        alert("Please add at least one product");
        isValid = false;
    }
    
    // Check quantities and rates
    $("#editProductTable [name='quantity[]']").each(function() {
        if($(this).closest('tr').find('[name="productName[]"]').val() && (!$(this).val() || $(this).val() <= 0)) {
            alert("Please enter valid quantities for all products");
            isValid = false;
            return false;
        }
    });
    
    $("#editProductTable [name='rate[]']").each(function() {
        if($(this).closest('tr').find('[name="productName[]"]').val() && (!$(this).val() || $(this).val() < 0)) {
            alert("Please enter valid rates for all products");
            isValid = false;
            return false;
        }
    });

    // Check locations
    $("#editProductTable [name='location[]']").each(function() {
        if($(this).closest('tr').find('[name="productName[]"]').val() && !$(this).val()) {
            alert("Please select storage locations for all products");
            isValid = false;
            return false;
        }
    });
    
    return isValid;
}

// Handle warehouse change in edit form
$('#editWarehouse').on('change', function() {
    var warehouseId = $(this).val();
    if(warehouseId) {
        loadLocationsForEdit(warehouseId);
    } else {
        $("#editProductTable [name='location[]']").html('<option value="">~~SELECT~~</option>');
        $('.selectpicker').selectpicker('refresh');
    }
});

// Handle VAT and withholding tax changes in edit form
$('#editVat').on('change keyup', function() {
    calculateEditGrandTotal();
});

$('#editWithholdingEnabled').on('change', function() {
    calculateEditGrandTotal();
});

$('#editWithholdingAmount').on('change keyup', function() {
    if($('#editWithholdingEnabled').is(':checked')) {
        calculateEditGrandTotal();
    }
});

// Remove purchase
function removePurchase(purchaseNumber) {
    if(purchaseNumber) {
        $("#removePurchaseModal").modal('show');
        $("#removePurchaseBtn").unbind('click').bind('click', function() {
            $.ajax({
                url: 'php_action/removePurchase.php',
                type: 'post',
                data: {purchaseNumber: purchaseNumber},
                dataType: 'json',
                success:function(response) {
                    if(response.success == true) {
                        $("#removePurchaseModal").modal('hide');
                        manageProductTable.ajax.reload(null, false);
                        $('.remove-messages').html('<div class="alert alert-success">'+
                            '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                            '<strong><i class="glyphicon glyphicon-ok-sign"></i></strong> '+ response.messages +
                        '</div>');
                    }
                }
            });
        });
    }
}

// Add form validation function
function validateForm() {
    var isValid = true;
    
    // Check supplier
    if(!$("#supplier").val()) {
        alert("Please select a supplier");
        isValid = false;
    }

    // Check warehouse
    if(!$("#warehouse").val()) {
        alert("Please select a warehouse");
        isValid = false;
    }
    
    // Check if at least one product is selected
    var hasProducts = false;
    $("[id^=productName]").each(function() {
        if($(this).val()) hasProducts = true;
    });
    
    if(!hasProducts) {
        alert("Please add at least one product");
        isValid = false;
    }
    
    // Check quantities and rates
    $("[id^=quantity]").each(function() {
        if($(this).closest('tr').find('[id^=productName]').val() && (!$(this).val() || $(this).val() <= 0)) {
            alert("Please enter valid quantities for all products");
            isValid = false;
            return false;
        }
    });
    
    $("[id^=rate]").each(function() {
        if($(this).closest('tr').find('[id^=productName]').val() && (!$(this).val() || $(this).val() < 0)) {
            alert("Please enter valid rates for all products");
            isValid = false;
            return false;
        }
    });
    
    return isValid;
}

// View purchase details
function viewPurchase(purchaseNumber) {
    if(purchaseNumber) {
        console.log('Viewing purchase:', purchaseNumber); // Debug log
        $.ajax({
            url: 'php_action/fetchPurchaseDetails.php',
            type: 'post',
            data: {purchaseNumber: purchaseNumber},
            dataType: 'json',
            success:function(response) {
                console.log('Response:', response); // Debug log
                if(response.success) {
                    // Populate purchase details
                    $("#view_purchase_number").text(response.data.purchase_number);
                    $("#view_supplier").text(response.data.supplier_name);
                    $("#view_purchase_date").text(response.data.purchase_date);
                    $("#view_warehouse").text(response.data.warehouse_name);
                    $("#view_sub_total").text(response.data.sub_total);
                    $("#view_vat_amount").text(response.data.vat_amount);
                    $("#view_withholding").text(response.data.withholding_tax_amount || '0.00');
                    $("#view_grand_total").text(response.data.grand_total);
                    $("#view_note").text(response.data.note || 'No notes available');

                    // Populate purchase items table
                    var itemsHtml = '';
                    if(response.data.items && response.data.items.length > 0) {
                        response.data.items.forEach(function(item) {
                            itemsHtml += '<tr>'+
                                '<td>'+(item.material_name || 'N/A')+'</td>'+
                                '<td>'+item.quantity+'</td>'+
                                '<td>'+item.rate+'</td>'+
                                '<td>'+item.total+'</td>'+
                            '</tr>';
                        });
                    } else {
                        itemsHtml = '<tr><td colspan="4" class="text-center">No items found</td></tr>';
                    }
                    $("#purchaseItemsTable").html(itemsHtml);

                    // Show modal
                    $("#viewPurchaseModal").modal('show');
                } else {
                    alert('Error loading purchase details: ' + response.messages);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
                alert('Error loading purchase details. Please check the console for more information.');
            }
        });
    }
}

// Update payment status
function updatePaymentStatus(purchaseNumber) {
    if (!purchaseNumber) {
        toastr.error('Purchase number is required');
        return;
    }

    // Reset form and messages
    $('#paymentForm')[0].reset();
    $('#payment-messages').html('');
    $('#purchase_number').val(purchaseNumber);
    $('#payment_purchase_number').val(purchaseNumber);

    // Set default date to today
    var today = new Date().toISOString().split('T')[0];
    $('#payment_date').val(today);

    // Hide payment details initially
    $('.payment-details').hide();
    $('#paid_amount_group').hide();

        // Get purchase details
        $.ajax({
            url: 'php_action/fetchSelectedPurchase.php',
        type: 'POST',
            data: {purchaseNumber: purchaseNumber},
            dataType: 'json',
            success: function(response) {
            if (response.success) {
                var data = response.data;
                var grandTotal = parseFloat(data.grand_total);
                var currentPaidAmount = parseFloat(data.paid_amount || 0);
                var remainingAmount = grandTotal - currentPaidAmount;

                // Set values in form
                $('#payment_grand_total').val(grandTotal.toFixed(2));
                $('#current_paid_amount').val(currentPaidAmount.toFixed(2));
                
                // Set current payment status
                $('#payment_status').val(data.payment_status || 'unpaid');

                // Configure paid amount field
                $('#paid_amount')
                    .attr('min', (currentPaidAmount + 0.01).toFixed(2))
                    .attr('max', grandTotal.toFixed(2))
                    .val('');
                    
                    // Show modal
                    $('#addPaymentModal').modal('show');

                // Handle payment status change
                $('#payment_status').off('change').on('change', function() {
                    var status = $(this).val();
                    handlePaymentStatusChange(status, grandTotal, currentPaidAmount);
                });

                // Trigger change event to set initial state
                $('#payment_status').trigger('change');
                } else {
                toastr.error(response.messages || 'Error loading purchase details');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
            toastr.error('Error loading purchase details');
        }
    });

    // Handle form submission
    $('#paymentForm').off('submit').on('submit', function(e) {
    e.preventDefault();
    
        // Validate form
        if (!validatePaymentForm()) {
            return false;
        }

        // Create FormData object
    var formData = new FormData(this);
    
        // Submit form
    $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
        data: formData,
        dataType: 'json',
            cache: false,
        contentType: false,
            processData: false,
        success: function(response) {
                if (response.success) {
                // Close modal
                $('#addPaymentModal').modal('hide');
                
                // Reset form
                    $('#paymentForm')[0].reset();
                
                // Reload table
                manageProductTable.ajax.reload(null, false);
                
                // Show success message
                    toastr.success(response.messages);
            } else {
                    toastr.error(response.messages);
                }
            },
            error: function(xhr, status, error) {
                console.error('Payment update error:', error);
                console.error('Server response:', xhr.responseText);
                toastr.error('Error occurred while updating payment');
            }
        });

        return false;
    });
}

// Handle payment status change
function handlePaymentStatusChange(status, grandTotal, currentPaidAmount) {
    var paidAmountGroup = $('#paid_amount_group');
    var paymentDetails = $('.payment-details');
    var paymentMethod = $('#payment_method');
    var paidAmount = $('#paid_amount');

    switch(status) {
        case 'partially_paid':
            paidAmountGroup.show();
            paymentDetails.show();
            paymentMethod.prop('required', true);
            paidAmount
                .prop('required', true)
                .attr('min', (currentPaidAmount + 0.01).toFixed(2))
                .attr('max', grandTotal.toFixed(2))
                .val('');
            break;
            
        case 'paid':
            paidAmountGroup.hide();
            paymentDetails.show();
            paymentMethod.prop('required', true);
            paidAmount
                .prop('required', false)
                .val(grandTotal.toFixed(2));
            break;
            
        case 'unpaid':
        default:
            paidAmountGroup.hide();
            paymentDetails.hide();
            paymentMethod.prop('required', false);
            paidAmount
                .prop('required', false)
                .val('0.00');
            break;
    }
}

// Validate payment form
function validatePaymentForm() {
    var status = $('#payment_status').val();
    if (!status) {
        toastr.error('Please select payment status');
        return false;
    }

    var paymentDate = $('#payment_date').val();
    if (!paymentDate) {
        toastr.error('Please select payment date');
        return false;
    }

    // Validate date is not in future
    var selectedDate = new Date(paymentDate);
    var today = new Date();
    today.setHours(0, 0, 0, 0);
    if (selectedDate > today) {
        toastr.error('Payment date cannot be in the future');
        return false;
    }

    if (status === 'partially_paid' || status === 'paid') {
        var paymentMethod = $('#payment_method').val();
        if (!paymentMethod) {
            toastr.error('Please select payment method');
            return false;
        }

        var paidAmount = parseFloat($('#paid_amount').val() || 0);
        var grandTotal = parseFloat($('#payment_grand_total').val() || 0);
        var currentPaidAmount = parseFloat($('#current_paid_amount').val() || 0);

        if (status === 'partially_paid') {
            if (!paidAmount || paidAmount <= currentPaidAmount) {
                toastr.error('New paid amount must be greater than current paid amount');
                return false;
            }
            if (paidAmount >= grandTotal) {
                toastr.error('For partial payment, amount must be less than grand total');
                return false;
            }
        }
    }

    return true;
}

// View payment history
function viewPaymentHistory(purchaseNumber) {
    if(purchaseNumber) {
        // Clear all existing content in the modal body
        $('#paymentHistoryModal .modal-body').html(
            '<div class="summary-section"></div>' +
            '<table class="table table-bordered table-striped" id="paymentHistoryTable">' +
            '<thead>' +
            '<tr>' +
            '<th>Date</th>' +
            '<th class="text-right">Amount</th>' +
            '<th>Method</th>' +
            '<th>Reference</th>' +
            '<th>Notes</th>' +
            '</tr>' +
            '</thead>' +
            '<tbody></tbody>' +
            '</table>'
        );

        $.ajax({
            url: 'php_action/fetchPurchasePayments.php',
            type: 'post',
            data: {purchaseNumber: purchaseNumber},
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    // Calculate total paid from payment history
                    var totalPaid = 0;
                    if(response.payments && response.payments.length > 0) {
                        response.payments.forEach(function(payment) {
                            // Parse amount as float, removing any commas
                            totalPaid += parseFloat(payment.amount.replace(/,/g, ''));
                        });
                    }

                    // Get grand total from the table
                    var table = $('#managePurchaseTable').DataTable();
                    var rowData = null;
                    
                    table.rows().every(function() {
                        if (this.data().purchase_number === purchaseNumber) {
                            rowData = this.data();
                            return false; // Break the loop
                        }
                    });

                    if (rowData) {
                        // Parse grand total as float, removing any commas
                        var grandTotal = parseFloat(rowData.grand_total.toString().replace(/,/g, ''));
                        var unpaidAmount = grandTotal - totalPaid;

                        // Format numbers with 2 decimal places
                        grandTotal = parseFloat(grandTotal).toFixed(2);
                        totalPaid = parseFloat(totalPaid).toFixed(2);
                        unpaidAmount = parseFloat(unpaidAmount).toFixed(2);

                        // Add summary information with better styling
                        var summaryHtml = '<h4 class="text-primary"><i class="fa fa-info-circle"></i> Payment Summary</h4>' +
                            '<div class="table-responsive">' +
                            '<table class="table table-bordered table-striped">' +
                            '<thead class="bg-primary" style="color: white;">' +
                            '<tr>' +
                            '<th>Description</th>' +
                            '<th class="text-right" style="width: 150px;">Amount</th>' +
                            '</tr>' +
                            '</thead>' +
                            '<tbody>' +
                            '<tr>' +
                            '<td><strong>Grand Total</strong></td>' +
                            '<td class="text-right">' + grandTotal + '</td>' +
                            '</tr>' +
                            '<tr>' +
                            '<td><strong>Total Paid</strong></td>' +
                            '<td class="text-right text-success">' + totalPaid + '</td>' +
                            '</tr>' +
                            '<tr class="active">' +
                            '<td><strong>Remaining Balance</strong></td>' +
                            '<td class="text-right text-danger"><strong>' + unpaidAmount + '</strong></td>' +
                            '</tr>' +
                            '</tbody>' +
                            '</table>' +
                            '</div>' +
                            '<h4 class="text-primary" style="margin-top: 20px;"><i class="fa fa-history"></i> Payment History</h4>';

                        $('#paymentHistoryModal .modal-body .summary-section').html(summaryHtml);
                    }
                    
                    // Add payment records
                    if(response.payments && response.payments.length > 0) {
                        var tbody = '';
                        response.payments.forEach(function(payment) {
                            tbody += '<tr>' +
                                '<td>' + payment.payment_date + '</td>' +
                                '<td class="text-right">' + parseFloat(payment.amount).toFixed(2) + '</td>' +
                                '<td>' + (payment.payment_method || '').toUpperCase() + '</td>' +
                                '<td>' + (payment.reference_number || 'N/A') + '</td>' +
                                '<td>' + (payment.notes || 'No notes') + '</td>' +
                            '</tr>';
                        });
                        $('#paymentHistoryTable tbody').html(tbody);
                    } else {
                        $('#paymentHistoryTable tbody').html('<tr><td colspan="5" class="text-center">No payment records found</td></tr>');
                    }
                    
                    // Show modal
                    $('#paymentHistoryModal').modal('show');
                } else {
                    toastr.error(response.messages || 'Error fetching payment history');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                toastr.error('Error fetching payment history');
            }
        });
    }
} 

// Add modal cleanup
$('#paymentHistoryModal').on('hidden.bs.modal', function () {
    // Clear all content in the modal body
    $('#paymentHistoryModal .modal-body').empty();
});

// Handle raw material selection in edit form
$(document).on('change', '[id^=editProductName]', function() {
    var row = $(this).attr('id').replace('editProductName', '');
    var selectedOption = $(this).find('option:selected');
    
    // Get the unit and cost from data attributes
    var unit = selectedOption.data('unit');
    var cost = selectedOption.data('cost');
    
    // Update the rate field with the cost
    $('#editRate' + row).val(cost);
    
    // Update quantity field placeholder with the unit
    $('#editQuantity' + row).attr('placeholder', 'Quantity in ' + unit);
    
    // Recalculate total for this row
    getEditTotal(row);
}); 