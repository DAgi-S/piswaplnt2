$(document).ready(function() {
    // Initialize DataTable
    var warehousesTable = $('#warehousesTable').DataTable({
        'ajax': {
            'url': 'php_action/fetchWarehouses.php',
            'type': 'POST'
        },
        'order': [],
        'columns': [
            { data: 'code' },
            { data: 'name' },
            { data: 'type' },
            { data: 'location' },
            { data: 'status' },
            { data: 'stock_items' },
            { data: 'action' }
        ],
        'pageLength': 10,
        'responsive': true,
        'dom': 'Bfrtip',
        'buttons': ['copy', 'csv', 'excel', 'pdf', 'print']
    });

    // Add Warehouse Form Submit
    $('#addWarehouseForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: 'php_action/createWarehouse.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#addWarehouseModal').modal('hide');
                    $('#addWarehouseForm')[0].reset();
                    warehousesTable.ajax.reload(null, false);
                    
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
                    text: 'There was an error processing your request'
                });
            }
        });
    });

    // Edit Warehouse
    window.editWarehouse = function(id) {
        $.ajax({
            url: 'php_action/fetchSingleWarehouse.php',
            type: 'POST',
            data: {id: id},
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#editWarehouseId').val(response.data.id);
                    $('#editCode').val(response.data.code);
                    $('#editName').val(response.data.name);
                    $('#editType').val(response.data.type);
                    $('#editLocation').val(response.data.location);
                    $('#editDescription').val(response.data.description);
                    
                    $('#editWarehouseModal').modal('show');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.messages
                    });
                }
            }
        });
    };

    // Edit Warehouse Form Submit
    $('#editWarehouseForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: 'php_action/editWarehouse.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#editWarehouseModal').modal('hide');
                    warehousesTable.ajax.reload(null, false);
                    
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
            }
        });
    });

    // Change Warehouse Status
    window.changeWarehouseStatus = function(id, status) {
        Swal.fire({
            title: 'Are you sure?',
            text: "Do you want to change this warehouse's status?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, change it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'php_action/changeWarehouseStatus.php',
                    type: 'POST',
                    data: {
                        id: id,
                        status: status
                    },
                    dataType: 'json',
                    success: function(response) {
                        if(response.success) {
                            warehousesTable.ajax.reload(null, false);
                            Swal.fire(
                                'Changed!',
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
                    }
                });
            }
        });
    };

    // View Stock
    window.viewStock = function(id) {
        $.ajax({
            url: 'php_action/fetchWarehouseStock.php',
            type: 'POST',
            data: {id: id},
            success: function(response) {
                $('#stockModalBody').html(response);
                $('#viewStockModal').modal('show');
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error fetching stock information'
                });
            }
        });
    };
}); 