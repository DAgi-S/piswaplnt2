// Global variable for the DataTable instance
var manageOrderTable = null;

// Function to initialize the DataTable
function initializeDataTable() {
    // If DataTable exists, destroy it first
    if ($.fn.DataTable.isDataTable('#manageOrderTable')) {
        $('#manageOrderTable').DataTable().destroy();
    }
    
    // Clear the table contents
    $('#manageOrderTable tbody').empty();
    
    // Initialize new DataTable
    manageOrderTable = $('#manageOrderTable').DataTable({
        'ajax': 'php_action/fetchOrders.php',
        'order': [[1, 'desc']], // Order by date descending by default
        'columnDefs': [
            {
                "targets": [0], // Order ID column
                "orderable": true,
                "searchable": true
            },
            {
                "targets": [1], // Date column
                "orderable": true,
                "searchable": true
            },
            {
                "targets": [2], // FS Number column
                "orderable": true,
                "searchable": true
            },
            {
                "targets": [3], // Client Name column
                "orderable": true,
                "searchable": true
            },
            {
                "targets": [4], // Contact column
                "orderable": true,
                "searchable": true
            },
            {
                "targets": [5], // Grand Total column
                "orderable": true,
                "searchable": true
            },
            {
                "targets": [6], // Payment Status column
                "orderable": true,
                "searchable": true
            },
            {
                "targets": [7], // Actions column
                "orderable": false,
                "searchable": false
            }
        ],
        "columns": [
            {"data": "order_id"},
            {"data": "order_date"},
            {"data": "fsnum"},
            {"data": "client_name"},
            {"data": "client_contact"},
            {
                "data": "grand_total",
                "render": function(data, type, row) {
                    if(type === 'display') {
                        let num = parseFloat(data);
                        if(isNaN(num)) return '0.00';
                        // Format with commas and fixed decimal places
                        let parts = num.toFixed(2).split('.');
                        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                        return parts.join('.');
                    }
                    return parseFloat(data) || 0; // For sorting/filtering
                }
            },
            {
                "data": "payment_status",
                "render": function(data, type, row) {
                    if(data == 1) {
                        return '<span class="label label-success">Full Payment</span>';
                    } else if(data == 2) {
                        return '<span class="label label-info">Advance Payment</span>';
                    } else {
                        return '<span class="label label-warning">No Payment</span>';
                    }
                }
            },
            {
                "data": "order_id",
                "render": function(data, type, row) {
                    var buttons = '<div class="btn-group">';
                    
                    // View button
                    buttons += '<button type="button" class="btn btn-info btn-sm" onclick="viewOrder('+data+')">' +
                              '<i class="glyphicon glyphicon-eye-open"></i></button>';
                    
                    // Edit button
                    buttons += '<a href="orders.php?o=editOrd&i='+data+'" class="btn btn-warning btn-sm">' +
                              '<i class="glyphicon glyphicon-edit"></i></a>';
                    
                    // Print button
                    buttons += '<button type="button" class="btn btn-primary btn-sm" onclick="printOrder('+data+')">' +
                              '<i class="glyphicon glyphicon-print"></i></button>';
                    
                    // Remove button
                    buttons += '<button type="button" class="btn btn-danger btn-sm" onclick="removeOrder('+data+')">' +
                              '<i class="glyphicon glyphicon-trash"></i></button>';
                    
                    buttons += '</div>';
                    return buttons;
                }
            }
        ]
    });
}

// Initialize when document is ready
$(document).ready(function() {
    // Only initialize if the table exists
    if ($('#manageOrderTable').length) {
        initializeDataTable();
    }
}); 