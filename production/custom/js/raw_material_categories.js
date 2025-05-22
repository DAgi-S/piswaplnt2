$(document).ready(function() {
    // Get permissions from PHP
    var permissions = {
        edit: typeof userPermissions !== 'undefined' ? (userPermissions.edit || userPermissions.legacy_edit) : false,
        delete: typeof userPermissions !== 'undefined' ? (userPermissions.delete || userPermissions.legacy_delete) : false,
        manage: typeof userPermissions !== 'undefined' ? (userPermissions.manage || userPermissions.legacy_manage) : false
    };

    // Initialize DataTable
    var categoriesTable = $('#categoriesTable').DataTable({
        'ajax': {
            'url': 'php_action/fetchMaterialCategories.php',
            'type': 'POST',
            'dataType': 'json',
            'error': function(xhr, error, thrown) {
                console.error('DataTables error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while fetching data'
                });
            }
        },
        'dom': 'Bfrtip',
        'buttons': ['copy', 'csv', 'excel', 'pdf', 'print'],
        'order': [[0, 'desc']],
        'serverSide': true,
        'processing': true,
        'columns': [
            { data: 'name' },
            { data: 'description' },
            { 
                data: 'status',
                render: function(data) {
                    return data === 'active' ? 
                        '<span class="label label-success">Active</span>' : 
                        '<span class="label label-danger">Inactive</span>';
                }
            }
        ]
    });

    // Add action column if user has edit or delete permissions
    if (permissions.edit || permissions.delete || permissions.manage) {
        categoriesTable.column.add({
            data: null,
            render: function(data) {
                var buttons = '';
                if (permissions.edit || permissions.manage) {
                    buttons += '<button type="button" class="btn btn-warning btn-sm" onclick="editCategory(' + data.id + ')"><i class="glyphicon glyphicon-edit"></i></button> ';
                }
                if (permissions.delete || permissions.manage) {
                    buttons += '<button type="button" class="btn btn-danger btn-sm" onclick="removeCategory(' + data.id + ')"><i class="glyphicon glyphicon-trash"></i></button>';
                }
                return buttons;
            }
        }).draw();
    }

    // Add Category Form Submit
    $('#addCategoryForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: 'php_action/createMaterialCategory.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#addCategoryModal').modal('hide');
                    $('#addCategoryForm')[0].reset();
                    categoriesTable.ajax.reload();
                    
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
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while processing your request'
                });
            }
        });
    });

    // Edit Category Form Submit
    $('#editCategoryForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: 'php_action/editMaterialCategory.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#editCategoryModal').modal('hide');
                    categoriesTable.ajax.reload();
                    
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
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while processing your request'
                });
            }
        });
    });
});

// Edit Category Function
function editCategory(id) {
    $.ajax({
        url: 'php_action/getMaterialCategory.php',
        type: 'POST',
        data: {categoryId: id},
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#editCategoryId').val(response.data.id);
                $('#editCategoryName').val(response.data.name);
                $('#editDescription').val(response.data.description);
                $('#editStatus').val(response.data.status);
                $('#editCategoryModal').modal('show');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.messages
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An error occurred while fetching category data'
            });
        }
    });
}

// Remove Category Function
function removeCategory(id) {
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
                url: 'php_action/removeMaterialCategory.php',
                type: 'POST',
                data: {categoryId: id},
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#categoriesTable').DataTable().ajax.reload();
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
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while processing your request'
                    });
                }
            });
        }
    });
}

// Reset forms when modals are closed
$('#addCategoryModal').on('hidden.bs.modal', function() {
    $('#addCategoryForm')[0].reset();
});

$('#editCategoryModal').on('hidden.bs.modal', function() {
    $('#editCategoryForm')[0].reset();
}); 