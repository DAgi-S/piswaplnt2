var manageSaleTable;

// Format date function with fallback
function formatDate(dateStr) {
    if (typeof moment === 'undefined') {
        // Fallback date formatting if moment.js is not available
        var date = new Date(dateStr);
        return date.toLocaleDateString();
    }
    return moment(dateStr).format('DD/MM/YYYY');
}

$(document).ready(function() {
    console.log('Initializing DataTable...');
    
    // Initialize DataTable
    manageSaleTable = $("#manageSaleTable").DataTable({
        'processing': true,
        'serverSide': false,
        'ajax': {
            'url': 'php_action/fetchSaleData.php',
            'type': 'POST',
            'dataSrc': function(json) {
                console.log('Raw response:', json);
                
                // Check if json is valid
                if (typeof json === 'string') {
                    try {
                        json = JSON.parse(json);
                    } catch (e) {
                        console.error('Invalid JSON response:', e);
                        alert('Error: Invalid server response');
                        return [];
                    }
                }
                
                // Check for PHP errors in response
                if (typeof json === 'string' && json.includes('Fatal error')) {
                    console.error('PHP Error:', json);
                    alert('PHP Error detected. Check console for details.');
                    return [];
                }
                
                // Check for error property
                if (json.error) {
                    console.error('Server error:', json.error);
                    alert('Error: ' + json.error);
                    return [];
                }
                
                // Check for data array
                if (!json.data) {
                    console.warn('No data array in response');
                    return [];
                }
                
                console.log('Processed data:', json.data);
                return json.data;
            },
            'error': function(xhr, error, thrown) {
                console.error('AJAX Error:', error);
                console.error('Status:', xhr.status);
                console.error('Response:', xhr.responseText);
                console.error('Error details:', thrown);
                alert('Error loading data. Check console for details.');
            }
        },
        'columns': [
            { "data": "sale_number" },
            { 
                "data": "sale_date",
                "render": function(data) {
                    return formatDate(data);
                }
            },
            { "data": "buyer_name" },
            { "data": "sales_type" },
            { "data": "quantity" },
            { 
                "data": "unit_price",
                "render": function(data, type, row) {
                    return row.currency + ' ' + parseFloat(data).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            },
            { 
                "data": "total",
                "render": function(data, type, row) {
                    return row.currency + ' ' + parseFloat(data).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            },
            { "data": "currency" },
            { 
                "data": "rate",
                "render": function(data) {
                    return parseFloat(data).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            },
            {
                "data": null,
                "orderable": false,
                "render": function(data, type, row) {
                    return '<div class="btn-group">' +
                           '<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">' +
                           'Action <span class="caret"></span>' +
                           '</button>' +
                           '<ul class="dropdown-menu">' +
                           '<li><a href="#" onclick="viewSale('+ row.id +')"><i class="fa fa-eye"></i> View</a></li>' +
                           '<li><a href="#" onclick="editSale('+ row.id +')"><i class="fa fa-edit"></i> Edit</a></li>' +
                           '<li><a href="#" onclick="removeSale('+ row.id +')"><i class="fa fa-trash"></i> Delete</a></li>' +
                           '</ul>' +
                           '</div>';
                }
            }
        ],
        'order': [[1, 'desc']], // Sort by date descending
        'pageLength': 10,
        'responsive': true,
        'language': {
            'processing': '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span>',
            'emptyTable': 'No sales data available'
        },
        'drawCallback': function(settings) {
            console.log('Table drawn with settings:', settings);
        }
    });

    // Auto calculate total
    function calculateTotal(form) {
        var prefix = form.attr('id') === 'submitSaleForm' ? '' : 'edit';
        
        var unitPrice = parseFloat(form.find('#' + prefix + 'UnitPrice').val()) || 0;
        var quantity = parseInt(form.find('#' + prefix + 'Quantity').val()) || 0;
        var rate = parseFloat(form.find('#' + prefix + 'Rate').val()) || 1;
        
        var total = unitPrice * quantity * rate;
        form.find('#' + prefix + 'Total').val(total.toFixed(2));
    }

    // Attach calculation to input changes
    $('#submitSaleForm #unitPrice, #submitSaleForm #quantity, #submitSaleForm #rate').on('input', function() {
        calculateTotal($('#submitSaleForm'));
    });

    $('#editSaleForm #editUnitPrice, #editSaleForm #editQuantity, #editSaleForm #editRate').on('input', function() {
        calculateTotal($('#editSaleForm'));
    });

    // Handle currency change
    $('#currency, #editCurrency').on('change', function() {
        var form = $(this).closest('form');
        var prefix = form.attr('id') === 'submitSaleForm' ? '' : 'edit';
        
        if ($(this).val() === 'USD') {
            form.find('#' + prefix + 'Rate').prop('readonly', false);
        } else {
            form.find('#' + prefix + 'Rate').val('1.00').prop('readonly', true);
        }
        calculateTotal(form);
    });

    // Handle form submission
    $("#submitSaleForm").on('submit', function(e) {
        e.preventDefault();
        console.log('Submitting form...');
        
        var formData = new FormData(this);
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            dataType: 'json',
            cache: false,
            contentType: false,
            processData: false,
            success: function(response) {
                console.log('Form submission response:', response);
                if(response.success) {
                    $('.remove-messages').html('<div class="alert alert-success">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        '<strong><i class="fa fa-check"></i></strong> ' + response.messages + '</div>');

                    $("#submitSaleForm")[0].reset();
                    $("#addSaleModal").modal('hide');
                    manageSaleTable.ajax.reload(null, false);
                } else {
                    $('.remove-messages').html('<div class="alert alert-danger">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        '<strong><i class="fa fa-exclamation-triangle"></i></strong> ' + 
                        response.messages + '</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Form submission error:', error);
                $('.remove-messages').html('<div class="alert alert-danger">' +
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                    '<strong><i class="fa fa-exclamation-triangle"></i></strong> ' + 
                    'Error submitting form. Check console for details.</div>');
            }
        });
    });

    // Handle edit form submission
    $("#editSaleForm").on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            dataType: 'json',
            cache: false,
            contentType: false,
            processData: false,
            success: function(response) {
                if(response.success) {
                    // Show success message
                    $('.remove-messages').html('<div class="alert alert-success">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        '<strong><i class="fa fa-check"></i></strong> ' + response.messages + '</div>');

                    // Close modal and reload table
                    $("#editSaleModal").modal('hide');
                    manageSaleTable.ajax.reload(null, false);
                } else {
                    $('.remove-messages').html('<div class="alert alert-danger">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        '<strong><i class="fa fa-exclamation-triangle"></i></strong> ' + 
                        response.messages + '</div>');
                }
            }
        });
    });
});

// View Sale
function viewSale(id) {
    $.ajax({
        url: 'php_action/fetchSaleDetails.php',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                var sale = response.data;
                
                // Format numbers and handle undefined values
                var formatCurrency = function(value, currency) {
                    if (value === undefined || value === null) return 'N/A';
                    return (currency || 'ETB') + ' ' + parseFloat(value).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                };

                var formatNumber = function(value) {
                    if (value === undefined || value === null) return 'N/A';
                    return parseFloat(value).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                };

                $('#viewSaleNumber').text(sale.sale_number || 'N/A');
                $('#viewSaleDate').text(sale.sale_date ? formatDate(sale.sale_date) : 'N/A');
                $('#viewBuyerName').text(sale.buyer_name || 'N/A');
                $('#viewContact').text(sale.contact || 'N/A');
                $('#viewSalesType').text(sale.sales_type || 'N/A');
                $('#viewUnitPrice').text(formatCurrency(sale.unit_price, sale.currency));
                $('#viewCurrency').text(sale.currency || 'ETB');
                $('#viewRate').text(formatNumber(sale.rate || 1));
                $('#viewQuantity').text(sale.quantity || 'N/A');
                $('#viewTotal').text(formatCurrency(sale.total, sale.currency));
                
                $('#viewSaleModal').modal('show');
            }
        }
    });
}

// Edit Sale
function editSale(id) {
    $.ajax({
        url: 'php_action/fetchSaleDetails.php',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                var sale = response.data;
                $('#editSaleId').val(sale.id);
                $('#editBuyerName').val(sale.buyer_name);
                $('#editContact').val(sale.contact);
                $('#editSalesType').val(sale.sales_type);
                $('#editUnitPrice').val(sale.unit_price);
                $('#editCurrency').val(sale.currency);
                $('#editRate').val(sale.rate);
                $('#editQuantity').val(sale.quantity);
                $('#editTotal').val(sale.total);
                
                $('#editSaleModal').modal('show');
            }
        }
    });
}

// Remove Sale
function removeSale(id) {
    if(confirm('Are you sure you want to delete this sale?')) {
        $.ajax({
            url: 'php_action/removeSale.php',
            type: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    manageSaleTable.ajax.reload(null, false);
                    $('.remove-messages').html('<div class="alert alert-success">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        '<strong><i class="fa fa-check"></i></strong> ' + response.messages + '</div>');
                } else {
                    $('.remove-messages').html('<div class="alert alert-danger">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        '<strong><i class="fa fa-exclamation-triangle"></i></strong> ' + 
                        response.messages + '</div>');
                }
            }
        });
    }
} 