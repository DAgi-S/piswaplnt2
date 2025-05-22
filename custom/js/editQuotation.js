$(document).ready(function() {
    // Load clients
    $.ajax({
        url: 'php_action/fetchClients.php',
        type: 'get',
        dataType: 'json',
        success: function(response) {
            var clientSelect = $('#clientId');
            $.each(response.data, function(i, client) {
                clientSelect.append($('<option>', {
                    value: client.id,
                    text: client.company_name
                }));
            });

            // Load quotation details
            loadQuotationDetails();
        }
    });

    // Load products
    loadProducts();

    // Handle quantity change
    $(document).on('change', '.product-quantity', function() {
        calculateRowTotal($(this).closest('tr'));
        calculateTotals();
    });

    // Handle product selection
    $(document).on('change', '.product-select', function() {
        var row = $(this).closest('tr');
        var productId = $(this).val();
        
        if(productId) {
            $.ajax({
                url: 'php_action/fetchSelectedProduct.php',
                type: 'post',
                data: {id: productId},
                dataType: 'json',
                success: function(response) {
                    row.find('.product-price').val(response.price);
                    calculateRowTotal(row);
                    calculateTotals();
                }
            });
        }
    });

    // Handle form submission
    $('#editQuotationForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            type: $(this).attr('method'),
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#edit-quotation-messages').html('<div class="alert alert-success">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        response.messages +
                        '</div>');
                    
                    setTimeout(function() {
                        window.location.href = 'manageQuotations.php';
                    }, 1500);
                } else {
                    $('#edit-quotation-messages').html('<div class="alert alert-danger">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        response.messages +
                        '</div>');
                }
            }
        });
    });
});

function loadProducts() {
    $.ajax({
        url: 'php_action/fetchProducts.php',
        type: 'get',
        dataType: 'json',
        success: function(response) {
            var productSelect = $('.product-select');
            $.each(response.data, function(i, product) {
                productSelect.append($('<option>', {
                    value: product.product_id,
                    text: product.name
                }));
            });
        }
    });
}

function loadQuotationDetails() {
    var quotationId = $('#quotationId').val();
    
    $.ajax({
        url: 'php_action/fetchSelectedQuotation.php',
        type: 'post',
        data: {id: quotationId},
        dataType: 'json',
        success: function(response) {
            $('#clientId').val(response.client_id);
            $('#note').val(response.note);

            // Clear existing rows except first
            $('#productTable tbody tr:not(:first)').remove();

            // Load quotation items
            $.each(response.items, function(i, item) {
                if(i > 0) {
                    addRow();
                }
                var row = $('#productTable tbody tr').eq(i);
                row.find('.product-select').val(item.product_id);
                row.find('.product-price').val(item.unit_price);
                row.find('.product-quantity').val(item.quantity);
                row.find('.product-total').val(item.total_price);
            });

            calculateTotals();
        }
    });
}

function addRow() {
    var row = $('#productTable tbody tr:first').clone();
    row.find('input').val('');
    row.find('select').val('');
    $('#productTable tbody').append(row);
}

function removeRow(button) {
    if($('#productTable tbody tr').length > 1) {
        $(button).closest('tr').remove();
        calculateTotals();
    }
}

function calculateRowTotal(row) {
    var price = parseFloat(row.find('.product-price').val()) || 0;
    var quantity = parseInt(row.find('.product-quantity').val()) || 0;
    var total = price * quantity;
    row.find('.product-total').val(total.toFixed(2));
}

function calculateTotals() {
    var subTotal = 0;
    $('.product-total').each(function() {
        subTotal += parseFloat($(this).val()) || 0;
    });

    var vatAmount = subTotal * 0.15;
    var grandTotal = subTotal + vatAmount;

    $('#subTotal').val(subTotal.toFixed(2));
    $('#vatAmount').val(vatAmount.toFixed(2));
    $('#grandTotal').val(grandTotal.toFixed(2));
} 