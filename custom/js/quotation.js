$(document).ready(function() {
    // Load clients on page load
    loadClients();
    
    // Bind events to the first row
    bindRowEvents($('#row1'));
    
    // Load products for the first row
    loadProducts($('#row1 .product-select'));
    
    // Initialize calculations
    calculateTotals();

    // Handle client creation form submission
    $('#createClientForm').on('submit', function(e) {
        e.preventDefault();
        createClient();
    });

    // Handle withholding tax checkbox
    $('#withholdingEnabled').on('change', function() {
        calculateTotals();
    });

    // Handle form submission
    $('#createQuotationForm').on('submit', function(e) {
        e.preventDefault();
        createQuotation();
    });
});

// Load clients into select dropdown
function loadClients() {
    $.ajax({
        url: 'php_action/fetchClients.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                var clientSelect = $('#clientId');
                clientSelect.empty();
                clientSelect.append('<option value="">Select Client</option>');
                
                $.each(response.data, function(i, client) {
                    clientSelect.append('<option value="' + client.id + '">' + client.company_name + '</option>');
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading clients:', error);
        }
    });
}

// Load products into select dropdown
function loadProducts(selectElement) {
    $.ajax({
        url: 'php_action/fetchProductForQuotation.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                selectElement.empty();
                selectElement.append('<option value="">Select Product</option>');
                
                $.each(response.data, function(i, product) {
                    selectElement.append(
                        $('<option>', {
                            value: product.product_id,
                            text: product.name,
                            'data-price': product.selling_price,
                            'data-unit': product.unit
                        })
                    );
                });
            } else {
                console.error('Error:', response.messages);
                $('#messages').html('<div class="alert alert-danger">Error loading products: ' + response.messages + '</div>');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading products:', error);
            $('#messages').html('<div class="alert alert-danger">Error loading products. Please try again.</div>');
        }
    });
}

// Bind events to product row
function bindRowEvents(row) {
    // Product selection change
    row.find('.product-select').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        if (selectedOption.val()) {
            var price = selectedOption.data('price');
            
            var priceInput = $(this).closest('tr').find('.product-price');
            var quantityInput = $(this).closest('tr').find('.product-quantity');
            
            // Set initial price and quantity
            priceInput.val(price);
            if (!quantityInput.val()) {
                quantityInput.val('1');
            }
            
            // Calculate total automatically
            calculateRowTotal($(this).closest('tr'));
        } else {
            // Reset inputs if no product is selected
            var row = $(this).closest('tr');
            row.find('.product-price').val('');
            row.find('.product-quantity').val('');
            row.find('.product-total').val('');
            calculateTotals();
        }
    });

    // Quantity change - calculate immediately
    row.find('.product-quantity').on('input', function() {
        var quantity = parseInt($(this).val()) || 0;
        if (quantity < 0) {
            $(this).val('0');
        }
        calculateRowTotal($(this).closest('tr'));
    });

    // Price change - calculate immediately
    row.find('.product-price').on('input', function() {
        var price = parseFloat($(this).val()) || 0;
        if (price < 0) {
            $(this).val('0');
        }
        calculateRowTotal($(this).closest('tr'));
    });

    // Remove row button
    row.find('.remove-row').on('click', function() {
        removeRow(this);
    });
}

// Add new product row
function addRow() {
    var rowCount = $('#productTable tbody tr').length;
    var newRow = $('#row1').clone();
    var newId = 'row' + (rowCount + 1);
    
    newRow.attr('id', newId);
    newRow.find('input').val('');
    newRow.find('select').val('');
    
    $('#productTable tbody').append(newRow);
    
    // Bind events for the new row
    bindRowEvents(newRow);
    
    // Load products for the new row
    loadProducts($('#' + newId + ' .product-select'));
}

// Remove product row
function removeRow(button) {
    if($('#productTable tbody tr').length > 1) {
        $(button).closest('tr').remove();
        calculateTotals();
    } else {
        alert('Cannot remove the last row');
    }
}

// Calculate row total
function calculateRowTotal(row) {
    var price = parseFloat(row.find('.product-price').val()) || 0;
    var quantity = parseInt(row.find('.product-quantity').val()) || 0;
    var total = price * quantity;
    
    // Update total field
    row.find('.product-total').val(total.toFixed(2));
    
    // Recalculate all totals
    calculateTotals();
}

// Calculate all totals
function calculateTotals() {
    var subTotal = 0;
    
    // Calculate subtotal from all rows
    $('#productTable tbody tr').each(function() {
        var price = parseFloat($(this).find('.product-price').val()) || 0;
        var quantity = parseInt($(this).find('.product-quantity').val()) || 0;
        var rowTotal = price * quantity;
        
        // Update row total
        $(this).find('.product-total').val(rowTotal.toFixed(2));
        subTotal += rowTotal;
    });
    
    // Set subtotal
    $('#subTotal').val(subTotal.toFixed(2));
    
    // Calculate VAT (15%)
    var vatAmount = subTotal * 0.15;
    $('#vatAmount').val(vatAmount.toFixed(2));
    
    // Calculate withholding tax if enabled (2%)
    var withholdingAmount = 0;
    if($('#withholdingEnabled').is(':checked')) {
        withholdingAmount = subTotal * 0.02;
        $('#withholdingAmount').val(withholdingAmount.toFixed(2));
    } else {
        $('#withholdingAmount').val('0.00');
    }
    
    // Calculate grand total
    var grandTotal = subTotal + vatAmount - withholdingAmount;
    $('#grandTotal').val(grandTotal.toFixed(2));
}

// Create new client
function createClient() {
    $.ajax({
        url: 'php_action/createClient.php',
        type: 'POST',
        data: $('#createClientForm').serialize(),
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                $('#createClientModal').modal('hide');
                $('#createClientForm')[0].reset();
                loadClients();
                
                // Show success message
                $('#messages').html('<div class="alert alert-success">' + response.messages + '</div>');
            } else {
                // Show error message
                $('#create-client-messages').html('<div class="alert alert-danger">' + response.messages + '</div>');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error creating client:', error);
            $('#create-client-messages').html('<div class="alert alert-danger">Error creating client</div>');
        }
    });
}

// Create quotation
function createQuotation() {
    $.ajax({
        url: 'php_action/createQuotation.php',
        type: 'POST',
        data: $('#createQuotationForm').serialize(),
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                window.location.href = 'manageQuotations.php';
            } else {
                $('#messages').html('<div class="alert alert-danger">' + response.messages + '</div>');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error creating quotation:', error);
            $('#messages').html('<div class="alert alert-danger">Error creating quotation</div>');
        }
    });
} 