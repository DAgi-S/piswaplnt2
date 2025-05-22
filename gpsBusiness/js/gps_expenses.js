var gpsExpensesTable;

$(document).ready(function() {
    // Initialize DataTable
    gpsExpensesTable = $('#gpsExpensesTable').DataTable({
        'ajax': 'php_action/fetchGpsExpenses.php',
        'order': [],
        'columnDefs': [{
            'targets': [6], // Action column
            'orderable': false
        }]
    });

    // Handle form submission for new expense
    $('#submitGpsExpenseForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#addGpsExpenseModal').modal('hide');
                    $('#submitGpsExpenseForm')[0].reset();
                    gpsExpensesTable.ajax.reload(null, false);
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

    // Handle edit expense
    $(document).on('click', '.editExpense', function() {
        var expenseId = $(this).data('id');
        
        $.ajax({
            url: 'php_action/fetchSelectedExpense.php',
            type: 'POST',
            data: {expenseId: expenseId},
            dataType: 'json',
            success: function(response) {
                $('#editDate').val(response.date);
                $('#editName').val(response.name);
                $('#editExpenseType').val(response.expense_type);
                $('#editUnitPrice').val(response.unit_price);
                $('#editQuantity').val(response.quantity);
                $('#editTotal').val(response.total);
                $('#expenseId').val(response.id);
                
                $('#editGpsExpenseModal').modal('show');
            },
            error: function() {
                Swal.fire({
                    title: 'Error',
                    text: 'Could not fetch expense details.',
                    icon: 'error'
                });
            }
        });
    });

    // Handle edit form submission
    $('#editGpsExpenseForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#editGpsExpenseModal').modal('hide');
                    gpsExpensesTable.ajax.reload(null, false);
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

    // Handle delete expense
    $(document).on('click', '.removeExpense', function() {
        var expenseId = $(this).data('id');
        
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
                    url: 'php_action/removeGpsExpense.php',
                    type: 'POST',
                    data: {expenseId: expenseId},
                    dataType: 'json',
                    success: function(response) {
                        if(response.success) {
                            gpsExpensesTable.ajax.reload(null, false);
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
                            'Could not delete the expense.',
                            'error'
                        );
                    }
                });
            }
        });
    });

    // Auto calculate total
    function calculateTotal() {
        var form = $(this).closest('form');
        var unitPrice = parseFloat(form.find('[name$="UnitPrice"]').val()) || 0;
        var quantity = parseInt(form.find('[name$="Quantity"]').val()) || 0;
        
        var total = unitPrice * quantity;
        form.find('[name$="Total"]').val(total.toFixed(2));
    }

    // Attach calculation to input changes
    $(document).on('input', '#unitPrice, #quantity, #editUnitPrice, #editQuantity', calculateTotal);
}); 