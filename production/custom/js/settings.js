$(document).ready(function() {
    // Load company information
    $.ajax({
        url: 'php_action/fetchCompanyInfo.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            $('input[name="company_name"]').val(response.company_name || '');
            $('textarea[name="company_address"]').val(response.company_address || '');
            $('input[name="company_phone"]').val(response.company_phone || '');
            $('input[name="company_email"]').val(response.company_email || '');
            $('input[name="company_website"]').val(response.company_website || '');
            $('input[name="company_tin"]').val(response.company_tin || '');
            
            if(response.company_logo) {
                $('.company-logo').attr('src', 'assets/images/company/' + response.company_logo);
            }
        },
        error: function() {
            showAlert('error', 'Error loading company information');
        }
    });

    // Handle company information form submission
    $('#companyInfoForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    showAlert('success', 'Company information updated successfully');
                    if(response.logo) {
                        $('.company-logo').attr('src', 'assets/images/company/' + response.logo);
                    }
                } else {
                    showAlert('error', response.message || 'Error updating company information');
                }
            },
            error: function() {
                showAlert('error', 'Error updating company information');
            }
        });
    });

    // Initialize DataTables
    var categoriesTable = $('#categoriesTable').DataTable({
        'ajax': {
            'url': 'php_action/fetchCategories.php',
            'type': 'GET'
        },
        'order': [],
        'pageLength': 10,
        'responsive': true,
        'dom': '<"row"<"col-sm-6"l><"col-sm-6"f>>' +
               '<"row"<"col-sm-12"tr>>' +
               '<"row"<"col-sm-5"i><"col-sm-7"p>>',
        'columns': [
            { 'data': 0 }, // name
            { 'data': 1 }, // type
            { 'data': 2 }, // description
            { 'data': 3 }, // status
            { 'data': 4 }, // created_at
            { 'data': 5 }, // table_name
            { 
                'data': 6,  // action buttons
                'orderable': false
            }
        ]
    });

    var brandsTable = $('#brandsTable').DataTable({
        'ajax': 'php_action/fetchBrands.php',
        'order': [],
        'pageLength': 10,
        'responsive': true,
        'dom': '<"row"<"col-sm-6"l><"col-sm-6"f>>' +
               '<"row"<"col-sm-12"tr>>' +
               '<"row"<"col-sm-5"i><"col-sm-7"p>>',
        'columns': [
            { data: 'name' },
            { data: 'description' },
            { 
                data: 'status',
                render: function(data) {
                    return data == 1 ? 
                        '<span class="label label-success">Active</span>' : 
                        '<span class="label label-danger">Inactive</span>';
                }
            },
            { data: 'created_at' },
            { data: 'table_name' },
            {
                data: null,
                render: function(data) {
                    return '<div class="btn-group">' +
                           '<button class="btn btn-warning btn-sm" onclick="editBrand(' + data.id + ', \'' + data.table_name + '\')"><i class="fa fa-edit"></i></button> ' +
                           '<button class="btn btn-danger btn-sm" onclick="deleteBrand(' + data.id + ', \'' + data.table_name + '\')"><i class="fa fa-trash"></i></button>' +
                           '</div>';
                }
            }
        ]
    });

    var unitsTable = $('#unitsTable').DataTable({
        'ajax': 'php_action/fetchUnits.php',
        'order': [],
        'pageLength': 10,
        'responsive': true,
        'dom': '<"row"<"col-sm-6"l><"col-sm-6"f>>' +
               '<"row"<"col-sm-12"tr>>' +
               '<"row"<"col-sm-5"i><"col-sm-7"p>>',
        'columns': [
            { data: 'name' },
            { data: 'abbreviation' },
            { data: 'description' },
            { 
                data: 'status',
                render: function(data) {
                    return data == 1 ? 
                        '<span class="label label-success">Active</span>' : 
                        '<span class="label label-danger">Inactive</span>';
                }
            },
            {
                data: null,
                render: function(data) {
                    return '<div class="btn-group">' +
                           '<button class="btn btn-warning btn-sm" onclick="editUnit(' + data.id + ')"><i class="fa fa-edit"></i></button> ' +
                           '<button class="btn btn-danger btn-sm" onclick="deleteUnit(' + data.id + ')"><i class="fa fa-trash"></i></button>' +
                           '</div>';
                }
            }
        ]
    });

    // Initialize Tax Rates DataTable
    var taxTable = $('#taxTable').DataTable({
        'ajax': 'php_action/fetchTaxRates.php',
        'order': [],
        'pageLength': 10,
        'responsive': true,
        'dom': '<"row"<"col-sm-6"l><"col-sm-6"f>>' +
               '<"row"<"col-sm-12"tr>>' +
               '<"row"<"col-sm-5"i><"col-sm-7"p>>',
        'columns': [
            { data: 'name' },
            { 
                data: 'rate',
                render: function(data, type, row) {
                    return row.type === 'percentage' ? data + '%' : data;
                }
            },
            { 
                data: 'type',
                render: function(data) {
                    return data.charAt(0).toUpperCase() + data.slice(1);
                }
            },
            { 
                data: 'status',
                render: function(data) {
                    return data == 1 ? 
                        '<span class="label label-success">Active</span>' : 
                        '<span class="label label-danger">Inactive</span>';
                }
            },
            {
                data: null,
                render: function(data) {
                    return '<div class="btn-group">' +
                           '<button class="btn btn-warning btn-sm" onclick="editTax(' + data.id + ')"><i class="fa fa-edit"></i></button> ' +
                           '<button class="btn btn-danger btn-sm" onclick="deleteTax(' + data.id + ')"><i class="fa fa-trash"></i></button>' +
                           '</div>';
                }
            }
        ]
    });

    // Add Category Form Submission
    $('#addCategoryForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#addCategoryModal').modal('hide');
                    $('#addCategoryForm')[0].reset();
                    categoriesTable.ajax.reload(null, false);
                    showAlert('success', response.message);
                } else {
                    showAlert('error', response.message);
                }
            },
            error: function() {
                showAlert('error', 'Error occurred while processing request');
            }
        });
    });

    // Add Brand Form Submission
    $('#addBrandForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#addBrandModal').modal('hide');
                    $('#addBrandForm')[0].reset();
                    brandsTable.ajax.reload(null, false);
                    showAlert('success', response.message);
                } else {
                    showAlert('error', response.message);
                }
            },
            error: function() {
                showAlert('error', 'Error occurred while processing request');
            }
        });
    });

    // Add Unit Form Submission
    $('#addUnitForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#addUnitModal').modal('hide');
                    $('#addUnitForm')[0].reset();
                    if (typeof manageUnitsTable !== 'undefined') {
                        manageUnitsTable.ajax.reload(null, false);
                    } else {
                        $('#manageUnitsTable').DataTable().ajax.reload(null, false);
                    }
                    showAlert('success', response.messages);
                } else {
                    $('#add-unit-messages').html('<div class="alert alert-danger alert-dismissible" role="alert">' +
                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                        response.messages + '</div>');
                }
            },
            error: function() {
                $('#add-unit-messages').html('<div class="alert alert-danger alert-dismissible" role="alert">' +
                    '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                    'Error occurred while adding unit.</div>');
            }
        });
    });

    // Add Tax Form Submission
    $('#addTaxForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#addTaxModal').modal('hide');
                    $('#addTaxForm')[0].reset();
                    taxTable.ajax.reload(null, false);
                    showAlert('success', response.messages);
                } else {
                    showAlert('error', response.messages);
                }
            },
            error: function() {
                showAlert('error', 'Error occurred while processing request');
            }
        });
    });

    // Currency Settings Form Submission
    $('#currencySettingsForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: 'php_action/updateCurrencySettings.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.messages);
                } else {
                    showAlert('error', response.messages);
                }
            },
            error: function() {
                showAlert('error', 'Error occurred while updating currency settings');
            }
        });
    });

    // Load existing settings
    loadCurrencySettings();
    loadCompanyInfo();

    // Add custom CSS styles
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .dataTables_wrapper .row {
                margin: 10px 0;
            }
            .table > thead > tr > th {
                vertical-align: middle;
            }
            .table > tbody > tr > td {
                vertical-align: middle;
            }
            .btn-group {
                display: flex;
                justify-content: center;
                gap: 5px;
            }
            .label {
                display: inline-block;
                min-width: 60px;
                text-align: center;
            }
            .table-striped > tbody > tr:nth-of-type(odd) {
                background-color: #f9f9f9;
            }
            .table-bordered > thead > tr > th {
                border-bottom-width: 1px;
                background-color: #f5f5f5;
            }
            .dataTables_length {
                padding-left: 15px;
            }
            .dataTables_filter {
                padding-right: 15px;
            }
            .dataTables_info {
                padding-left: 15px;
            }
            .dataTables_paginate {
                padding-right: 15px;
            }
        `)
        .appendTo('head');
});

// Function to load currency settings
function loadCurrencySettings() {
    $.ajax({
        url: 'php_action/fetchCurrencySettings.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                var settings = response.data;
                $('select[name="default_currency"]').val(settings.default_currency);
                $('select[name="currency_position"]').val(settings.currency_position);
                $('input[name="thousand_separator"]').val(settings.thousand_separator);
                $('input[name="decimal_separator"]').val(settings.decimal_separator);
                $('input[name="decimals"]').val(settings.decimals);
            }
        }
    });
}

// Function to load company information
function loadCompanyInfo() {
    $.ajax({
        url: 'php_action/fetchCompanyInfo.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                var info = response.data;
                $('input[name="company_name"]').val(info.company_name);
                $('textarea[name="company_address"]').val(info.company_address);
                $('input[name="company_phone"]').val(info.company_phone);
                $('input[name="company_email"]').val(info.company_email);
                $('input[name="tax_number"]').val(info.tax_number);
                if (info.company_logo) {
                    $('.company-logo').attr('src', info.company_logo);
                }
            }
        }
    });
}

// Function to edit category
function editCategory(id, table) {
    $.ajax({
        url: 'php_action/editCategory.php',
        type: 'GET',
        data: { id: id, category_table: table },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                $('#editCategoryForm input[name="id"]').val(id);
                $('#editCategoryForm input[name="category_table"]').val(table);

                // Hide and disable all sections
                $('.category-form-section').hide().find(':input').prop('disabled', true);

                // Show and enable the relevant section
                var $section = $('#edit-form-' + table);
                $section.show().find(':input').prop('disabled', false);

                // Populate fields for each table
                if (table === 'categories') {
                    $section.find('input[name="name"]').val(response.data.name);
                    $section.find('select[name="type"]').val(response.data.type);
                    $section.find('textarea[name="description"]').val(response.data.description);
                    $section.find('select[name="status"]').val(response.data.status);
                } else if (table === 'digital_categories') {
                    $section.find('input[name="category_name"]').val(response.data.category_name);
                    $section.find('textarea[name="description"]').val(response.data.description);
                } else if (table === 'payment_categories') {
                    $section.find('input[name="name"]').val(response.data.name);
                    $section.find('textarea[name="description"]').val(response.data.description);
                    $section.find('input[name="type"]').val(response.data.type);
                    $section.find('select[name="status"]').val(response.data.status);
                } else if (table === 'production_categories' || table === 'raw_material_categories') {
                    $section.find('input[name="name"]').val(response.data.name);
                    $section.find('textarea[name="description"]').val(response.data.description);
                    $section.find('select[name="status"]').val(response.data.status);
                } else if (table === 'system_config_categories') {
                    $section.find('input[name="category_name"]').val(response.data.category_name);
                    $section.find('textarea[name="description"]').val(response.data.description);
                    $section.find('input[name="display_order"]').val(response.data.display_order);
                }

                $('#editCategoryModal').modal('show');
            } else {
                showAlert('error', response.message);
            }
        },
        error: function() {
            showAlert('error', 'Error occurred while fetching category data');
        }
    });
}

// Function to delete category
function deleteCategory(id, table) {
    if(confirm('Are you sure you want to delete this category?')) {
        $.ajax({
            url: 'php_action/deleteCategory.php',
            type: 'POST',
            data: { id: id, category_table: table },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#categoriesTable').DataTable().ajax.reload(null, false);
                    showAlert('success', response.message);
                } else {
                    showAlert('error', response.message);
                }
            },
            error: function() {
                showAlert('error', 'Error occurred while deleting category');
            }
        });
    }
}

// Handle edit category form submission
$('#editCategoryForm').on('submit', function(e) {
    e.preventDefault();
    
    $.ajax({
        url: 'php_action/editCategory.php',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                $('#editCategoryModal').modal('hide');
                $('#categoriesTable').DataTable().ajax.reload(null, false);
                showAlert('success', response.message);
            } else {
                showAlert('error', response.message);
            }
        },
        error: function() {
            showAlert('error', 'Error occurred while updating category');
        }
    });
});

// Edit Brand
function editBrand(id, table) {
    $.ajax({
        url: 'php_action/editBrand.php',
        type: 'GET',
        data: { id: id, table_name: table },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                $('#editBrandForm input[name="id"]').val(id);
                $('#editBrandForm input[name="table_name"]').val(table);
                $('#editBrandForm input[name="name"]').val(response.data.name);
                $('#editBrandForm textarea[name="description"]').val(response.data.description);
                $('#editBrandForm select[name="status"]').val(response.data.status);
                $('#editBrandModal').modal('show');
            } else {
                alert(response.messages);
            }
        },
        error: function() {
            alert('Error occurred while fetching brand data');
        }
    });
}

// Delete Brand
function deleteBrand(id, table) {
    if(confirm('Are you sure you want to delete this brand?')) {
        $.ajax({
            url: 'php_action/deleteBrand.php',
            type: 'POST',
            data: { id: id, table_name: table },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    alert('Brand deleted successfully');
                    location.reload();
                } else {
                    alert(response.messages);
                }
            },
            error: function() {
                alert('Error occurred while deleting brand');
            }
        });
    }
}

// Handle Edit Brand Form Submission
$('#editBrandForm').submit(function(e) {
    e.preventDefault();
    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                $('#editBrandModal').modal('hide');
                alert('Brand updated successfully');
                location.reload();
            } else {
                alert(response.messages);
            }
        },
        error: function() {
            alert('Error occurred while updating brand');
        }
    });
});

// Remove the old editUnit function and replace with unified logic
function editUnit(id) {
    $.ajax({
        url: 'php_action/fetchSelectedUnit.php',
        type: 'POST',
        data: {id: id},
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                // Fill the form with unit data (use correct selectors)
                $('#editUnitId').val(response.data.id);
                $('#editName').val(response.data.name);
                $('#editAbbreviation').val(response.data.abbreviation);
                $('#editDescription').val(response.data.description);
                $('#editStatus').val(response.data.status);
                // Show the modal
                $('#editUnitModal').modal('show');
            } else {
                showAlert('error', response.messages);
            }
        },
        error: function() {
            showAlert('error', 'Error occurred while fetching unit data');
        }
    });
}

// Update deleteUnit to use manageUnitsTable for reload
function deleteUnit(id) {
    if(confirm('Are you sure you want to delete this unit?')) {
        $.ajax({
            url: 'php_action/deleteUnit.php',
            type: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    if (typeof manageUnitsTable !== 'undefined') {
                        manageUnitsTable.ajax.reload(null, false);
                    } else {
                        $('#manageUnitsTable').DataTable().ajax.reload(null, false);
                    }
                    showAlert('success', response.messages);
                } else {
                    showAlert('error', response.messages);
                }
            },
            error: function() {
                showAlert('error', 'Error occurred while deleting unit');
            }
        });
    }
}

// Update add/edit unit form handlers to use manageUnitsTable for reload
$(document).ready(function() {
    // ... existing code ...
    $('#editUnitForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#editUnitModal').modal('hide');
                    if (typeof manageUnitsTable !== 'undefined') {
                        manageUnitsTable.ajax.reload(null, false);
                    } else {
                        $('#manageUnitsTable').DataTable().ajax.reload(null, false);
                    }
                    showAlert('success', response.messages);
                } else {
                    $('#edit-unit-messages').html('<div class="alert alert-danger alert-dismissible" role="alert">' +
                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                        response.messages + '</div>');
                }
            },
            error: function() {
                $('#edit-unit-messages').html('<div class="alert alert-danger alert-dismissible" role="alert">' +
                    '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                    'Error occurred while updating unit.</div>');
            }
        });
    });
});

// Function to edit tax
function editTax(id) {
    $.ajax({
        url: 'php_action/editTax.php',
        type: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                $('#editTaxModal').modal('show');
                $('#editTaxForm input[name="id"]').val(response.data.id);
                $('#editTaxForm input[name="name"]').val(response.data.name);
                $('#editTaxForm input[name="rate"]').val(response.data.rate);
                $('#editTaxForm select[name="type"]').val(response.data.type);
                $('#editTaxForm select[name="status"]').val(response.data.status);
            } else {
                showAlert('error', response.messages);
            }
        },
        error: function() {
            showAlert('error', 'Error occurred while fetching tax rate data');
        }
    });
}

// Function to delete tax
function deleteTax(id) {
    if(confirm('Are you sure you want to delete this tax rate?')) {
        $.ajax({
            url: 'php_action/deleteTax.php',
            type: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#taxTable').DataTable().ajax.reload(null, false);
                    showAlert('success', response.messages);
                } else {
                    showAlert('error', response.messages);
                }
            },
            error: function() {
                showAlert('error', 'Error occurred while deleting tax rate');
            }
        });
    }
}

// Handle Edit Tax Form Submission
$('#editTaxForm').submit(function(e) {
    e.preventDefault();
    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                $('#editTaxModal').modal('hide');
                $('#taxTable').DataTable().ajax.reload(null, false);
                showAlert('success', response.messages);
            } else {
                showAlert('error', response.messages);
            }
        },
        error: function() {
            showAlert('error', 'Error occurred while updating tax rate');
        }
    });
});

// Function to show alerts
function showAlert(type, message) {
    var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    var alert = '<div class="alert ' + alertClass + ' alert-dismissible" role="alert">' +
                '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                '<span aria-hidden="true">&times;</span></button>' + message + '</div>';
    
    $('.page-heading').after(alert);
    setTimeout(function() {
        $('.alert').fadeOut('slow', function() {
            $(this).remove();
        });
    }, 3000);
} 