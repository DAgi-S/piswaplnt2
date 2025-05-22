var gpsPaymentFollowupTable;

$(document).ready(function() {
    // Initialize DataTable
    gpsPaymentFollowupTable = $('#gpsPaymentFollowupTable').DataTable({
        'ajax': {
            'url': 'php_action/fetchGpsPaymentFollowup.php',
            'type': 'GET',
            'error': function(xhr, error, thrown) {
                console.log('DataTables error:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Could not load payment follow-up data. Please try refreshing the page.',
                    icon: 'error'
                });
            }
        },
        'order': [],
        'columnDefs': [{
            'targets': [7], // Action column
            'orderable': false
        }]
    });

    // Handle form submission for new follow-up
    $('#submitGpsPaymentFollowupForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#addGpsPaymentFollowupModal').modal('hide');
                    $('#submitGpsPaymentFollowupForm')[0].reset();
                    gpsPaymentFollowupTable.ajax.reload(null, false);
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
            error: function() {
                Swal.fire({
                    title: 'Error',
                    text: 'An error occurred while processing your request.',
                    icon: 'error'
                });
            }
        });
    });

    // Handle edit follow-up
    $(document).on('click', '.editFollowup', function() {
        var followupId = $(this).data('id');
        
        $.ajax({
            url: 'php_action/fetchSelectedFollowup.php',
            type: 'POST',
            data: {followupId: followupId},
            dataType: 'json',
            success: function(response) {
                $('#editFollowupDate').val(response.followup_date);
                $('#editOrderId').val(response.order_id);
                $('#editPaymentId').val(response.payment_id);
                $('#editInvestorId').val(response.investor_id);
                $('#editFollowupType').val(response.followup_type);
                $('#editNotes').val(response.notes);
                $('#editStatus').val(response.status);
                $('#followupId').val(response.id);
                
                $('#editGpsPaymentFollowupModal').modal('show');
            },
            error: function() {
                Swal.fire({
                    title: 'Error',
                    text: 'Could not fetch follow-up details.',
                    icon: 'error'
                });
            }
        });
    });

    // Handle edit form submission
    $('#editGpsPaymentFollowupForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#editGpsPaymentFollowupModal').modal('hide');
                    gpsPaymentFollowupTable.ajax.reload(null, false);
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
            error: function() {
                Swal.fire({
                    title: 'Error',
                    text: 'An error occurred while processing your request.',
                    icon: 'error'
                });
            }
        });
    });

    // Handle delete follow-up
    $(document).on('click', '.removeFollowup', function() {
        var followupId = $(this).data('id');
        
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
                    url: 'php_action/removeGpsPaymentFollowup.php',
                    type: 'POST',
                    data: {followupId: followupId},
                    dataType: 'json',
                    success: function(response) {
                        if(response.success) {
                            gpsPaymentFollowupTable.ajax.reload(null, false);
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
                    error: function() {
                        Swal.fire(
                            'Error!',
                            'Could not delete the follow-up.',
                            'error'
                        );
                    }
                });
            }
        });
    });

    // Load orders for dropdown
    $.ajax({
        url: 'php_action/fetchGpsOrders.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            var options = '<option value="">Select Order</option>';
            $.each(response.data, function(index, order) {
                options += '<option value="' + order.id + '">' + order.order_number + '</option>';
            });
            $('#orderId, #editOrderId').html(options);
        }
    });

    // Load payments for dropdown
    $.ajax({
        url: 'php_action/fetchGpsPayments.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            var options = '<option value="">Select Payment</option>';
            $.each(response.data, function(index, payment) {
                options += '<option value="' + payment.id + '">' + payment.payment_number + '</option>';
            });
            $('#paymentId, #editPaymentId').html(options);
        }
    });

    // Load investors for dropdown
    $.ajax({
        url: 'php_action/fetchGpsInvestors.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            var options = '<option value="">Select Investor</option>';
            $.each(response.data, function(index, investor) {
                options += '<option value="' + investor.id + '">' + investor.name + '</option>';
            });
            $('#investorId, #editInvestorId').html(options);
        }
    });
}); 