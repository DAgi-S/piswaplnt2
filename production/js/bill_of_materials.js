// Shared functions for material selection
function initializeMaterialSelect(element, materials = null) {
    var $element = $(element);
    var selectedOption = $element.find('option:selected');
    var initialData = null;
    
    if (selectedOption.length && selectedOption.val()) {
        initialData = {
            id: selectedOption.val(),
            material_code: selectedOption.data('material-code'),
            name: selectedOption.data('name'),
            unit: selectedOption.data('unit'),
            text: selectedOption.text()
        };
    }

    // Find the visible modal
    var $visibleModal = $('.modal:visible');
    
    var select2Config = {
        dropdownParent: $visibleModal.length ? $visibleModal : $('body'),
        width: '100%',
        placeholder: 'Select Raw Material',
        allowClear: true,
        minimumInputLength: 0,
        ajax: {
            url: 'php_action/fetchRawMaterialAtBom.php',
            type: 'POST',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    search: params.term || '',
                    fetch_all: !params.term
                };
            },
            processResults: function(data) {
                if (data.success) {
                    return {
                        results: data.data.map(function(item) {
                            return {
                                id: item.id,
                                text: item.material_code + ' - ' + item.name,
                                material_code: item.material_code,
                                name: item.name,
                                unit: item.unit,
                                current_stock: item.current_stock,
                                cost_per_unit: item.cost_per_unit
                            };
                        })
                    };
                }
                return { results: [] };
            },
            cache: true
        },
        templateResult: formatMaterial,
        templateSelection: formatMaterialSelection
    };

    // If materials data is provided, use it instead of AJAX
    if (materials) {
        select2Config.data = materials;
    }
    
    // Destroy existing Select2 if it exists
    if ($element.data('select2')) {
        $element.select2('destroy');
    }
    
    $element.select2(select2Config).on('select2:select', function(e) {
        var data = e.params.data;
        $(this).closest('tr').find('.material-unit').text(data.unit || '-');
    }).on('select2:unselect', function() {
        $(this).closest('tr').find('.material-unit').text('-');
    });

    // Set initial data if exists
    if (initialData) {
        $element.closest('tr').find('.material-unit').text(initialData.unit || '-');
    }
}

// Format material display in dropdown
function formatMaterial(material) {
    if (material.loading) return material.text;
    if (!material.id) return material.text;
    
    var stock = material.current_stock || '0.00';
    var stockClass = parseFloat(stock) <= 0 ? 'text-danger' : 'text-success';
    
    return $(`<div class="material-item">
        <strong>${material.material_code} - ${material.name}</strong><br>
        <small>Stock: <span class="${stockClass}">${stock} ${material.unit}</span></small>
    </div>`);
}

// Format selected material display
function formatMaterialSelection(material) {
    if (!material.id) return material.text;
    return material.material_code + ' - ' + material.name;
}

// Edit BOM function
function editBOM(id) {
    $.ajax({
        url: 'php_action/fetchSingleBOM.php',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                // Load edit modal content
                $('#editBOMModal .modal-content').html(response.html);
                $('#editBOMModal').modal('show');
                
                // Initialize Select2 in edit modal
                $('#editBOMModal .material-select').each(function() {
                    initializeMaterialSelect(this, response.materials);
                });

                // Handle add material row in edit modal
                $('#addEditMaterialRow').on('click', function() {
                    var newRow = `
                        <tr>
                            <td>
                                <select class="form-control material-select" name="materials[]" required>
                                    <option value="">Select Raw Material</option>
                                </select>
                            </td>
                            <td>
                                <input type="number" class="form-control quantity-required" name="quantities[]" step="0.01" min="0.01" required />
                            </td>
                            <td class="material-unit">-</td>
                            <td>
                                <input type="number" class="form-control wastage-percent" name="wastage[]" step="0.01" min="0" max="100" value="0" required />
                            </td>
                            <td>
                                <button type="button" class="btn btn-danger btn-sm remove-material">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>`;
                    
                    var $newRow = $(newRow);
                    $('#editBomMaterialsTable tbody').append($newRow);
                    
                    // Initialize Select2 for the new row with the same materials data
                    initializeMaterialSelect($newRow.find('.material-select'), response.materials);
                });

                // Handle form submission
                $('#editBOMForm').off('submit').on('submit', function(e) {
                    e.preventDefault();
                    
                    // Validate form
                    var isValid = true;
                    $(this).find('[required]').each(function() {
                        if(!$(this).val()) {
                            isValid = false;
                            $(this).addClass('error');
                        } else {
                            $(this).removeClass('error');
                        }
                    });
                    
                    if(!isValid) {
                        toastr.error('Please fill in all required fields');
                        return false;
                    }
                    
                    // Show loading state
                    $('#updateBOMBtn')
                        .prop('disabled', true)
                        .html('<i class="fa fa-spinner fa-spin"></i> Updating...');
                    
                    $.ajax({
                        url: $(this).attr('action'),
                        type: 'POST',
                        data: $(this).serialize(),
                        dataType: 'json',
                        success: function(response) {
                            if(response.success) {
                                // Close modal
                                $('#editBOMModal').modal('hide');
                                
                                // Reload DataTable
                                $('#bomTable').DataTable().ajax.reload();
                                
                                // Show success message
                                toastr.success('Bill of Materials updated successfully');
                            } else {
                                toastr.error(response.messages);
                            }
                        },
                        error: function(xhr, status, error) {
                            toastr.error('An error occurred while updating the bill of materials');
                            console.error('Error:', error);
                        },
                        complete: function() {
                            // Reset button state
                            $('#updateBOMBtn')
                                .prop('disabled', false)
                                .html('Update BOM');
                        }
                    });
                });

                // Handle remove material in edit modal
                $('#editBomMaterialsTable').on('click', '.remove-material', function() {
                    var tbody = $(this).closest('tbody');
                    if(tbody.find('tr').length > 1) {
                        $(this).closest('tr').remove();
                    } else {
                        toastr.warning('At least one material is required');
                    }
                });
            } else {
                toastr.error(response.messages);
            }
        },
        error: function(xhr, status, error) {
            toastr.error('Error fetching BOM details');
            console.error('Error:', error);
        }
    });
}

// Change BOM Status function
function changeBOMStatus(id, status) {
    if (!id || !status) {
        toastr.error('Invalid request parameters');
        return;
    }

    Swal.fire({
        title: 'Are you sure?',
        text: "Do you want to " + (status === 'active' ? 'activate' : 'deactivate') + " this BOM?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, proceed!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'php_action/changeBOMStatus.php',
                type: 'POST',
                data: {
                    id: id,
                    status: status
                },
                dataType: 'json',
                beforeSend: function() {
                    // Show loading state
                    Swal.fire({
                        title: 'Processing...',
                        text: 'Please wait while we update the status.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                },
                success: function(response) {
                    if(response.success) {
                        // Reload only the table data
                        $('#bomTable').DataTable().ajax.reload(null, false);
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.messages || 'Status updated successfully'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.messages || 'Failed to update status'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    console.error('Response:', xhr.responseText);
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'An error occurred while updating the status. Please try again.'
                    });
                }
            });
        }
    });
}

// Document ready function
$(document).ready(function() {
    // Initialize DataTable
    var bomTable = $('#bomTable').DataTable({
        "processing": true,
        "serverSide": false,
        "ajax": {
            "url": "php_action/fetchBOM.php",
            "type": "POST"
        },
        "columns": [
            { "data": 0 }, // Product
            { "data": 1 }, // Raw Material
            { "data": 2 }, // Quantity Required
            { "data": 3 }, // Unit
            { "data": 4 }, // Wastage %
            { "data": 5 }, // Status
            { "data": 6 }  // Action
        ],
        "order": [[0, "asc"]],
        "pageLength": 10,
        "responsive": true
    });

    // Initialize Select2 for product selection
    $('.product-select').select2({
        placeholder: 'Select Product',
        allowClear: true,
        dropdownParent: $('#addBOMModal'),
        width: '100%'
    });

    // Initialize Select2 for material selection
    function initializeMaterialSelect(element) {
        var $element = $(element);
        
        // Destroy existing Select2 if it exists
        if ($element.data('select2')) {
            $element.select2('destroy');
        }
        
        $element.select2({
            placeholder: 'Select Raw Material',
            allowClear: true,
            dropdownParent: $('#addBOMModal'),
            width: '100%'
        }).on('change', function() {
            var selectedOption = $(this).find('option:selected');
            var unit = selectedOption.data('unit') || '-';
            $(this).closest('tr').find('.material-unit').text(unit);
        });
    }

    // Initialize all material selects
    $('.material-select').each(function() {
        initializeMaterialSelect(this);
    });

    // Handle Add Material Row
    $('#addMaterialRow').on('click', function() {
        var $tbody = $('#bomMaterialsTable tbody');
        var $firstRow = $tbody.find('tr:first');
        var $newRow = $firstRow.clone();
        
        // Clean up the cloned row
        $newRow.find('select.material-select')
            .val('')
            .removeData('select2')
            .next('.select2-container').remove();
            
        $newRow.find('input[type="number"]').val('');
        $newRow.find('.material-unit').text('-');
        
        // Append the new row
        $tbody.append($newRow);
        
        // Initialize Select2 on the new row's select
        initializeMaterialSelect($newRow.find('.material-select'));
    });

    // Handle Remove Material Row
    $('#bomMaterialsTable').on('click', '.remove-material', function() {
        var $tbody = $(this).closest('tbody');
        if ($tbody.find('tr').length > 1) {
            $(this).closest('tr').remove();
        } else {
            alert('At least one material is required');
        }
    });

    // Handle Form Submission
    $('#submitBOMForm').on('submit', function(e) {
        e.preventDefault();
        
        // Validate form
        var isValid = true;
        $(this).find('[required]').each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('error');
            } else {
                $(this).removeClass('error');
            }
        });
        
        if (!isValid) {
            alert('Please fill in all required fields');
            return false;
        }
        
        // Show loading state
        $('#createBOMBtn')
            .prop('disabled', true)
            .html('<i class="fa fa-spinner fa-spin"></i> Creating...');
        
        // Submit form via AJAX
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Reset form
                    $('#submitBOMForm')[0].reset();
                    
                    // Clean up all rows except first
                    $('#bomMaterialsTable tbody tr:not(:first)').remove();
                    
                    // Reset first row's select
                    var $firstSelect = $('#bomMaterialsTable tbody tr:first .material-select');
                    $firstSelect.val('').trigger('change');
                    
                    // Close modal and reload table
                    $('#addBOMModal').modal('hide');
                    bomTable.ajax.reload();
                    
                    alert(response.messages || 'Bill of Materials created successfully');
                } else {
                    alert(response.messages || 'An error occurred');
                }
            },
            error: function() {
                alert('An error occurred while creating the bill of materials');
            },
            complete: function() {
                $('#createBOMBtn')
                    .prop('disabled', false)
                    .html('Create BOM');
            }
        });
    });

    // Reset form when modal is hidden
    $('#addBOMModal').on('hidden.bs.modal', function() {
        $('#submitBOMForm')[0].reset();
        $('#bomMaterialsTable tbody tr:not(:first)').remove();
        var $firstSelect = $('#bomMaterialsTable tbody tr:first .material-select');
        $firstSelect.val('').trigger('change');
    });
});

// Status management function
function manageStatus(id, currentStatus) {
    var newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    if (confirm('Are you sure you want to ' + (newStatus === 'active' ? 'activate' : 'deactivate') + ' this BOM?')) {
        $.ajax({
            url: 'php_action/changeBOMStatus.php',
            type: 'POST',
            data: { id: id, status: newStatus },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#bomTable').DataTable().ajax.reload();
                    alert(response.messages || 'Status updated successfully');
                } else {
                    alert(response.messages || 'Failed to update status');
                }
            },
            error: function() {
                alert('An error occurred while updating the status');
            }
        });
    }
} 