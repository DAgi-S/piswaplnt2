$(document).ready(function() {
    // Initialize select2 for better dropdown experience
    $('#categoryFilter, #categorySelect').select2();

    // Initialize transactions DataTable
    var transactionsTable = $('#categorizedTransactionsTable').DataTable({
        'ajax': {
            'url': 'php_action/fetchCategorizedTransactions.php',
            'data': function(d) {
                d.categoryId = $('#categoryFilter').val();
            }
        },
        'order': [[1, 'desc']], // Order by date column
        'pageLength': 10,
        'responsive': true,
        'dom': 'Bfrtip',
        'buttons': [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        'columnDefs': [
            {
                'targets': 0, // Checkbox column
                'searchable': false,
                'orderable': false,
                'className': 'dt-body-center',
                'render': function (data, type, full, meta) {
                    return '<input type="checkbox" class="transaction-checkbox" value="' + data + '">';
                }
            },
            {
                'targets': 7, // Action column
                'orderable': false
            }
        ],
        'select': {
            'style': 'multi'
        }
    });

    // Handle search input
    $('#searchInput').on('keyup', function() {
        transactionsTable.search(this.value).draw();
    });

    // Handle category filter change
    $('#categoryFilter').change(function() {
        transactionsTable.ajax.reload();
    });

    // Handle select all checkbox
    $('#selectAll').on('click', function() {
        $('.transaction-checkbox').prop('checked', this.checked);
        updateAssignSelectedButton();
    });

    // Handle individual checkbox changes
    $(document).on('change', '.transaction-checkbox', function() {
        updateAssignSelectedButton();
        // If not all checkboxes are checked, uncheck "select all"
        if (!this.checked) {
            $('#selectAll').prop('checked', false);
        }
    });

    // Update Assign Selected button state
    function updateAssignSelectedButton() {
        var checkedCount = $('.transaction-checkbox:checked').length;
        $('#assignSelectedBtn').prop('disabled', checkedCount === 0);
    }

    // Handle assign selected button click
    $('#assignSelectedBtn').click(function() {
        var selectedIds = [];
        $('.transaction-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length > 0) {
            $('#isMultiple').val(1);
            $('#transactionId').val(selectedIds.join(','));
            $('#assignCategoryModal').modal('show');
        }
    });

    // Handle assign category button click for single transaction
    $(document).on('click', '.assign-category', function() {
        var transactionId = $(this).data('id');
        var currentCategory = $(this).data('category');
        $('#isMultiple').val(0);
        $('#transactionId').val(transactionId);
        $('#categorySelect').val(currentCategory).trigger('change');
        $('#assignCategoryModal').modal('show');
    });

    // Handle category assignment form submission
    $('#assignCategoryForm').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var isMultiple = $('#isMultiple').val() === '1';
        var url = isMultiple ? 'php_action/assignBulkCategory.php' : 'php_action/assignCategory.php';

        $.ajax({
            url: url,
            type: form.attr('method'),
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#assignCategoryModal').modal('hide');
                    transactionsTable.ajax.reload(null, false);
                    $("#success-message").html(response.messages);
                    $("#success-alert").show();
                    setTimeout(function() {
                        $("#success-alert").hide();
                    }, 3000);
                    // Reset checkboxes and button state
                    $('#selectAll').prop('checked', false);
                    updateAssignSelectedButton();
                } else {
                    alert(response.messages);
                }
            },
            error: function() {
                alert('Error occurred while assigning category');
            }
        });
    });

    // Handle bulk auto-assign category
    $('#bulkAssignCategory').click(function() {
        if(confirm('Are you sure you want to assign categories to all uncategorized transactions based on their platform?')) {
            $.ajax({
                url: 'php_action/bulkAssignCategories.php',
                type: 'post',
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        transactionsTable.ajax.reload(null, false);
                        $("#success-message").html(response.messages);
                        $("#success-alert").show();
                        setTimeout(function() {
                            $("#success-alert").hide();
                        }, 3000);
                    } else {
                        alert(response.messages);
                    }
                },
                error: function() {
                    alert('Error occurred during bulk category assignment');
                }
            });
        }
    });
}); 