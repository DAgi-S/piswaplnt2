$(document).ready(function() {
    // Initialize DataTable
    var manageExpenseTable = $('#manageExpenseTable').DataTable({
        'ajax': {
            'url': '../expense/api/getExpenses.php',
            'type': 'GET',
            'data': function(d) {
                d.type = 'production'; // Only get production-related expenses
                return d;
            }
        },
        'columns': [
            { 'data': 'expense_number' },
            { 'data': 'expense_date', 
              'render': function(data) {
                  return data ? moment(data).format('YYYY-MM-DD') : '';
              }
            },
            { 
                'data': 'category_id',
                'render': function(data, type, row) {
                    // Get category type from the joined category table
                    return row.category_name || 'Direct Production Costs';
                }
            },
            { 
                'data': 'category_id',
                'render': function(data, type, row) {
                    return row.category_name || '';
                }
            },
            { 
                'data': 'amount',
                'render': function(data) {
                    return parseFloat(data).toFixed(2);
                }
            },
            { 
                'data': 'production_order_id',
                'render': function(data, type, row) {
                    return row.production_order_number || '';
                }
            },
            { 'data': 'status' },
            {
                'data': null,
                'orderable': false,
                'searchable': false,
                'render': function(data, type, row) {
                    return '<div class="btn-group">' +
                        '<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' +
                        'Action <span class="caret"></span>' +
                        '</button>' +
                        '<ul class="dropdown-menu dropdown-menu-right">' +
                        '<li><a href="#" class="view-expense" data-id="' + row.id + '"><i class="glyphicon glyphicon-eye-open"></i> View</a></li>' +
                        '<li><a href="#" class="edit-expense" data-id="' + row.id + '"><i class="glyphicon glyphicon-edit"></i> Edit</a></li>' +
                        '<li><a href="#" class="delete-expense" data-id="' + row.id + '"><i class="glyphicon glyphicon-trash"></i> Delete</a></li>' +
                        '</ul>' +
                        '</div>';
                }
            }
        ],
        'order': [[1, 'desc']], // Sort by date in descending order
        'processing': true,
        'serverSide': true,
        'responsive': true,
        'dom': '<"top"fl>rt<"bottom"ip><"clear">',
        'language': {
            'processing': 'Loading...',
            'emptyTable': 'No expenses found',
            'zeroRecords': 'No matching expenses found'
        }
    });

    // Handle expense type selection
    $('.add-expense-type').on('click', function(e) {
        e.preventDefault();
        var expenseType = $(this).data('type');
        
        // Show the appropriate modal based on expense type
        switch(expenseType) {
            case 'production':
                $('#productionExpenseModal').modal('show');
                loadProductionData();
                break;
            case 'labor':
                $('#laborExpenseModal').modal('show');
                loadExpenseCategories('labor');
                loadProductionData();
                break;
            case 'maintenance':
                $('#maintenanceExpenseModal').modal('show');
                loadExpenseCategories('maintenance');
                loadAssets();
                break;
            case 'other':
                $('#otherExpenseModal').modal('show');
                loadExpenseCategories('other');
                break;
        }
    });

    // Load expense categories based on type
    function loadExpenseCategories(type) {
        $.ajax({
            url: '../expense/api/fetchExpenseCategories.php',
            type: 'GET',
            data: { type: type },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    var options = '<option value="">Select Category</option>';
                    $.each(response.data, function(index, category) {
                        options += '<option value="' + category.id + '">' + category.name + '</option>';
                    });
                    $('#' + type + 'ExpenseCategory').html(options);
                }
            }
        });
    }

    // Load production-related data
    function loadProductionData() {
        // Load production expense categories
        loadExpenseCategories('production');

        // Load production orders
        $.ajax({
            url: '../expense/api/fetchProductionOrders.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    var options = '<option value="">Select Production Order</option>';
                    $.each(response.data, function(index, order) {
                        options += '<option value="' + order.id + '">' + order.order_number + '</option>';
                    });
                    $('#productionOrderSelect').html(options);
                }
            }
        });
    }

    // Load assets for maintenance expense
    function loadAssets() {
        $.ajax({
            url: '../expense/api/fetchAssets.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    var options = '<option value="">Select Asset</option>';
                    $.each(response.data, function(index, asset) {
                        options += '<option value="' + asset.id + '">' + asset.name + '</option>';
                    });
                    $('#maintenanceExpenseAsset').html(options);
                }
            }
        });
    }

    // Handle form submissions
    $('.expense-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var formData = new FormData(this);
        
        // Add production type to all forms
        formData.append('expense_type', 'production');

        $.ajax({
            url: '../expense/api/createExpense.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if(response.success) {
                    // Show success message
                    showAlert('success', response.messages);

                    // Reset form and close modal
                    form[0].reset();
                    form.closest('.modal').modal('hide');

                    // Reload expense table
                    manageExpenseTable.ajax.reload(null, false);
                } else {
                    // Show error message
                    showAlert('error', response.messages);
                }
            }
        });
    });

    // Handle file input change
    $('.expense-attachment').on('change', function() {
        var fileInput = $(this);
        var maxSize = 5 * 1024 * 1024; // 5MB
        var allowedTypes = ['image/jpeg', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        
        if (fileInput[0].files.length > 0) {
            var file = fileInput[0].files[0];
            if (file.size > maxSize) {
                alert('File size exceeds 5MB limit');
                fileInput.val('');
                return;
            }
            if (!allowedTypes.includes(file.type)) {
                alert('Invalid file type. Allowed types: JPG, PNG, PDF, DOC, DOCX');
                fileInput.val('');
                return;
            }
        }
    });

    // Helper function to show alerts
    function showAlert(type, message) {
        var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        var icon = type === 'success' ? 'glyphicon-ok-sign' : 'glyphicon-exclamation-sign';
        
        $('.remove-messages').html(
            '<div class="alert ' + alertClass + '">' +
            '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
            '<strong><i class="glyphicon ' + icon + '"></i></strong> ' + message +
            '</div>'
        );

        // Auto hide after 3 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 3000);
    }

    // Initialize date pickers
    $('.expense-date').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        todayHighlight: true
    });
}); 