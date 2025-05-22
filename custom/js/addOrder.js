$(document).ready(function() {
    // Set default date
    var today = new Date();
    $('#orderDate').val(today.getFullYear() + '-' + ('0' + (today.getMonth() + 1)).slice(-2) + '-' + ('0' + today.getDate()).slice(-2));

    // Product change event
    $(document).on('change', '.product', function() {
        var row = $(this).closest('tr');
        var productId = $(this).val();
        
        if(productId) {
            $.ajax({
                url: 'php_action/getProductDetails.php',
                type: 'post',
                data: {productId: productId},
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        // Set default price (editable)
                        row.find('.price').val(response.price);
                        
                        // Set available quantity
                        row.find('[id^="available"]').text(response.quantity);
                        
                        // Set purchased quantity
                        row.find('[id^="purchased"]').text(response.purchased || 0);
                        
                        // Set max quantity
                        row.find('.quantity').attr('max', response.quantity);
                        
                        // Clear quantity and total
                        row.find('.quantity').val('');
                        row.find('.total').val('');
                    }
                },
                error: function() {
                    alert('Error fetching product details');
                }
            });
        } else {
            row.find('.price').val('');
            row.find('[id^="available"]').text('');
            row.find('[id^="purchased"]').text('0');
            row.find('.quantity').val('').attr('max', '');
            row.find('.total').val('');
        }
    });

    // Price or quantity change event
    $(document).on('input', '.price, .quantity', function() {
        calculateTotal($(this).closest('tr'));
        calculateGrandTotal();
    });

    // Add new row
    var rowCount = 1;
    $('#addRow').on('click', function() {
        rowCount++;
        var newRow = $('#row1').clone();
        
        // Update IDs and clear values
        newRow.attr('id', 'row' + rowCount);
        newRow.find('select').attr('id', 'productName' + rowCount).val('');
        newRow.find('.price').attr('id', 'price' + rowCount).val('');
        newRow.find('[id^="available"]').attr('id', 'available' + rowCount).text('');
        newRow.find('[id^="purchased"]').attr('id', 'purchased' + rowCount).text('0');
        newRow.find('.quantity').attr('id', 'quantity' + rowCount).val('');
        newRow.find('.total').attr('id', 'total' + rowCount).val('');
        newRow.find('button').attr('onclick', 'removeRow(' + rowCount + ')');
        
        $('#productTable tbody').append(newRow);
    });

    // Form submit handler
    $('#addOrderForm').on('submit', function(e) {
        e.preventDefault();
        
        // Validate form
        var valid = true;
        $('.product').each(function() {
            if(!$(this).val()) {
                valid = false;
                return false;
            }
        });
        
        if(!valid) {
            alert('Please select all products');
            return;
        }
        
        // Submit form
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    alert('Order created successfully');
                    window.location.href = 'orders.php?o=manord';
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error creating order');
            }
        });
    });
});

// Calculate row total
function calculateTotal(row) {
    var price = parseFloat(row.find('.price').val()) || 0;
    var quantity = parseInt(row.find('.quantity').val()) || 0;
    row.find('.total').val((price * quantity).toFixed(2));
}

// Calculate grand total
function calculateGrandTotal() {
    var subTotal = 0;
    $('.total').each(function() {
        subTotal += parseFloat($(this).val()) || 0;
    });
    
    var vat = subTotal * 0.15;
    var grandTotal = subTotal + vat;
    
    $('#subTotal').val(subTotal.toFixed(2));
    $('#vat').val(vat.toFixed(2));
    $('#grandTotal').val(grandTotal.toFixed(2));
}

// Remove row
function removeRow(rowNum) {
    if($('#productTable tbody tr').length > 1) {
        $('#row' + rowNum).remove();
        calculateGrandTotal();
    } else {
        alert('At least one product must be selected.');
    }
} 