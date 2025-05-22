var gpsExpenseCategoriesTable;

$(document).ready(function() {
    // Initialize DataTable
    gpsExpenseCategoriesTable = $('#gpsExpenseCategoriesTable').DataTable({
        'ajax': {
            'url': 'php_action/fetchGpsExpenseCategories.php',
            'type': 'GET',
            'error': function(xhr, error, thrown) {
                console.log('DataTables error:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Could not load expense categories. Please try refreshing the page.',
                    icon: 'error'
                });
            }
        },
        'order': [],
        'columnDefs': [{
            'targets': [4], // Action column
            'orderable': false
        }]
    });

    // Handle form submission for new category
    $('#submitGpsExpenseCategoryForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#addGpsExpenseCategoryModal').modal('hide');
                    $('#submitGpsExpenseCategoryForm')[0].reset();
                    gpsExpenseCategoriesTable.ajax.reload(null, false);
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

    // Handle edit category
    $(document).on('click', '.editCategory', function() {
        var categoryId = $(this).data('id');
        
        $.ajax({
            url: 'php_action/fetchSelectedExpenseCategory.php',
            type: 'POST',
            data: {categoryId: categoryId},
            dataType: 'json',
            success: function(response) {
                $('#editName').val(response.name);
                $('#editExpenseType').val(response.expense_type);
                $('#editDescription').val(response.description);
                $('#editUnitPrice').val(response.unit_price);
                $('#categoryId').val(response.id);
                
                $('#editGpsExpenseCategoryModal').modal('show');
            },
            error: function() {
                Swal.fire({
                    title: 'Error',
                    text: 'Could not fetch category details.',
                    icon: 'error'
                });
            }
        });
    });

    // Handle edit form submission
    $('#editGpsExpenseCategoryForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#editGpsExpenseCategoryModal').modal('hide');
                    gpsExpenseCategoriesTable.ajax.reload(null, false);
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

    // Handle delete category
    $(document).on('click', '.removeCategory', function() {
        var categoryId = $(this).data('id');
        
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
                    url: 'php_action/removeGpsExpenseCategory.php',
                    type: 'POST',
                    data: {categoryId: categoryId},
                    dataType: 'json',
                    success: function(response) {
                        if(response.success) {
                            gpsExpenseCategoriesTable.ajax.reload(null, false);
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
                            'Could not delete the category.',
                            'error'
                        );
                    }
                });
            }
        });
    });
}); 