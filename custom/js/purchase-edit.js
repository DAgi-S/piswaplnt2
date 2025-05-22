$(document).ready(function() {
    // Initialize edit purchase functionality
    initializeEditPurchase();
});

function initializeEditPurchase() {
    // Handle edit button click
    $(document).on('click', '.editPurchaseBtn', function() {
        var purchaseId = $(this).data('id');
        fetchPurchaseDetails(purchaseId);
    });
}

function fetchPurchaseDetails(purchaseId) {
    $.ajax({
        url: 'php_action/fetchSelectedPurchase.php',
        type: 'POST',
        data: { purchaseId: purchaseId },
        dataType: 'json',
        success: function(response) {
            console.log('Fetch purchase response:', response);
            
            if (response.success) {
                // Load form content
                $('#editPurchaseModal .modal-body').html(response.html);
                
                // Show modal
                $('#editPurchaseModal').modal('show');
                
                // Initialize form elements after modal is shown
                $('#editPurchaseModal').on('shown.bs.modal', function() {
                    initializeFormElements();
                    calculateTotals();
                });
            } else {
                alert('Error: ' + response.messages);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            alert('Error fetching purchase details');
        }
    });
}

function initializeFormElements() {
    // Initialize select2 for supplier
    $('#supplier').select2({
        width: '100%',
        dropdownParent: $('#editPurchaseModal')
    }).on('select2:open', function() {
        // Ensure the dropdown is above the modal
        $('.select2-container').css('z-index', 9999);
    });

    // Initialize select2 for all product dropdowns
    $('.product-select').each(function() {
        $(this).select2({
            width: '100%',
            dropdownParent: $('#editPurchaseModal')
        }).on('select2:open', function() {
            $('.select2-container').css('z-index', 9999);
        });
    });

    // Initialize datepicker
    $('#purchaseDate').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        todayHighlight: true
    });

    // Handle quantity and rate changes
    $(document).off('input', '.quantity, .rate').on('input', '.quantity, .rate', function() {
        var row = $(this).closest('tr');
        calculateLineTotal(row);
    });

    // Handle withholding tax checkbox
    $(document).off('change', '#withholdingTaxEnabled').on('change', '#withholdingTaxEnabled', function() {
        calculateTotals();
    });

    // Handle product selection
    $(document).off('change', '.product-select').on('change', '.product-select', function() {
        var row = $(this).closest('tr');
        calculateLineTotal(row);
    });

    // Handle remove item button
    $(document).off('click', '.removeItem').on('click', '.removeItem', function() {
        if ($('#purchaseItems tbody tr').length > 1) {
            $(this).closest('tr').remove();
            calculateTotals();
        } else {
            alert('At least one item is required');
        }
    });

    // Handle add item button
    $(document).off('click', '#addItemBtn').on('click', '#addItemBtn', function() {
        addItemRow();
    });

    // Handle save changes button
    $(document).off('click', '#saveChangesBtn').on('click', '#saveChangesBtn', function() {
        if (validatePurchaseForm()) {
            submitEditPurchase();
        }
    });

    // Initial calculation
    calculateTotals();
}

function calculateLineTotal(row) {
    var quantity = parseFloat(row.find('.quantity').val()) || 0;
    var rate = parseFloat(row.find('.rate').val()) || 0;
    var amount = quantity * rate;
    row.find('.amount').val(amount.toFixed(2));
    calculateTotals();
}

function calculateTotals() {
    var subTotal = 0;
    
    // Calculate subtotal from all line items
    $('#purchaseItems tbody tr').each(function() {
        var quantity = parseFloat($(this).find('.quantity').val()) || 0;
        var rate = parseFloat($(this).find('.rate').val()) || 0;
        var amount = quantity * rate;
        $(this).find('.amount').val(amount.toFixed(2));
        subTotal += amount;
    });
    
    // Format subtotal to 2 decimal places
    subTotal = parseFloat(subTotal.toFixed(2));
    $('#subTotal').val(subTotal.toFixed(2));
    
    // Calculate VAT (15%)
    var vat = parseFloat((subTotal * 0.15).toFixed(2));
    $('#vat').val(vat.toFixed(2));
    
    // Calculate withholding tax if enabled (2%)
    var withholdingAmount = 0;
    if ($('#withholdingTaxEnabled').is(':checked')) {
        withholdingAmount = parseFloat((subTotal * 0.02).toFixed(2));
    }
    $('#withholdingAmount').val(withholdingAmount.toFixed(2));
    
    // Calculate grand total
    var grandTotal = parseFloat((subTotal + vat - withholdingAmount).toFixed(2));
    $('#grandTotal').val(grandTotal.toFixed(2));
}

function addItemRow() {
    var rowCount = $('#purchaseItems tbody tr').length + 1;
    var productOptions = $('#purchaseItems tbody tr:first .product-select').html();
    
    var newRow = '<tr id="row' + rowCount + '">' +
        '<td><select class="form-control product-select" name="productId[]" required>' + productOptions + '</select></td>' +
        '<td><input type="number" class="form-control quantity" name="quantity[]" min="0.01" step="0.01" required></td>' +
        '<td><input type="number" class="form-control rate" name="rate[]" min="0.01" step="0.01" required></td>' +
        '<td><input type="number" class="form-control amount" name="amount[]" readonly></td>' +
        '<td><button type="button" class="btn btn-danger btn-sm removeItem"><i class="glyphicon glyphicon-trash"></i></button></td>' +
        '</tr>';
    
    $('#purchaseItems tbody').append(newRow);
    
    // Initialize select2 for the new product dropdown
    $('#row' + rowCount + ' .product-select').select2({
        width: '100%',
        dropdownParent: $('#editPurchaseModal')
    });
}

function validatePurchaseForm() {
    var isValid = true;
    var messages = [];
    
    // Clear previous messages
    $('#edit-purchase-messages').empty();
    
    // Validate supplier
    if (!$('#supplier').val() || $('#supplier').val() === '') {
        messages.push('Please select a supplier');
        isValid = false;
    }
    
    // Validate purchase date
    if (!$('#purchaseDate').val()) {
        messages.push('Please select a purchase date');
        isValid = false;
    }
    
    // Validate products
    var hasValidProducts = false;
    var validationFailed = false;
    
    $('#purchaseItems tbody tr').each(function() {
        var productId = $(this).find('.product-select').val();
        var quantity = parseFloat($(this).find('.quantity').val());
        var rate = parseFloat($(this).find('.rate').val());
        
        if (productId) {
            hasValidProducts = true;
            if (isNaN(quantity) || quantity <= 0) {
                messages.push('Please enter a valid quantity for all products');
                validationFailed = true;
                return false;
            }
            if (isNaN(rate) || rate <= 0) {
                messages.push('Please enter a valid rate for all products');
                validationFailed = true;
                return false;
            }
        }
    });
    
    if (!hasValidProducts) {
        messages.push('Please add at least one product');
        isValid = false;
    }
    
    if (validationFailed) {
        isValid = false;
    }
    
    // Display validation messages if any
    if (!isValid) {
        var messageHtml = '<div class="alert alert-danger">' +
            '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
            '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> ' +
            messages.join('<br>') +
            '</div>';
        $('#edit-purchase-messages').html(messageHtml);
    }
    
    return isValid;
}

function submitEditPurchase() {
    // Show loading message
    $('#edit-purchase-messages').html(
        '<div class="alert alert-info">' +
        '<i class="glyphicon glyphicon-refresh"></i> Updating purchase...' +
        '</div>'
    );
    
    // Disable save button
    $('#saveChangesBtn').prop('disabled', true);
    
    // Prepare form data
    var formData = {
        purchaseId: $('input[name="purchaseId"]').val(),
        supplier: $('#supplier').val(),
        purchaseDate: $('#purchaseDate').val(),
        note: $('#note').val(),
        subTotal: parseFloat($('#subTotal').val()).toFixed(2),
        vat: parseFloat($('#vat').val()).toFixed(2),
        withholdingTaxEnabled: $('#withholdingTaxEnabled').is(':checked') ? 1 : 0,
        withholdingAmount: parseFloat($('#withholdingAmount').val()).toFixed(2),
        grandTotal: parseFloat($('#grandTotal').val()).toFixed(2),
        productId: [],
        quantity: [],
        rate: [],
        amount: []
    };

    // Collect product data
    $('#purchaseItems tbody tr').each(function() {
        var productId = $(this).find('.product-select').val();
        var quantity = $(this).find('.quantity').val();
        var rate = $(this).find('.rate').val();
        var amount = $(this).find('.amount').val();
        
        if (productId && quantity && rate) {
            formData.productId.push(productId);
            formData.quantity.push(parseFloat(quantity).toFixed(2));
            formData.rate.push(parseFloat(rate).toFixed(2));
            formData.amount.push(parseFloat(amount).toFixed(2));
        }
    });

    // Send AJAX request
    $.ajax({
        url: 'php_action/editPurchase.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            console.log('Edit purchase response:', response);
            
            if (response.success) {
                // Close modal
                $('#editPurchaseModal').modal('hide');
                
                // Reload purchases table
                managePurchaseTable.ajax.reload(null, false);
                
                // Show success message
                $('.remove-messages').html(
                    '<div class="alert alert-success">' +
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                    '<strong><i class="glyphicon glyphicon-ok-sign"></i></strong> ' + 
                    response.messages +
                    '</div>'
                );
            } else {
                // Show error message
                $('#edit-purchase-messages').html(
                    '<div class="alert alert-danger">' +
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                    '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> ' + 
                    response.messages +
                    '</div>'
                );
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            console.error('Response:', xhr.responseText);
            
            $('#edit-purchase-messages').html(
                '<div class="alert alert-danger">' +
                '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> ' +
                'Error updating purchase: ' + error +
                '</div>'
            );
        },
        complete: function() {
            // Re-enable save button
            $('#saveChangesBtn').prop('disabled', false);
        }
    });
}