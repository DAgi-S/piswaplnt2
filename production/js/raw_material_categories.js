$(document).ready(function() {
    // Initialize DataTable
    var categoriesTable = $('#categoriesTable').DataTable({
        'processing': true,
        'serverSide': true,
        'ajax': {
            'url': 'php_action/fetchMaterialCategories.php',
            'type': 'POST',
            'dataType': 'json',
            'error': function(xhr, error, thrown) {
                console.error('DataTables error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while loading the data. Please try again.'
                });
            }
        },
        'order': [[0, 'asc']],
        'columns': [
            { data: 'name' },
            { data: 'description' },
            { 
                data: 'status',
                render: function(data) {
                    let badge = data === 'active' ? 'label-success' : 'label-danger';
                    return '<span class="label ' + badge + '">' + data.toUpperCase() + '</span>';
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    return `
                        <div class="btn-group">
                            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                Action <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-right">
                                <li><a href="#" onclick="editCategory(${row.id})"><i class="fa fa-edit"></i> Edit</a></li>
                                <li><a href="#" onclick="removeCategory(${row.id})"><i class="fa fa-trash"></i> Remove</a></li>
                            </ul>
                        </div>`;
                }
            }
        ],
        'pageLength': 10,
        'responsive': true,
        'dom': '<"row"<"col-sm-6"l><"col-sm-6"f>>' +
               '<"row"<"col-sm-12"tr>>' +
               '<"row"<"col-sm-5"i><"col-sm-7"p>>'
    });

    // Form submission for creating category
    $('#submitCategoryForm').on('submit', function(e) {
        e.preventDefault();
        
        // Validate form
        var isValid = true;
        var $form = $(this);
        
        // Check required fields
        $form.find('[required]').each(function() {
            if(!$(this).val()) {
                isValid = false;
                $(this).addClass('error');
            } else {
                $(this).removeClass('error');
            }
        });
        
        if(!isValid) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Please fill in all required fields'
            });
            return false;
        }
        
        // Show loading state
        $('#createCategoryBtn')
            .prop('disabled', true)
            .html('<i class="fa fa-spinner fa-spin"></i> Creating...');
        
        $.ajax({
            url: 'php_action/createMaterialCategory.php',
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    // Reset form and close modal
                    $form[0].reset();
                    $('#addCategoryModal').modal('hide');
                    
                    // Reload DataTable
                    categoriesTable.ajax.reload();
                    
                    // Show success notification
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.messages || 'Material category created successfully'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.messages || 'An error occurred while creating the category'
                    });
                }
            },
            error: function(xhr, status, error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while creating the category'
                });
                console.error('Error:', error);
            },
            complete: function() {
                // Reset button state
                $('#createCategoryBtn')
                    .prop('disabled', false)
                    .html('Create Category');
            }
        });
    });

    // Form submission for editing category
    $('#editCategoryForm').on('submit', function(e) {
        e.preventDefault();
        
        // Validate form
        var isValid = true;
        var $form = $(this);
        
        // Check required fields
        $form.find('[required]').each(function() {
            if(!$(this).val()) {
                isValid = false;
                $(this).addClass('error');
            } else {
                $(this).removeClass('error');
            }
        });
        
        if(!isValid) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Please fill in all required fields'
            });
            return false;
        }
        
        // Show loading state
        $('#editCategoryBtn')
            .prop('disabled', true)
            .html('<i class="fa fa-spinner fa-spin"></i> Saving...');
        
        $.ajax({
            url: 'php_action/editMaterialCategory.php',
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    // Close modal
                    $('#editCategoryModal').modal('hide');
                    
                    // Reload DataTable
                    categoriesTable.ajax.reload();
                    
                    // Show success notification
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.messages || 'Material category updated successfully'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.messages || 'An error occurred while updating the category'
                    });
                }
            },
            error: function(xhr, status, error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while updating the category'
                });
                console.error('Error:', error);
            },
            complete: function() {
                // Reset button state
                $('#editCategoryBtn')
                    .prop('disabled', false)
                    .html('Save Changes');
            }
        });
    });
});

// Edit Category
function editCategory(id) {
    $.ajax({
        url: 'php_action/getMaterialCategory.php',
        type: 'POST',
        data: { categoryId: id },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                // Populate form fields
                $('#editCategoryId').val(response.data.id);
                $('#editCategoryName').val(response.data.name);
                $('#editDescription').val(response.data.description);
                $('#editStatus').val(response.data.status);
                
                // Show modal
                $('#editCategoryModal').modal('show');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.messages || 'Error fetching category details'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            console.log('Response Text:', xhr.responseText);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error fetching category details'
            });
        }
    });
}

// Remove Category
function removeCategory(id) {
    if (!id) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Category ID is missing'
        });
        return;
    }

    Swal.fire({
        title: 'Are you sure?',
        text: "This material category will be permanently deleted!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'No, cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading state
            Swal.fire({
                title: 'Deleting...',
                text: 'Please wait while we delete the category',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: 'php_action/removeMaterialCategory.php',
                type: 'POST',
                data: { categoryId: id },
                dataType: 'json'
            })
            .done(function(response) {
                if (response.success) {
                    // Reload the DataTable
                    $('#categoriesTable').DataTable().ajax.reload(null, false);
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: response.messages || 'Category has been deleted successfully'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.messages || 'Could not delete the category'
                    });
                }
            })
            .fail(function(xhr, status, error) {
                console.error('Delete Error:', error);
                console.log('Response Text:', xhr.responseText);
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while deleting the category. Please try again.'
                });
            });
        }
    });
}

// Reset forms when modals are closed
$('#addCategoryModal').on('hidden.bs.modal', function() {
    $('#submitCategoryForm')[0].reset();
});

$('#editCategoryModal').on('hidden.bs.modal', function() {
    $('#editCategoryForm')[0].reset();
}); 