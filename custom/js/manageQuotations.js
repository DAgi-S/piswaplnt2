var manageQuotationsTable;
var currentQuotationId;

$(document).ready(function() {
    // Initialize DataTable
    manageQuotationsTable = $('#manageQuotationsTable').DataTable({
        'ajax': 'php_action/fetchQuotations.php',
        'order': [],
        'pageLength': 10,
        'responsive': true,
        'dom': '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
               '<"row"<"col-sm-12"tr>>' +
               '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        'buttons': [
            'copy', 'csv', 'print'
        ],
        'columns': [
            { data: 'quotation_number' },
            { data: 'client_name' },
            { data: 'created_at' },
            { 
                data: 'sub_total',
                render: function(data) {
                    return parseFloat(data).toFixed(2) + ' ETB';
                }
            },
            { 
                data: 'vat_amount',
                render: function(data) {
                    return parseFloat(data).toFixed(2) + ' ETB';
                }
            },
            { 
                data: 'grand_total',
                render: function(data) {
                    return parseFloat(data).toFixed(2) + ' ETB';
                }
            },
            { 
                data: 'status',
                render: function(data) {
                    let badgeClass = '';
                    switch(data.toLowerCase()) {
                        case 'active':
                            badgeClass = 'badge-active';
                            break;
                        case 'pending':
                            badgeClass = 'badge-pending';
                            break;
                        case 'cancelled':
                            badgeClass = 'badge-cancelled';
                            break;
                        default:
                            badgeClass = 'badge-default';
                    }
                    return '<span class="badge ' + badgeClass + '">' + data + '</span>';
                }
            },
            {
                data: null,
                render: function(data) {
                    let buttons = '<div class="btn-group">';
                    buttons += '<button class="btn btn-default" onclick="viewQuotation(' + data.id + ')"><i class="glyphicon glyphicon-eye-open"></i></button>';
                    buttons += '<button class="btn btn-default" onclick="editQuotation(' + data.id + ')"><i class="glyphicon glyphicon-edit"></i></button>';
                    buttons += '<button class="btn btn-default" onclick="emailQuotation(' + data.id + ')"><i class="glyphicon glyphicon-envelope"></i></button>';
                    buttons += '<button class="btn btn-default" onclick="removeQuotation(' + data.id + ')"><i class="glyphicon glyphicon-trash"></i></button>';
                    buttons += '</div>';
                    return buttons;
                }
            }
        ]
    });

    // Handle email form submission
    $("#emailQuotationForm").on('submit', function(e) {
        if (e.isDefaultPrevented()) {
            // Handle validation errors
            $('#email-messages').html(
                '<div class="alert alert-warning">' +
                '<i class="glyphicon glyphicon-warning-sign"></i> ' +
                'Please fill in all required fields correctly</div>'
            );
            return false;
        }
        
        e.preventDefault();
        
        var form = $(this);
        var btn = $('#sendEmailBtn');
        
        // Show loading state
        btn.prop('disabled', true)
           .html('<i class="glyphicon glyphicon-refresh spinning"></i> Sending...');
        
        // Clear previous messages
        $('#email-messages').empty();

        // Get form data
        var formData = {
            quotationId: $('#quotationId').val(),
            emailTo: $('#emailTo').val().trim(),
            emailSubject: $('#emailSubject').val().trim(),
            emailMessage: $('#emailMessage').val().trim()
        };

        // Additional validation
        if (!formData.emailTo || !formData.emailSubject) {
            $('#email-messages').html(
                '<div class="alert alert-warning">' +
                '<i class="glyphicon glyphicon-warning-sign"></i> ' +
                'Email and Subject are required</div>'
            );
            btn.prop('disabled', false).html('Send Email');
            return false;
        }

        $.ajax({
            url: 'php_action/sendQuotationEmail.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#email-messages').html(
                        '<div class="alert alert-success">' +
                        '<i class="glyphicon glyphicon-ok"></i> ' +
                        response.messages + '</div>'
                    );
                    // Close modal after successful send
                    setTimeout(function() {
                        $('#emailQuotationModal').modal('hide');
                    }, 2000);
                } else {
                    $('#email-messages').html(
                        '<div class="alert alert-danger">' +
                        '<i class="glyphicon glyphicon-remove"></i> ' +
                        (response.messages || 'Error sending email') + '</div>'
                    );
                }
            },
            error: function(xhr, status, error) {
                console.error('XHR Response:', xhr.responseText);
                let errorMessage = 'Error sending email';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.messages) {
                        errorMessage = response.messages;
                    }
                } catch (e) {
                    errorMessage = 'Error sending email: ' + error;
                }
                $('#email-messages').html(
                    '<div class="alert alert-danger">' +
                    '<i class="glyphicon glyphicon-remove"></i> ' +
                    errorMessage + '</div>'
                );
            },
            complete: function() {
                btn.prop('disabled', false).html('Send Email');
            }
        });
    });
});

function viewQuotation(id) {
    if (!id) return;
    
    currentQuotationId = id;
    
    // Show modal with loading state
    $('#viewQuotationModal').modal('show');
    $('#viewQuotationModal .modal-body').html(`
        <div class="text-center">
            <i class="glyphicon glyphicon-refresh spinning"></i> Loading quotation details...
        </div>
    `);

    // Fetch quotation details
    $.ajax({
        url: 'php_action/fetchQuotationDetails.php',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            console.log('Response:', response); // Debug log
            
            if(response.success && response.data) {
                const data = response.data;
                
                // Ensure all required data exists
                if (!data.quotation_number) {
                    throw new Error('Invalid quotation data received');
                }
                
                // Populate the modal content
                $('#viewQuotationModal .modal-body').html(`
                    <div class="row">
                        <div class="col-md-6">
                            <div class="panel panel-primary">
                                <div class="panel-heading">
                                    <h4 class="panel-title">Quotation Information</h4>
                                </div>
                                <div class="panel-body">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="40%">Quotation Number</th>
                                            <td>${data.quotation_number}</td>
                                        </tr>
                                        <tr>
                                            <th>Date Created</th>
                                            <td>${data.created_at}</td>
                                        </tr>
                                        <tr>
                                            <th>Created By</th>
                                            <td>${data.created_by || '-'}</td>
                                        </tr>
                                        <tr>
                                            <th>Status</th>
                                            <td><span class="badge ${getBadgeClass(data.status)}">${data.status || 'Unknown'}</span></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="panel panel-info">
                                <div class="panel-heading">
                                    <h4 class="panel-title">Client Information</h4>
                                </div>
                                <div class="panel-body">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="40%">Company Name</th>
                                            <td>${data.client_name || '-'}</td>
                                        </tr>
                                        <tr>
                                            <th>TIN Number</th>
                                            <td>${data.tin_number || '-'}</td>
                                        </tr>
                                        <tr>
                                            <th>Phone</th>
                                            <td>${data.client_phone || '-'}</td>
                                        </tr>
                                        <tr>
                                            <th>Email</th>
                                            <td>${data.client_email || '-'}</td>
                                        </tr>
                                        <tr>
                                            <th>Address</th>
                                            <td>${data.client_address || '-'}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h4 class="panel-title">Quotation Items</h4>
                                </div>
                                <div class="panel-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th style="width: 5%;">#</th>
                                                    <th style="width: 30%;">Product</th>
                                                    <th style="width: 25%;">Description</th>
                                                    <th style="width: 10%;">Quantity</th>
                                                    <th style="width: 15%;">Unit Price</th>
                                                    <th style="width: 15%;">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${generateItemsRows(data.items)}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h4 class="panel-title">Notes</h4>
                                </div>
                                <div class="panel-body">
                                    <div class="well well-sm" style="margin-bottom: 0;">
                                        ${data.notes || 'No notes available'}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="panel panel-success">
                                <div class="panel-heading">
                                    <h4 class="panel-title">Financial Summary</h4>
                                </div>
                                <div class="panel-body">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="40%">Sub Total</th>
                                            <td class="text-right">${formatCurrency(data.sub_total)}</td>
                                        </tr>
                                        <tr>
                                            <th>VAT Amount</th>
                                            <td class="text-right">${formatCurrency(data.vat_amount)}</td>
                                        </tr>
                                        <tr class="success">
                                            <th>Grand Total</th>
                                            <td class="text-right"><strong>${formatCurrency(data.grand_total)}</strong></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                `);

                // Enable action buttons
                $('#printQuotationBtn').prop('disabled', false)
                    .attr('onclick', `printQuotation(${data.id})`);
                $('#emailQuotationBtn').prop('disabled', false)
                    .attr('onclick', `emailQuotation(${data.id})`);
            } else {
                $('#viewQuotationModal .modal-body').html(`
                    <div class="alert alert-danger">
                        <i class="glyphicon glyphicon-warning-sign"></i> 
                        ${response.messages || 'Error loading quotation details'}
                    </div>
                `);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error details:', {
                xhr: xhr.responseText,
                status: status,
                error: error
            });
            $('#viewQuotationModal .modal-body').html(`
                <div class="alert alert-danger">
                    <i class="glyphicon glyphicon-warning-sign"></i> 
                    Error: ${error}
                </div>
            `);
        }
    });
}

// Helper function to get badge class based on status
function getBadgeClass(status) {
    if (!status) return 'badge-secondary';
    
    status = String(status).toLowerCase().trim();
    switch(status) {
        case 'active':
            return 'badge-success';
        case 'pending':
            return 'badge-warning';
        case 'cancelled':
            return 'badge-danger';
        default:
            return 'badge-secondary';
    }
}

// Helper function to generate items table rows
function generateItemsRows(items) {
    if (!items || !Array.isArray(items) || items.length === 0) {
        return '<tr><td colspan="6" class="text-center">No items found</td></tr>';
    }
    
    return items.map((item, index) => `
        <tr>
            <td>${index + 1}</td>
            <td>${item.product_name || '-'}</td>
            <td>${item.description || '-'}</td>
            <td>${item.quantity || 0}</td>
            <td class="text-right">${formatCurrency(item.unit_price)}</td>
            <td class="text-right">${formatCurrency(item.total)}</td>
        </tr>
    `).join('');
}

// Helper function to format currency
function formatCurrency(amount) {
    if (!amount) return '0.00 ETB';
    return parseFloat(amount).toFixed(2) + ' ETB';
}

function printQuotation(id) {
    window.open('php_action/printQuotation.php?id=' + id, '_blank');
}

function emailQuotation(id) {
    // Fetch quotation details first
    $.ajax({
        url: 'php_action/fetchSelectedQuotation.php',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                // Reset form and messages
                $('#emailQuotationForm')[0].reset();
                $('#email-messages').empty();
                
                // Set the quotation ID and default subject
                $('#quotationId').val(id);
                $('#emailSubject').val('Quotation #' + response.quotation_number);
                
                // If client email exists, set it
                if(response.client_email) {
                    $('#emailTo').val(response.client_email);
                }
                
                // Show modal
                $('#emailQuotationModal').modal('show');
            } else {
                alert('Error fetching quotation details');
            }
        },
        error: function(xhr, status, error) {
            alert('Error fetching quotation details: ' + error);
        }
    });
}

function removeQuotation(id) {
    if(confirm('Are you sure you want to remove this quotation?')) {
        $.ajax({
            url: 'php_action/removeQuotation.php',
            type: 'post',
            data: {id: id},
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    manageQuotationsTable.ajax.reload(null, false);
                }
            }
        });
    }
}

function editQuotation(id) {
    if (!id) return;

    // Show modal and reset previous state
    $('#editQuotationModal').modal('show');
    $('#edit-quotation-messages').empty();
    $('.modal-body').html(`
        <div class="text-center">
            <i class="glyphicon glyphicon-refresh spinning"></i> Loading quotation details...
        </div>
    `);

    // Fetch quotation details
    $.ajax({
        url: 'php_action/fetchQuotationDetails.php',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if(response.success && response.data) {
                const data = response.data;
                
                // Build the edit form HTML
                const formHtml = `
                    <form id="editQuotationForm">
                        <input type="hidden" name="quotationId" value="${id}">
                        
                        <div class="form-group">
                            <label>Quotation Number</label>
                            <input type="text" class="form-control" value="${data.quotation_number}" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label>Client</label>
                            <input type="hidden" name="clientId" value="${data.client_id}">
                            <p class="form-control-static">
                                <strong>${data.client_name}</strong><br>
                                TIN: ${data.tin_number || 'N/A'}<br>
                                Phone: ${data.client_phone || 'N/A'}
                            </p>
                        </div>
                        
                        <div class="form-group">
                            <label>Items</label>
                            <table class="table table-bordered" id="editItemsTable">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Description</th>
                                        <th>Quantity</th>
                                        <th>Unit Price</th>
                                        <th>Total</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.items.map((item, index) => `
                                        <tr>
                                            <td>
                                                <select name="productId[]" class="form-control product-select" data-row="${index + 1}">
                                                    <option value="${item.product_id}">${item.product_name}</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="description[]" class="form-control" value="${item.description || ''}">
                                            </td>
                                            <td>
                                                <input type="number" name="quantity[]" class="form-control quantity" value="${item.quantity}" min="1" required data-row="${index + 1}">
                                            </td>
                                            <td>
                                                <input type="number" name="price[]" class="form-control price" value="${item.unit_price}" step="0.01" required data-row="${index + 1}">
                                            </td>
                                            <td>
                                                <input type="number" name="total[]" class="form-control total" value="${item.total}" readonly data-row="${index + 1}">
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-danger btn-sm remove-item" data-row="${index + 1}">
                                                    <i class="glyphicon glyphicon-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                            <button type="button" class="btn btn-info btn-sm" id="addEditItem">
                                <i class="glyphicon glyphicon-plus"></i> Add Item
                            </button>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Notes</label>
                                    <textarea name="notes" class="form-control" rows="3">${data.notes || ''}</textarea>
                                </div>
                                <div class="form-group">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="withholding" id="editWithholding" ${data.withholding ? 'checked' : ''}> Apply Withholding Tax (2%)
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Sub Total</label>
                                    <input type="number" name="subTotal" class="form-control" value="${data.sub_total}" readonly>
                                </div>
                                <div class="form-group">
                                    <label>VAT Amount (15%)</label>
                                    <input type="number" name="vatAmount" class="form-control" value="${data.vat_amount}" readonly>
                                </div>
                                <div class="form-group withholding-group" style="display: ${data.withholding ? 'block' : 'none'}">
                                    <label>Withholding Amount (2%)</label>
                                    <input type="number" name="withholdingAmount" class="form-control" value="${data.withholding_amount || 0}" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Grand Total</label>
                                    <input type="number" name="grandTotal" class="form-control" value="${data.grand_total}" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group text-right">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Update Quotation</button>
                        </div>
                    </form>
                `;
                
                // Update modal content
                $('.modal-body').html(formHtml);
                
                // Initialize product dropdowns
                $('.product-select').each(function() {
                    $(this).on('change', function() {
                        const row = $(this).data('row');
                        const selectedOption = $(this).find('option:selected');
                        const price = selectedOption.data('price') || 0;
                        
                        $(`input[name="price[]"][data-row="${row}"]`).val(price);
                        calculateRowTotal(row);
                    });
                });
                
                // Bind events
                bindEditFormEvents();
                
            } else {
                $('.modal-body').html(`
                    <div class="alert alert-danger">
                        <i class="glyphicon glyphicon-warning-sign"></i> 
                        ${response.messages || 'Error loading quotation details'}
                    </div>
                `);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error details:', {
                xhr: xhr.responseText,
                status: status,
                error: error
            });
            $('.modal-body').html(`
                <div class="alert alert-danger">
                    <i class="glyphicon glyphicon-warning-sign"></i> 
                    Error: ${error}
                </div>
            `);
        }
    });
}

function bindEditFormEvents() {
    let rowCount = $('#editItemsTable tbody tr').length;

    // Add new item row
    $('#addEditItem').off('click').on('click', function() {
        rowCount++;
        const newRow = `
            <tr>
                <td>
                    <select name="productId[]" class="form-control product-select" data-row="${rowCount}" required>
                        <option value="">Select Product</option>
                    </select>
                </td>
                <td><input type="text" name="description[]" class="form-control"></td>
                <td><input type="number" name="quantity[]" class="form-control quantity" min="1" required data-row="${rowCount}" value="1"></td>
                <td><input type="number" name="price[]" class="form-control price" step="0.01" required data-row="${rowCount}" value="0.00"></td>
                <td><input type="number" name="total[]" class="form-control total" readonly data-row="${rowCount}" value="0.00"></td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-item" data-row="${rowCount}">
                        <i class="glyphicon glyphicon-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        $('#editItemsTable tbody').append(newRow);
        
        // Initialize product dropdown for new row
        const newSelect = $(`select[data-row="${rowCount}"]`);
        
        // Fetch products for the dropdown
        $.ajax({
            url: 'php_action/fetchProducts.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response && response.length > 0) {
                    let options = '<option value="">Select Product</option>';
                    response.forEach(function(product) {
                        options += `<option value="${product.product_id}" data-price="${product.rate}">${product.product_name}</option>`;
                    });
                    newSelect.html(options);
                }
            }
        });

        // Bind change event for the new product dropdown
        newSelect.on('change', function() {
            const row = $(this).data('row');
            const selectedOption = $(this).find('option:selected');
            const price = selectedOption.data('price') || 0;
            
            $(`input[name="price[]"][data-row="${row}"]`).val(price);
            calculateRowTotal(row);
        });

        // Bind events for new quantity and price inputs
        $(`input[data-row="${rowCount}"]`).on('change', function() {
            calculateRowTotal(rowCount);
        });
    });
    
    // Remove item row
    $(document).off('click', '.remove-item').on('click', '.remove-item', function() {
        if($('#editItemsTable tbody tr').length > 1) {
            $(this).closest('tr').remove();
            calculateTotals();
        }
    });
    
    // Calculate row total on input change
    $(document).off('change', '.quantity, .price').on('change', '.quantity, .price', function() {
        const row = $(this).data('row');
        calculateRowTotal(row);
    });

    // Toggle withholding tax
    $('#editWithholding').off('change').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('.withholding-group').toggle(isChecked);
        calculateTotals();
    });
    
    // Handle form submission
    $('#editQuotationForm').off('submit').on('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="glyphicon glyphicon-refresh spinning"></i> Updating...');
        
        // Prepare form data
        const formData = {
            quotationId: $('input[name="quotationId"]').val(),
            clientId: $('input[name="clientId"]').val(),
            notes: $('textarea[name="notes"]').val(),
            withholding: $('#editWithholding').is(':checked') ? 1 : 0,
            items: [],
            subTotal: $('input[name="subTotal"]').val(),
            vatAmount: $('input[name="vatAmount"]').val(),
            withholdingAmount: $('input[name="withholdingAmount"]').val(),
            grandTotal: $('input[name="grandTotal"]').val()
        };

        // Gather items data
        $('#editItemsTable tbody tr').each(function() {
            const item = {
                productId: $(this).find('select[name="productId[]"]').val(),
                description: $(this).find('input[name="description[]"]').val(),
                quantity: $(this).find('input[name="quantity[]"]').val(),
                price: $(this).find('input[name="price[]"]').val(),
                total: $(this).find('input[name="total[]"]').val()
            };
            formData.items.push(item);
        });

        // Validate required fields
        if (!formData.quotationId || !formData.clientId || formData.items.length === 0) {
            $('#edit-quotation-messages').html(`
                <div class="alert alert-danger">
                    <i class="glyphicon glyphicon-warning-sign"></i> 
                    Please fill in all required fields
                </div>
            `);
            submitBtn.prop('disabled', false).html('Update Quotation');
            return false;
        }

        // Validate items
        for (let item of formData.items) {
            if (!item.productId || !item.quantity || !item.price) {
                $('#edit-quotation-messages').html(`
                    <div class="alert alert-danger">
                        <i class="glyphicon glyphicon-warning-sign"></i> 
                        Please fill in all item details
                    </div>
                `);
                submitBtn.prop('disabled', false).html('Update Quotation');
                return false;
            }
        }
        
        $.ajax({
            url: 'php_action/editQuotation.php',
            type: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#edit-quotation-messages').html(`
                        <div class="alert alert-success">
                            <i class="glyphicon glyphicon-ok"></i> 
                            ${response.messages}
                        </div>
                    `);
                    // Reload the quotations table
                    manageQuotationsTable.ajax.reload(null, false);
                    // Close modal after successful update
                    setTimeout(() => $('#editQuotationModal').modal('hide'), 1500);
                } else {
                    $('#edit-quotation-messages').html(`
                        <div class="alert alert-danger">
                            <i class="glyphicon glyphicon-warning-sign"></i> 
                            ${response.messages || 'Error updating quotation'}
                        </div>
                    `);
                }
            },
            error: function(xhr, status, error) {
                console.error('Update Error:', {
                    xhr: xhr.responseText,
                    status: status,
                    error: error
                });
                $('#edit-quotation-messages').html(`
                    <div class="alert alert-danger">
                        <i class="glyphicon glyphicon-warning-sign"></i> 
                        Error: ${error}
                    </div>
                `);
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('Update Quotation');
            }
        });
    });
}

function calculateRowTotal(row) {
    const quantity = parseFloat($(`input[name="quantity[]"][data-row="${row}"]`).val()) || 0;
    const price = parseFloat($(`input[name="price[]"][data-row="${row}"]`).val()) || 0;
    const total = quantity * price;
    $(`input[name="total[]"][data-row="${row}"]`).val(total.toFixed(2));
    calculateTotals();
}

function calculateTotals() {
    let subTotal = 0;
    
    // Calculate subtotal from all items
    $('#editItemsTable tbody tr').each(function() {
        const total = parseFloat($(this).find('.total').val()) || 0;
        subTotal += total;
    });
    
    // Calculate VAT (15%)
    const vatRate = 0.15;
    const vatAmount = subTotal * vatRate;
    
    // Calculate Withholding (2% if checked)
    let withholdingAmount = 0;
    const isWithholdingChecked = $('#editWithholding').is(':checked');
    
    if (isWithholdingChecked) {
        withholdingAmount = subTotal * 0.02;
        $('.withholding-group').show();
    } else {
        $('.withholding-group').hide();
        withholdingAmount = 0;
    }
    
    // Calculate Grand Total (Subtotal + VAT - Withholding)
    const grandTotal = (subTotal + vatAmount) - withholdingAmount;
    
    // Update all total fields
    $('input[name="subTotal"]').val(subTotal.toFixed(2));
    $('input[name="vatAmount"]').val(vatAmount.toFixed(2));
    $('input[name="withholdingAmount"]').val(withholdingAmount.toFixed(2));
    $('input[name="grandTotal"]').val(grandTotal.toFixed(2));
} 