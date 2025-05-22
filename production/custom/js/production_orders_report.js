$(document).ready(function() {
    // Initialize toastr
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 5000
    };

    // Initialize select2 for product dropdown
    $('#product').select2();

    // Initialize date inputs
    const today = new Date().toISOString().split('T')[0];
    $('#startDate').val(today);
    $('#endDate').val(today);

    // Initialize DataTable
    let ordersTable = $('#ordersTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel"></i> Excel',
                className: 'btn btn-success btn-sm',
                exportOptions: {
                    columns: ':visible:not(.no-export)'
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf"></i> PDF',
                className: 'btn btn-danger btn-sm',
                exportOptions: {
                    columns: ':visible:not(.no-export)'
                }
            },
            {
                extend: 'print',
                text: '<i class="fas fa-print"></i> Print',
                className: 'btn btn-info btn-sm',
                exportOptions: {
                    columns: ':visible:not(.no-export)'
                }
            }
        ],
        ajax: {
            url: "php_action/fetchProductionOrdersReport.php",
            type: "POST",
            data: function(d) {
                return {
                    ...getFilterParams(),
                    draw: d.draw
                };
            },
            dataSrc: function(json) {
                if (!json.success) {
                    toastr.error(json.error || 'Failed to fetch data');
                    return [];
                }
                updateSummaryCards(json.data);
                return json.data;
            },
            error: function(xhr, error, thrown) {
                toastr.error('Error loading data: ' + error);
            }
        },
        columns: [
            { data: "order_number" },
            { data: "product_code" },
            { data: "product_name" },
            { data: "target_quantity" },
            { data: "completed_quantity" },
            { data: "start_date" },
            { data: "expected_completion" },
            { data: "actual_completion" },
            { 
                data: "effective_status",
                render: function(data, type, row) {
                    let statusClass = data === 'completed' ? 'success' : 
                                    data === 'in_progress' ? 'primary' :
                                    data === 'delayed' ? 'danger' : 'warning';
                    return `<span class="badge badge-${statusClass}">${data}</span>`;
                }
            },
            { 
                data: "efficiency",
                render: function(data, type, row) {
                    return `<span class="${row.efficiency_class}">${data}%</span>`;
                }
            },
            {
                data: null,
                className: "no-export",
                render: function(data, type, row) {
                    return `
                        <button class="btn btn-info btn-sm view-details" data-order="${row.order_number}">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-primary btn-sm print-order" data-order="${row.order_number}">
                            <i class="fas fa-print"></i>
                        </button>
                    `;
                }
            }
        ],
        order: [[0, 'desc']],
        responsive: true,
        pageLength: 25
    });

    // Handle filter form submission
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        ordersTable.ajax.reload();
    });

    // Handle date range selection
    $('#dateRange').on('change', function() {
        const range = $(this).val();
        const today = new Date();
        let startDate = today;
        let endDate = today;

        switch(range) {
            case 'yesterday':
                startDate = new Date(today.setDate(today.getDate() - 1));
                endDate = startDate;
                break;
            case 'last7days':
                startDate = new Date(today.setDate(today.getDate() - 7));
                break;
            case 'last30days':
                startDate = new Date(today.setDate(today.getDate() - 30));
                break;
            case 'thisMonth':
                startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                break;
            case 'lastMonth':
                startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                endDate = new Date(today.getFullYear(), today.getMonth(), 0);
                break;
            case 'custom':
                $('#dateInputs').show();
                return;
            default: // today
                break;
        }

        if (range !== 'custom') {
            $('#dateInputs').hide();
            $('#startDate').val(startDate.toISOString().split('T')[0]);
            $('#endDate').val(endDate.toISOString().split('T')[0]);
            ordersTable.ajax.reload();
        }
    });

    // Helper function to get filter parameters
    function getFilterParams() {
        const dateRange = $('#dateRange').val();
        let params = {
            dateRange: dateRange,
            product: $('#product').val(),
            orderStatus: $('#orderStatus').val()
        };

        if (dateRange === 'custom') {
            const startDate = $('#startDate').val();
            const endDate = $('#endDate').val();

            if (!startDate || !endDate) {
                toastr.error('Please select both start and end dates for custom range');
                return false;
            }

            if (startDate > endDate) {
                toastr.error('Start date cannot be after end date');
                return false;
            }

            params.startDate = startDate;
            params.endDate = endDate;
        }

        return params;
    }

    // Function to update summary cards
    function updateSummaryCards(data) {
        let totalOrders = data.length;
        let completedOrders = data.filter(order => order.status === 'completed').length;
        let inProgressOrders = data.filter(order => order.status === 'in_progress').length;
        let delayedOrders = data.filter(order => order.effective_status === 'delayed').length;

        $('#totalOrders').text(totalOrders);
        $('#completedOrders').text(completedOrders);
        $('#inProgressOrders').text(inProgressOrders);
        $('#delayedOrders').text(delayedOrders);
    }

    // Handle view details button click
    $(document).on('click', '.view-details', function() {
        const orderNumber = $(this).data('order');
        $('#orderDetailsModal').modal('show');
        // Load order details via AJAX
        $.ajax({
            url: 'php_action/getOrderDetails.php',
            type: 'POST',
            data: { orderNumber: orderNumber },
            success: function(response) {
                if (response.success) {
                    $('#orderDetailsContent').html(response.html);
                } else {
                    toastr.error(response.error || 'Failed to load order details');
                }
            },
            error: function(xhr, status, error) {
                toastr.error('Error loading order details: ' + error);
            }
        });
    });

    // Handle print order button click
    $(document).on('click', '.print-order', function() {
        const orderNumber = $(this).data('order');
        window.open(`print_order.php?order=${orderNumber}`, '_blank');
    });
}); 