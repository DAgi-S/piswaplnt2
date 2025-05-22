var paymentsTable;
var managePaymentTable;

$(document).ready(function() {
    // Debug log
    console.log('Initializing GPS Payments DataTable');

    // Initialize Select2 for dropdowns
    $('.select2').select2();

    // Initialize select2 for order dropdown
    $('select[name="gps_order_id"]').select2({
        ajax: {
            url: 'php_action/fetchAvailableOrders.php',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term
                };
            },
            processResults: function(data) {
                return {
                    results: data.success ? data.data : []
                };
            },
            cache: true
        },
        placeholder: 'Select Order',
        minimumInputLength: 0
    });

    // Initialize the DataTable
    paymentsTable = $('#paymentsTable').DataTable({
        'ajax': {
            'url': 'php_action/fetchGpsPayments.php',
            'type': 'POST',
            'error': function(xhr, error, thrown) {
                console.error('Error loading payment data:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Could not load payment data. Please check the console for details.'
                });
            }
        },
        'order': [[2, 'desc']], // Order by payment date descending
        'columns': [
            { data: 'payment_number' },
            { data: 'order_number' },
            { 
                data: 'payment_date',
                render: function(data) {
                    return moment(data).format('DD/MM/YYYY');
                }
            },
            { data: 'payment_type' },
            { data: 'paid_by' },
            { 
                data: 'paid_amount',
                render: function(data, type, row) {
                    return row.currency + ' ' + parseFloat(data).toFixed(2);
                }
            },
            { data: 'rate' },
            { data: 'bank' },
            { data: 'deposited_to' },
            {
                data: null,
                render: function(data, type, row) {
                    return `
                        <div class="btn-group">
                            <button class="btn btn-primary btn-sm viewPayment" data-id="${row.id}">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-warning btn-sm editPayment" data-id="${row.id}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-danger btn-sm deletePayment" data-id="${row.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>`;
                }
            }
        ],
        'responsive': true,
        'pageLength': 10,
        'dom': 'Bfrtip',
        'buttons': [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    });

    managePaymentTable = $("#managePaymentTable").DataTable({
        "ajax": "php_action/fetchPaymentData.php",
        "order": [],
        "columns": [
            { "data": "payment_number" },
            { "data": "order_number" },
            { "data": "payment_date" },
            { "data": "payment_type" },
            { "data": "investor_name" },
            { 
                "data": "paid_amount",
                "render": function(data, type, row) {
                    return parseFloat(data).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            },
            { "data": "currency" },
            { 
                "data": "rate",
                "render": function(data, type, row) {
                    return parseFloat(data).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            },
            { "data": "bank" },
            { 
                "data": "balance",
                "render": function(data, type, row) {
                    return parseFloat(data).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            },
            {
                "data": null,
                "render": function(data, type, row) {
                    return '<div class="btn-group">' +
                           '<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' +
                           'Action <span class="caret"></span>' +
                           '</button>' +
                           '<ul class="dropdown-menu">' +
                           '<li><a href="#" onclick="editPayment('+ row.id +')"><i class="glyphicon glyphicon-edit"></i> Edit</a></li>' +
                           '<li><a href="#" onclick="viewPayment('+ row.id +')"><i class="glyphicon glyphicon-eye-open"></i> View</a></li>' +
                           '<li><a href="#" onclick="removePayment('+ row.id +')"><i class="glyphicon glyphicon-trash"></i> Remove</a></li>' +
                           '</ul>' +
                           '</div>';
                }
            }
        ]
    });

    // Helper function to format amount with currency
    function formatAmount(amount, currency) {
        return currency === 'ETB' ? 
            'ETB ' + parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) :
            'USD ' + parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    // Show/hide rate field based on currency selection
    $('#currency, #editCurrency').on('change', function() {
        var rateGroup = $(this).attr('id') === 'currency' ? '#rateGroup' : '#editRateGroup';
        if ($(this).val() === 'USD') {
            $(rateGroup).show();
            $(rateGroup + ' input').prop('required', true);
        } else {
            $(rateGroup).hide();
            $(rateGroup + ' input').prop('required', false);
        }
    });

    // Handle Add Payment button click
    $('#addPaymentBtn').on('click', function() {
        $('#createPaymentForm')[0].reset();
        $('#rateGroup').hide();
        $('#addPaymentModal').modal('show');
    });

    // Handle form submission
    $('#addPaymentForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#addPaymentModal').modal('hide');
                    $('#addPaymentForm')[0].reset();
                    $('select[name="gps_order_id"]').val(null).trigger('change');
                    $('#imagePreview').hide();
                    paymentsTable.ajax.reload(null, false);
                    Swal.fire({
                        title: 'Success',
                        text: response.messages,
                        icon: 'success'
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: response.messages,
                        icon: 'error'
                    });
                }
            },
            error: function(xhr, status, error) {
                var errorMessage = xhr.responseJSON ? xhr.responseJSON.messages : 'An error occurred while processing your request.';
                Swal.fire({
                    title: 'Error',
                    text: errorMessage,
                    icon: 'error'
                });
            }
        });
    });

    // View Payment Details
    $('#paymentsTable').on('click', '.viewPayment', function() {
        var id = $(this).data('id');
        $.ajax({
            url: 'php_action/fetchGpsPaymentDetails.php',
            type: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    var payment = response.data;
                    $('#viewPaymentNumber').text(payment.payment_number);
                    $('#viewOrderNumber').text(payment.order_number || payment.gps_order_id);
                    $('#viewPaymentDate').text(moment(payment.payment_date).format('DD/MM/YYYY'));
                    $('#viewPaymentType').text(payment.payment_type);
                    $('#viewPaidBy').text(payment.paid_by);
                    $('#viewAmount').text(formatAmount(payment.paid_amount, payment.currency));
                    $('#viewCurrency').text(payment.currency);
                    $('#viewRate').text(payment.rate || 'N/A');
                    $('#viewBank').text(payment.bank || 'N/A');
                    $('#viewDepositedTo').text(payment.deposited_to || 'N/A');
                    
                    // Handle image preview with correct path
                    if (payment.image_location) {
                        var imageUrl = payment.image_location.startsWith('http') ? 
                            payment.image_location : 
                            'uploads/payments/' + payment.image_location.split('/').pop();
                        
                        console.log('Image URL:', imageUrl); // Debug log
                        
                        $('#viewImagePreview').html(
                            '<div style="margin-top:5px;">' +
                            '<img src="' + imageUrl + '" class="payment-image-preview" ' +
                            'style="width:50px; height:50px; object-fit:cover; cursor:pointer; border-radius:4px;" ' +
                            'onclick="showImagePopup(\'' + imageUrl + '\')" ' +
                            'title="Click to view full image">' +
                            '</div>'
                        );
                    } else {
                        $('#viewImagePreview').html('<p class="text-muted">No image available</p>');
                    }
                    
                    $('#viewPaymentModal').modal('show');
                } else {
                    showErrorMessage(response.messages || 'Could not load payment details');
                }
            },
            error: function(xhr, status, error) {
                console.error(error);
                showErrorMessage('Could not load payment details');
            }
        });
    });

    // Load Payment Data for Edit
    $('#paymentsTable').on('click', '.editPayment', function() {
        var id = $(this).data('id');
        console.log('Editing payment ID:', id);
        
        $.ajax({
            url: 'php_action/fetchGpsPaymentDetails.php',
            type: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                console.log('Payment details response:', response);
                if(response.success) {
                    var payment = response.data;
                    
                    // Populate form fields
                    $('#editPaymentId').val(payment.id);
                    $('#editPaymentNumber').val(payment.payment_number);
                    $('#editGpsOrderId').val(payment.gps_order_id).trigger('change');
                    $('#editPaymentDate').val(payment.payment_date);
                    $('#editPaymentType').val(payment.payment_type);
                    $('#editPaidBy').val(payment.paid_by);
                    $('#editPaidAmount').val(payment.paid_amount);
                    $('#editCurrency').val(payment.currency).trigger('change');
                    $('#editRate').val(payment.rate);
                    $('#editBank').val(payment.bank);
                    $('#editDepositedTo').val(payment.deposited_to);
                    $('#editOldImageLocation').val(payment.image_location);
                    
                    // Show/hide rate field based on currency
                    if(payment.currency === 'USD') {
                        $('#editRateGroup').show();
                    } else {
                        $('#editRateGroup').hide();
                    }
                    
                    // Handle image preview
                    if (payment.image_location) {
                        var imageUrl = payment.image_location.startsWith('http') ? 
                            payment.image_location : 
                            'gpsBusiness/' + payment.image_location;
                        
                        $('#currentImageDisplay').html(
                            '<div class="well well-sm" style="margin-top:10px;">' +
                            '<p>Current Image:</p>' +
                            '<a href="' + imageUrl + '" target="_blank">' +
                            '<img src="' + imageUrl + '" class="payment-image-preview" style="max-width:100%; max-height:200px;">' +
                            '</a></div>'
                        ).show();
                        $('#editImagePreview').hide();
                    } else {
                        $('#currentImageDisplay').empty();
                        $('#editImagePreview').hide();
                    }
                    
                    $('#editPaymentModal').modal('show');
                } else {
                    showErrorMessage(response.messages || 'Could not load payment details');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching payment details:', error);
                showErrorMessage('Could not load payment details');
            }
        });
    });

    // Handle Edit Payment Form Submit
    $('#editPaymentForm').on('submit', function(e) {
        e.preventDefault();
        console.log('Submitting edit form');
        
        var formData = new FormData(this);
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Edit response:', response);
                if (typeof response === 'string') {
                    response = JSON.parse(response);
                }
                
                if (response.success) {
                    $('#editPaymentModal').modal('hide');
                    $('#editPaymentForm')[0].reset();
                    $('#editImagePreview').hide();
                    $('#currentImageDisplay').empty();
                    paymentsTable.ajax.reload();
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.messages
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.messages
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Error updating payment:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Could not update payment'
                });
            }
        });
    });

    // Handle Delete Payment
    $('#paymentsTable').on('click', '.deletePayment', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'php_action/deleteGpsPayment.php',
                    type: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            paymentsTable.ajax.reload();
                            Swal.fire(
                                'Deleted!',
                                response.message,
                                'success'
                            );
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Could not delete payment.'
                        });
                    }
                });
            }
        });
    });

    // Reset form when modal is closed
    $('.modal').on('hidden.bs.modal', function() {
        $(this).find('form')[0].reset();
        $('#rateGroup').hide();
    });

    // Load GPS Orders when add modal is shown
    $('#addPaymentModal').on('show.bs.modal', function() {
        $('select[name="gps_order_id"]').val(null).trigger('change');
    });
});

// View Payment Details
function viewPayment(id) {
    $.ajax({
        url: 'php_action/fetchGpsPaymentDetails.php',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                var payment = response.data;
                $('#viewPaymentNumber').text(payment.payment_number);
                $('#viewOrderNumber').text(payment.order_number || payment.gps_order_id);
                $('#viewPaymentDate').text(moment(payment.payment_date).format('DD/MM/YYYY'));
                $('#viewPaymentType').text(payment.payment_type);
                $('#viewPaidBy').text(payment.paid_by);
                $('#viewAmount').text(formatAmount(payment.paid_amount, payment.currency));
                $('#viewCurrency').text(payment.currency);
                $('#viewRate').text(payment.rate || 'N/A');
                $('#viewBank').text(payment.bank || 'N/A');
                $('#viewDepositedTo').text(payment.deposited_to || 'N/A');
                
                // Handle image preview with correct path
                if (payment.image_location) {
                    var imageUrl = payment.image_location.startsWith('http') ? 
                        payment.image_location : 
                        'uploads/payments/' + payment.image_location.split('/').pop();
                    
                    console.log('Image URL:', imageUrl); // Debug log
                    
                    $('#viewImagePreview').html(
                        '<div style="margin-top:5px;">' +
                        '<img src="' + imageUrl + '" class="payment-image-preview" ' +
                        'style="width:50px; height:50px; object-fit:cover; cursor:pointer; border-radius:4px;" ' +
                        'onclick="showImagePopup(\'' + imageUrl + '\')" ' +
                        'title="Click to view full image">' +
                        '</div>'
                    );
                } else {
                    $('#viewImagePreview').html('<p class="text-muted">No image available</p>');
                }
                
                $('#viewPaymentModal').modal('show');
            } else {
                showErrorMessage(response.messages || 'Could not load payment details');
            }
        },
        error: function(xhr, status, error) {
            console.error(error);
            showErrorMessage('Could not load payment details');
        }
    });
}

// Edit Payment
function editPayment(id) {
    $.ajax({
        url: 'php_action/fetchGpsPaymentDetails.php',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                var payment = response.data;
                
                // Populate form fields
                $('#editPaymentId').val(payment.id);
                $('#editPaymentNumber').val(payment.payment_number);
                $('#editGpsOrderId').val(payment.gps_order_id).trigger('change');
                $('#editPaymentDate').val(payment.payment_date);
                $('#editPaymentType').val(payment.payment_type);
                $('#editPaidBy').val(payment.paid_by);
                $('#editPaidAmount').val(payment.paid_amount);
                $('#editCurrency').val(payment.currency).trigger('change');
                $('#editRate').val(payment.rate);
                $('#editBank').val(payment.bank);
                $('#editDepositedTo').val(payment.deposited_to);
                $('#editOldImageLocation').val(payment.image_location);
                
                // Show/hide rate field based on currency
                if(payment.currency === 'USD') {
                    $('#editRateGroup').show();
                } else {
                    $('#editRateGroup').hide();
                }
                
                // Handle image preview
                if (payment.image_location) {
                    var imageUrl = payment.image_location.startsWith('http') ? 
                        payment.image_location : 
                        'gpsBusiness/' + payment.image_location;
                    
                    $('#currentImageDisplay').html(
                        '<div class="well well-sm" style="margin-top:10px;">' +
                        '<p>Current Image:</p>' +
                        '<a href="' + imageUrl + '" target="_blank">' +
                        '<img src="' + imageUrl + '" class="payment-image-preview" style="max-width:100%; max-height:200px;">' +
                        '</a></div>'
                    ).show();
                    $('#editImagePreview').hide();
                } else {
                    $('#currentImageDisplay').empty();
                    $('#editImagePreview').hide();
                }
                
                $('#editPaymentModal').modal('show');
            } else {
                showErrorMessage(response.messages || 'Could not load payment details');
            }
        },
        error: function(xhr, status, error) {
            console.error(error);
            showErrorMessage('Could not load payment details');
        }
    });
}

// Delete Payment
function deletePayment(paymentId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'php_action/deleteGpsPayment.php',
                type: 'POST',
                data: { payment_id: paymentId },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        paymentsTable.ajax.reload();
                        Swal.fire(
                            'Deleted!',
                            response.messages,
                            'success'
                        );
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.messages
                        });
                    }
                }
            });
        }
    });
}

// Function for image popup (moved to global scope)
window.showImagePopup = function(imageUrl) {
    console.log('Opening image popup:', imageUrl);
    Swal.fire({
        imageUrl: imageUrl,
        imageAlt: 'Payment Image',
        width: '80%',
        padding: '10px',
        showConfirmButton: false,
        showCloseButton: true,
        customClass: {
            image: 'img-fluid',
            popup: 'payment-image-popup'
        }
    });
};

// Add custom styles for the image popup
$(document).ready(function() {
    $('<style>')
        .text(`
            .payment-image-popup {
                max-width: 90vw !important;
            }
            .payment-image-popup img {
                max-height: 80vh;
                object-fit: contain;
            }
            .payment-image-preview {
                transition: transform 0.2s;
                border: 1px solid #ddd;
            }
            .payment-image-preview:hover {
                transform: scale(1.1);
                border-color: #007bff;
            }
        `)
        .appendTo('head');

    // Rest of your document ready code...
});

// On payment form submit
$("#submitPaymentForm").on('submit', function(e) {
    e.preventDefault();
    
    var form = $(this);
    var formData = new FormData(this);

    $.ajax({
        url: form.attr('action'),
        type: form.attr('method'),
        data: formData,
        dataType: 'json',
        cache: false,
        contentType: false,
        processData: false,
        success: function(response) {
            if (response.success == true) {
                // Update balance after payment is saved
                $.ajax({
                    url: 'php_action/updateBalanceOnPayment.php',
                    type: 'POST',
                    data: { payment_id: response.payment_id },
                    dataType: 'json',
                    success: function(balanceResponse) {
                        if (balanceResponse.status) {
                            // Show success message
                            $('.remove-messages').html('<div class="alert alert-success">' +
                                '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                                '<strong><i class="glyphicon glyphicon-ok-sign"></i></strong> ' + response.messages +
                                ' Balance updated successfully.</div>');

                            // Reset the form
                            $("#submitPaymentForm")[0].reset();
                            // Close modal
                            $("#addPaymentModal").modal('hide');
                            // Reload table
                            managePaymentTable.ajax.reload(null, false);
                        } else {
                            $('.remove-messages').html('<div class="alert alert-danger">' +
                                '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                                '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> ' + 
                                balanceResponse.message + '</div>');
                        }
                    },
                    error: function() {
                        $('.remove-messages').html('<div class="alert alert-danger">' +
                            '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                            '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> ' + 
                            'Error updating balance.</div>');
                    }
                });
            }
        }
    });
}); 