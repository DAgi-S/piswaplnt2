var gpsInvestorsTable;

$(document).ready(function() {
    // Initialize DataTable
    gpsInvestorsTable = $('#gpsInvestorsTable').DataTable({
        'ajax': 'php_action/fetchGpsInvestors.php',
        'order': []
    });

    // Handle form submission for new investor
    $('#submitGpsInvestorForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#addGpsInvestorModal').modal('hide');
                    $('#submitGpsInvestorForm')[0].reset();
                    gpsInvestorsTable.ajax.reload(null, false);
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

    // Handle edit investor
    $(document).on('click', '.editInvestor', function() {
        var investorId = $(this).data('id');
        
        $.ajax({
            url: 'php_action/fetchSelectedInvestor.php',
            type: 'POST',
            data: {investorId: investorId},
            dataType: 'json',
            success: function(response) {
                $('#editName').val(response.name);
                $('#editSharePercentage').val(response.share_percentage);
                $('#editInvestment').val(response.investment);
                $('#editAccountType').val(response.account_type);
                $('#editBalance').val(response.balance);
                $('#editCreditAmount').val(response.credit_amount);
                $('#investorId').val(response.id);
                
                $('#editGpsInvestorModal').modal('show');
            },
            error: function() {
                Swal.fire({
                    title: 'Error',
                    text: 'Could not fetch investor details.',
                    icon: 'error'
                });
            }
        });
    });

    // Handle edit form submission
    $('#editGpsInvestorForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#editGpsInvestorModal').modal('hide');
                    gpsInvestorsTable.ajax.reload(null, false);
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

    // Handle delete investor
    $(document).on('click', '.removeInvestor', function() {
        var investorId = $(this).data('id');
        
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
                    url: 'php_action/removeGpsInvestor.php',
                    type: 'POST',
                    data: {investorId: investorId},
                    dataType: 'json',
                    success: function(response) {
                        if(response.success) {
                            gpsInvestorsTable.ajax.reload(null, false);
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
                            'Could not delete the investor.',
                            'error'
                        );
                    }
                });
            }
        });
    });

    // Validate share percentage
    $('#sharePercentage, #editSharePercentage').on('input', function() {
        var value = parseFloat($(this).val());
        if(value < 0) {
            $(this).val(0);
        } else if(value > 100) {
            $(this).val(100);
        }
    });
}); 