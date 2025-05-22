$(document).ready(function() {
    // Initialize categories DataTable
    var categoriesTable = $('#manageCategoriesTable').DataTable({
        'ajax': 'php_action/fetchCategories.php',
        'order': []
    });

    // Handle category form submission
    $("#submitCategoryForm").on("submit", function(e) {
        e.preventDefault();
        
        var form = $(this);
        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $("#addCategoryModal").modal('hide');
                    categoriesTable.ajax.reload(null, false);
                    $("#success-message").html(response.messages);
                    $("#success-alert").show();
                    setTimeout(function() {
                        $("#success-alert").hide();
                    }, 3000);
                    form[0].reset();
                }
            }
        });
    });

    // Handle edit category
    $(document).on('click', '.edit-category', function() {
        var categoryId = $(this).data('id');
        $.ajax({
            url: 'php_action/getCategory.php',
            type: 'post',
            data: {categoryId: categoryId},
            dataType: 'json',
            success: function(response) {
                $("#categoryId").val(response.category_id);
                $("#editCategoryName").val(response.category_name);
                $("#editDescription").val(response.description);
                $("#editCategoryModal").modal('show');
            }
        });
    });

    // Handle edit form submission
    $("#editCategoryForm").on("submit", function(e) {
        e.preventDefault();
        var form = $(this);
        
        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $("#editCategoryModal").modal('hide');
                    categoriesTable.ajax.reload(null, false);
                    $("#success-message").html(response.messages);
                    $("#success-alert").show();
                    setTimeout(function() {
                        $("#success-alert").hide();
                    }, 3000);
                }
            }
        });
    });

    // Handle delete category
    $(document).on('click', '.remove-category', function() {
        var categoryId = $(this).data('id');
        if(confirm('Are you sure you want to delete this category?')) {
            $.ajax({
                url: 'php_action/removeCategory.php',
                type: 'post',
                data: {categoryId: categoryId},
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        categoriesTable.ajax.reload(null, false);
                        $("#success-message").html(response.messages);
                        $("#success-alert").show();
                        setTimeout(function() {
                            $("#success-alert").hide();
                        }, 3000);
                    }
                }
            });
        }
    });
}); 