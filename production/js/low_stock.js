$(document).ready(function() {
    // Initialize DataTable
    var table = $('#lowStockTable').DataTable({
        "processing": true,
        "serverSide": false,
        "ajax": {
            "url": "php_action/fetchLowStock.php",
            "type": "GET",
            "dataSrc": function(json) {
                if (json.error) {
                    console.error('Server Error:', json.message);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: json.message || 'An error occurred while loading the data.'
                    });
                    return [];
                }
                
                // Update counts before returning data
                if (typeof updateStockCounts === 'function') {
                    try {
                        updateStockCounts(json.data);
                    } catch (e) {
                        console.error('Error updating stock counts:', e);
                    }
                }
                
                return json.data || [];
            },
            "error": function(xhr, error, thrown) {
                console.error('DataTables Ajax Error:', error, thrown);
                console.log('XHR Response:', xhr.responseText);
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error Loading Data',
                    text: 'Failed to load stock data. Please try refreshing the page.'
                });
            }
        },
        "columns": [
            {"data": "id"},
            {"data": "name"},
            {"data": "type"},
            {"data": "warehouse"},
            {"data": "current_stock"},
            {"data": "minimum_stock"},
            {"data": "reorder_point"},
            {
                "data": "status",
                "render": function(data, type, row) {
                    if (type === 'display') {
                        return data;  // Status is already formatted as HTML
                    }
                    return $(data).text();  // Strip HTML for sorting/filtering
                }
            },
            {"data": "action", "orderable": false}
        ],
        "order": [[3, "asc"]],
        "pageLength": 25,
        "dom": '<"row"<"col-sm-4"l><"col-sm-4 text-center"B><"col-sm-4"f>>rtip',
        "buttons": [
            {
                extend: 'collection',
                text: '<i class="fa fa-download"></i> Export',
                buttons: ['copy', 'excel', 'csv', 'pdf', 'print']
            }
        ],
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "language": {
            "emptyTable": "No low stock items found",
            "zeroRecords": "No matching records found",
            "processing": '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span>'
        }
    });

    // Update stock counts in the dashboard
    function updateStockCounts(data) {
        var outOfStock = 0;
        var criticalStock = 0;
        var lowStock = 0;

        data.forEach(function(item) {
            if (parseFloat(item.current_stock) <= 0) {
                outOfStock++;
            } else if (parseFloat(item.current_stock) <= parseFloat(item.minimum_stock) * 0.5) {
                criticalStock++;
            } else {
                lowStock++;
            }
        });

        $('#outOfStockCount').text(outOfStock);
        $('#criticalStockCount').text(criticalStock);
        $('#lowStockCount').text(lowStock);
        $('#totalItemCount').text(data.length);
    }

    // Handle View Details button click
    $('#lowStockTable').on('click', '.view-details', function() {
        var button = $(this);
        var id = button.data('id');
        var type = button.data('type');
        var name = button.data('name');
        
        // Show item details in a modal
        Swal.fire({
            title: name,
            html: `
                <table class="table table-bordered">
                    <tr>
                        <th>ID</th>
                        <td>${type === 'product' ? 'P' : 'R'}${id}</td>
                    </tr>
                    <tr>
                        <th>Type</th>
                        <td>${type === 'product' ? 'Product' : 'Raw Material'}</td>
                    </tr>
                    <tr>
                        <th>Current Stock</th>
                        <td>${$(button).closest('tr').find('td:eq(4)').text()}</td>
                    </tr>
                    <tr>
                        <th>Minimum Stock</th>
                        <td>${$(button).closest('tr').find('td:eq(5)').text()}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>${$(button).closest('tr').find('td:eq(7)').html()}</td>
                    </tr>
                </table>
            `,
            icon: 'info',
            width: '600px'
        });
    });

    // Refresh table data every 5 minutes
    setInterval(function() {
        table.ajax.reload(null, false);
    }, 300000);

    // Handle Threshold Settings Form Submission
    $('#saveThresholdSettings').click(function() {
        // Show loading state
        $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

        // Get form data
        var formData = {
            threshold_buffer: $('#thresholdBuffer').val(),
            auto_calculate_thresholds: $('#autoCalculateThresholds').is(':checked') ? 1 : 0,
            email_notifications: $('#emailNotifications').is(':checked') ? 1 : 0,
            alert_frequency: $('#alertFrequency').val()
        };

        // Send AJAX request
        $.ajax({
            url: 'php_action/saveThresholdSettings.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.messages[0],
                        timer: 2000,
                        showConfirmButton: false
                    });

                    // Close modal
                    $('#thresholdSettingsModal').modal('hide');

                    // Refresh table
                    table.ajax.reload();
                } else {
                    // Show error message
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.messages[0]
                    });
                }
            },
            error: function() {
                // Show error message
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while saving settings'
                });
            },
            complete: function() {
                // Reset button state
                $('#saveThresholdSettings').prop('disabled', false).html('Save Changes');
            }
        });
    });

    // Load Current Settings
    function loadThresholdSettings() {
        $.ajax({
            url: 'php_action/getThresholdSettings.php',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    $('#thresholdBuffer').val(response.data.threshold_buffer);
                    $('#autoCalculateThresholds').prop('checked', response.data.auto_calculate_thresholds == 1);
                    $('#emailNotifications').prop('checked', response.data.email_notifications == 1);
                    $('#alertFrequency').val(response.data.alert_frequency);
                }
            }
        });
    }

    // Load settings when modal is opened
    $('#thresholdSettingsModal').on('show.bs.modal', function() {
        loadThresholdSettings();
    });

    // Handle Add Stock button click
    $('.add-stock').click(function() {
        var itemId = $(this).data('id');
        $('#itemId').val(itemId);
        $('#addStockModal').modal('show');
    });

    // Handle View Trend button click
    $('.view-trend').click(function() {
        var itemId = $(this).data('id');
        loadStockTrend(itemId);
        $('#stockTrendModal').modal('show');
    });
});

// View Material Details
function viewMaterial(id) {
    $.ajax({
        url: 'php_action/fetchSingleMaterial.php',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                var data = response.data;
                var stockStatus = '';
                var statusClass = '';
                
                if(parseFloat(data.current_stock) === 0) {
                    stockStatus = 'OUT OF STOCK';
                    statusClass = 'label-critical';
                } else if(parseFloat(data.current_stock) <= parseFloat(data.minimum_stock) * 0.5) {
                    stockStatus = 'CRITICAL';
                    statusClass = 'label-critical';
                } else if(parseFloat(data.current_stock) <= parseFloat(data.minimum_stock)) {
                    stockStatus = 'LOW';
                    statusClass = 'label-warning';
                }
                
                var html = `
                    <table class="table table-bordered">
                        <tr>
                            <th style="width:35%">Code</th>
                            <td>${data.code}</td>
                        </tr>
                        <tr>
                            <th>Name</th>
                            <td>${data.name}</td>
                        </tr>
                        <tr>
                            <th>Category</th>
                            <td>${data.category}</td>
                        </tr>
                        <tr>
                            <th>Current Stock</th>
                            <td class="stock-qty">${parseFloat(data.current_stock).toFixed(2)} ${data.unit}</td>
                        </tr>
                        <tr>
                            <th>Minimum Stock Level</th>
                            <td>${parseFloat(data.minimum_stock).toFixed(2)} ${data.unit}</td>
                        </tr>
                        <tr>
                            <th>Stock Status</th>
                            <td><span class="label ${statusClass} stock-status">${stockStatus}</span></td>
                        </tr>
                        <tr>
                            <th>Description</th>
                            <td>${data.description || '-'}</td>
                        </tr>
                        <tr>
                            <th>Last Updated</th>
                            <td>${moment(data.updated_at).format('DD/MM/YYYY HH:mm:ss')}</td>
                        </tr>
                    </table>
                `;
                
                $('#materialDetails').html(html);
                $('#viewMaterialModal').modal('show');
            } else {
                toastr.error(response.messages);
            }
        },
        error: function() {
            toastr.error('Error fetching material details');
        }
    });
}

// Create Inbound Movement
function createInbound(id) {
    // Get material details first
    $.ajax({
        url: 'php_action/fetchSingleMaterial.php',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                // Pre-select the material in the inbound form
                $('#inboundMaterialId').val(id).trigger('change');
                
                // Show the inbound modal
                $('#addInboundModal').modal('show');
            } else {
                toastr.error(response.messages);
            }
        },
        error: function() {
            toastr.error('Error fetching material details');
        }
    });
}

// Export to PDF
function exportToPDF() {
    // Get table data
    var data = $('#lowStockTable').DataTable().data().toArray();
    
    // Create PDF content
    var docDefinition = {
        pageOrientation: 'landscape',
        content: [
            { text: 'Low Stock Report', style: 'header' },
            { text: 'Generated on: ' + moment().format('DD/MM/YYYY HH:mm:ss'), style: 'subheader' },
            {
                table: {
                    headerRows: 1,
                    widths: ['auto', '*', 'auto', 'auto', 'auto', 'auto', 'auto'],
                    body: [
                        ['Code', 'Material', 'Category', 'Current Stock', 'Min Stock', 'Unit', 'Status'],
                        ...data.map(item => [
                            item.code,
                            item.name,
                            item.category,
                            parseFloat(item.current_stock).toFixed(2),
                            parseFloat(item.minimum_stock).toFixed(2),
                            item.unit,
                            item.current_stock <= item.minimum_stock * 0.5 ? 'CRITICAL' : 'LOW'
                        ])
                    ]
                }
            }
        ],
        styles: {
            header: {
                fontSize: 18,
                bold: true,
                margin: [0, 0, 0, 10]
            },
            subheader: {
                fontSize: 12,
                margin: [0, 0, 0, 10]
            }
        }
    };
    
    // Generate and download PDF
    pdfMake.createPdf(docDefinition).download('low_stock_report.pdf');
}

// Export to Excel
function exportToExcel() {
    // Get table data
    var data = $('#lowStockTable').DataTable().data().toArray();
    
    // Create worksheet
    var ws = XLSX.utils.json_to_sheet(data.map(item => ({
        'Code': item.code,
        'Material': item.name,
        'Category': item.category,
        'Current Stock': parseFloat(item.current_stock).toFixed(2),
        'Min Stock Level': parseFloat(item.minimum_stock).toFixed(2),
        'Unit': item.unit,
        'Status': item.current_stock <= item.minimum_stock * 0.5 ? 'CRITICAL' : 'LOW'
    })));
    
    // Create workbook
    var wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Low Stock");
    
    // Generate and download Excel file
    XLSX.writeFile(wb, 'low_stock_report.xlsx');
} 