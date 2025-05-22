var gpsOrdersTable;

$(document).ready(function() {
    // Debug log
    console.log('Initializing GPS Orders DataTable');

    gpsOrdersTable = $('#gpsOrdersTable').DataTable({
        'ajax': {
            'url': 'php_action/fetchGpsOrders.php',
            'type': 'POST'
        },
        'columns': [
            { data: 'order_number' },
            { data: 'order_date' },
            { 
                data: 'unit_price',
                render: function(data) {
                    return 'ETB ' + parseFloat(data).toFixed(2);
                }
            },
            { data: 'quantity' },
            { 
                data: 'total_price',
                render: function(data, type, row) {
                    return 'ETB ' + (parseFloat(row.unit_price) * parseInt(row.quantity)).toFixed(2);
                }
            },
            { 
                data: 'has_credit',
                render: function(data, type, row) {
                    // Convert to integer and check value
                    return parseInt(data) === 1 ? 
                        '<span class="label label-success">Yes</span>' : 
                        '<span class="label label-default">No</span>';
                }
            },
            { 
                data: 'credit_amount',
                render: function(data, type, row) {
                    // Show credit amount only if has_credit is 1
                    return parseInt(row.has_credit) === 1 && parseFloat(data) > 0 ? 
                        'ETB ' + parseFloat(data).toFixed(2) : '-';
                }
            },
            { 
                data: 'id',
                render: function(data) {
                    return '<div class="btn-group">' +
                        '<button type="button" class="btn btn-primary btn-sm edit-order" data-id="' + data + '"><i class="fa fa-edit"></i></button>' +
                        '<button type="button" class="btn btn-danger btn-sm delete-order" data-id="' + data + '"><i class="fa fa-trash"></i></button>' +
                        '</div>';
                }
            }
        ],
        'order': [[1, 'desc']]
    });

    // Calculate total price for add form
    function calculateTotal() {
        var unitPrice = parseFloat($('#unitPrice').val()) || 0;
        var quantity = parseInt($('#quantity').val()) || 0;
        var total = unitPrice * quantity;
        $('#totalPrice').val(total.toFixed(2));
    }

    // Event listeners for add form
    $('#unitPrice, #quantity').on('input', calculateTotal);

    // Handle form submission for new order
    $('#submitGpsOrderForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: 'php_action/createGpsOrder.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#addGpsOrderModal').modal('hide');
                    $('#submitGpsOrderForm')[0].reset();
                    gpsOrdersTable.ajax.reload();
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
                console.error(xhr.responseText);
                Swal.fire({
                    title: 'Error',
                    text: 'An error occurred while processing your request.',
                    icon: 'error'
                });
            }
        });
    });

    // Calculate total price for edit form
    function calculateEditTotal() {
        var unitPrice = parseFloat($('#editUnitPrice').val()) || 0;
        var quantity = parseInt($('#editQuantity').val()) || 0;
        var total = unitPrice * quantity;
        $('#editTotalPrice').val(total.toFixed(2));
    }

    // Event listeners for edit form
    $('#editUnitPrice, #editQuantity').on('input', calculateEditTotal);

    // Handle credit status change in add form
    $('#hasCredit').on('change', function() {
        if ($(this).val() === '1') {
            $('#creditAmountGroup').show();
            $('#creditAmount').prop('required', true);
        } else {
            $('#creditAmountGroup').hide();
            $('#creditAmount').prop('required', false);
            $('#creditAmount').val('');
        }
    });

    // Handle edit order
    $(document).on('click', '.edit-order', function() {
        var orderId = $(this).data('id');
        
        $.ajax({
            url: 'php_action/fetchSelectedOrder.php',
            type: 'POST',
            data: {orderId: orderId},
            dataType: 'json',
            success: function(response) {
                if(response.success && response.data) {
                    $('#editOrderNumber').val(response.data.order_number);
                    $('#editOrderDate').val(response.data.order_date);
                    $('#editUnitPrice').val(response.data.unit_price);
                    $('#editQuantity').val(response.data.quantity);
                    $('#editTotalPrice').val(response.data.total_price);
                    $('#editHasCredit').val(response.data.has_credit);
                    $('#editCreditAmount').val(response.data.credit_amount);
                    $('#orderId').val(response.data.id);
                    
                    // Show/hide credit amount field based on has_credit value
                    if(parseInt(response.data.has_credit) === 1) {
                        $('#editCreditAmountGroup').show();
                        $('#editCreditAmount').prop('required', true);
                    } else {
                        $('#editCreditAmountGroup').hide();
                        $('#editCreditAmount').prop('required', false);
                        $('#editCreditAmount').val('');
                    }
                    
                    $('#editGpsOrderModal').modal('show');
                    calculateEditTotal();
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: response.messages || 'Could not fetch order details.',
                        icon: 'error'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
                Swal.fire({
                    title: 'Error',
                    text: 'Could not fetch order details.',
                    icon: 'error'
                });
            }
        });
    });

    // Handle edit form submission
    $('#editGpsOrderForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: 'php_action/editGpsOrder.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#editGpsOrderModal').modal('hide');
                    gpsOrdersTable.ajax.reload();
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
                console.error(xhr.responseText);
                Swal.fire({
                    title: 'Error',
                    text: 'An error occurred while processing your request.',
                    icon: 'error'
                });
            }
        });
    });

    // Handle delete order
    $(document).on('click', '.delete-order', function() {
        var orderId = $(this).data('id');
        
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
                    url: 'php_action/removeGpsOrder.php',
                    type: 'POST',
                    data: {orderId: orderId},
                    dataType: 'json',
                    success: function(response) {
                        if(response.success) {
                            gpsOrdersTable.ajax.reload();
                            Swal.fire(
                                'Deleted!',
                                response.messages,
                                'success'
                            );
                        } else {
                            Swal.fire(
                                'Error!',
                                response.messages,
                                'error'
                            );
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                        Swal.fire(
                            'Error!',
                            'Could not delete the order.',
                            'error'
                        );
                    }
                });
            }
        });
    });

    // Handle credit status change in edit form
    $('#editHasCredit').on('change', function() {
        console.log('Edit Has Credit changed:', $(this).val());
        if ($(this).val() === '1') {
            $('#editCreditAmountGroup').show();
            $('#editCreditAmount').prop('required', true);
        } else {
            $('#editCreditAmountGroup').hide();
            $('#editCreditAmount').prop('required', false);
            $('#editCreditAmount').val('');
        }
    });

    // Reset form when modal is closed
    $('.modal').on('hidden.bs.modal', function() {
        $(this).find('form')[0].reset();
        $('#creditAmountGroup, #editCreditAmountGroup').hide();
        $('#creditAmount, #editCreditAmount').prop('required', false);
    });
}); 