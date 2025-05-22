$(document).ready(function() {
    // Initialize DataTable
    var expenseTable = $('#manageExpenseTable').DataTable({
        'ajax': {
            'url': 'expense/api/getExpenses.php',
            'type': 'POST',
            'data': function(data) {
                data.expenseType = $('#filterExpenseType').val();
                data.dateFrom = $('#filterDateFrom').val();
                data.dateTo = $('#filterDateTo').val();
            }
        },
        'order': [[1, 'desc']],
        'columns': [
            { 'data': 'expense_number' },
            { 'data': 'date' },
            { 'data': 'type' },
            { 'data': 'category' },
            { 
                'data': 'amount',
                'render': function(data) {
                    return parseFloat(data).toFixed(2);
                }
            },
            { 'data': 'reference' },
            { 'data': 'status' },
            {
                'data': null,
                'render': function(data) {
                    var buttons = '<div class="btn-group">';
                    buttons += '<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">';
                    buttons += '<i class="fa fa-cog"></i> <span class="caret"></span></button>';
                    buttons += '<ul class="dropdown-menu dropdown-menu-right">';
                    buttons += '<li><a href="#" class="edit-expense" data-id="' + data.id + '"><i class="fa fa-edit"></i> Edit</a></li>';
                    if (data.attachment) {
                        buttons += '<li><a href="' + data.attachment + '" target="_blank"><i class="fa fa-file"></i> View Attachment</a></li>';
                    }
                    if (data.status === 'draft') {
                        buttons += '<li><a href="#" class="submit-expense" data-id="' + data.id + '"><i class="fa fa-check"></i> Submit</a></li>';
                        buttons += '<li><a href="#" class="delete-expense" data-id="' + data.id + '"><i class="fa fa-trash"></i> Delete</a></li>';
                    }
                    buttons += '</ul></div>';
                    return buttons;
                }
            }
        ]
    });

    // Handle expense type selection in dropdown menu
    $('.dropdown-menu a[data-type]').click(function() {
        var expenseType = $(this).data('type');
        $('#expenseType').val(expenseType);
        $('.expense-type-text').text($(this).text());
        
        // Show/hide production order field based on expense type
        if (expenseType === 'production') {
            $('.production-only').show();
            $('.non-production').hide();
            $('#productionOrder').prop('required', true);
        } else {
            $('.production-only').hide();
            $('.non-production').show();
            $('#productionOrder').prop('required', false);
        }
        
        // Load appropriate categories based on expense type
        loadExpenseCategories(expenseType);
    });

    // Load expense categories based on type
    function loadExpenseCategories(type) {
        $.ajax({
            url: 'expense/api/fetchExpenseCategories.php',
            type: 'GET',
            data: { type: type },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var categories = response.data;
                    var options = '<option value="">Select Category</option>';
                    categories.forEach(function(category) {
                        options += '<option value="' + category.id + '">' + category.name + '</option>';
                    });
                    $('#expenseCategory, #editExpenseCategory').html(options);
                }
            },
            error: function() {
                showErrorMessage('Error loading expense categories');
            }
        });
    }

    // Load production orders for production expenses
    function loadProductionOrders() {
        $.ajax({
            url: 'expense/api/fetchProductionOrders.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var orders = response.data;
                    var options = '<option value="">Select Production Order</option>';
                    orders.forEach(function(order) {
                        options += '<option value="' + order.id + '">' + order.order_number + ' - ' + order.product_name + '</option>';
                    });
                    $('#productionOrder, #editProductionOrder').html(options);
                }
            },
            error: function() {
                showErrorMessage('Error loading production orders');
            }
        });
    }

    // Handle filter button click
    $('#filterExpenses').click(function() {
        expenseTable.ajax.reload();
    });

    // Handle reset filters button click
    $('#resetFilters').click(function() {
        $('#filterExpenseType').val('');
        $('#filterDateFrom').val('');
        $('#filterDateTo').val('');
        expenseTable.ajax.reload();
    });

    // Handle form submission for adding expense
    $('#submitExpenseForm').submit(function(e) {
        e.preventDefault();
        var form = $(this);
        var formData = new FormData(this);

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#addExpenseModal').modal('hide');
                    form[0].reset();
                    expenseTable.ajax.reload();
                    showSuccessMessage('Expense added successfully');
                } else {
                    showErrorMessage(response.messages);
                }
            },
            error: function() {
                showErrorMessage('Error adding expense');
            }
        });
    });

    // Handle edit expense button click
    $(document).on('click', '.edit-expense', function(e) {
        e.preventDefault();
        var expenseId = $(this).data('id');
        
        $.ajax({
            url: 'expense/api/getExpense.php',
            type: 'GET',
            data: { id: expenseId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var expense = response.data;
                    $('#editExpenseId').val(expense.id);
                    $('#editExpenseType').val(expense.type);
                    $('#editExpenseDate').val(expense.date);
                    loadExpenseCategories(expense.type);
                    setTimeout(function() {
                        $('#editExpenseCategory').val(expense.category_id);
                    }, 500);
                    $('#editExpenseAmount').val(expense.amount);
                    $('#editExpenseDescription').val(expense.description);
                    $('#editExpenseReference').val(expense.reference);
                    
                    if (expense.type === 'production') {
                        $('.production-only').show();
                        $('.non-production').hide();
                        loadProductionOrders();
                        setTimeout(function() {
                            $('#editProductionOrder').val(expense.production_order_id);
                        }, 500);
                    } else {
                        $('.production-only').hide();
                        $('.non-production').show();
                    }
                    
                    if (expense.attachment) {
                        $('#existingAttachment').html(
                            '<div class="mt-2">' +
                            '<a href="' + expense.attachment + '" target="_blank" class="btn btn-sm btn-info">' +
                            '<i class="fa fa-file"></i> View Current Attachment</a>' +
                            '</div>'
                        );
                    } else {
                        $('#existingAttachment').empty();
                    }
                    
                    $('#editExpenseModal').modal('show');
                }
            },
            error: function() {
                showErrorMessage('Error loading expense details');
            }
        });
    });

    // Handle form submission for editing expense
    $('#editExpenseForm').submit(function(e) {
        e.preventDefault();
        var form = $(this);
        var formData = new FormData(this);

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#editExpenseModal').modal('hide');
                    form[0].reset();
                    expenseTable.ajax.reload();
                    showSuccessMessage('Expense updated successfully');
                } else {
                    showErrorMessage(response.messages);
                }
            },
            error: function() {
                showErrorMessage('Error updating expense');
            }
        });
    });

    // Handle submit expense button click
    $(document).on('click', '.submit-expense', function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to submit this expense?')) {
            var expenseId = $(this).data('id');
            
            $.ajax({
                url: 'expense/api/submitExpense.php',
                type: 'POST',
                data: { id: expenseId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        expenseTable.ajax.reload();
                        showSuccessMessage('Expense submitted successfully');
                    } else {
                        showErrorMessage(response.messages);
                    }
                },
                error: function() {
                    showErrorMessage('Error submitting expense');
                }
            });
        }
    });

    // Handle delete expense button click
    $(document).on('click', '.delete-expense', function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to delete this expense?')) {
            var expenseId = $(this).data('id');
            
            $.ajax({
                url: 'expense/api/deleteExpense.php',
                type: 'POST',
                data: { id: expenseId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        expenseTable.ajax.reload();
                        showSuccessMessage('Expense deleted successfully');
                    } else {
                        showErrorMessage(response.messages);
                    }
                },
                error: function() {
                    showErrorMessage('Error deleting expense');
                }
            });
        }
    });

    // Helper function to show success message
    function showSuccessMessage(message) {
        $('.remove-messages').html(
            '<div class="alert alert-success">' +
            '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
            '<strong><i class="fa fa-check"></i></strong> ' + message +
            '</div>'
        );
    }

    // Helper function to show error message
    function showErrorMessage(message) {
        $('.remove-messages').html(
            '<div class="alert alert-danger">' +
            '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
            '<strong><i class="fa fa-times"></i></strong> ' + message +
            '</div>'
        );
    }

    // Clear form and error messages when modal is hidden
    $('.modal').on('hidden.bs.modal', function() {
        $(this).find('form')[0].reset();
        $(this).find('.alert').remove();
        $('#existingAttachment').empty();
    });

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
}); 