document.addEventListener('DOMContentLoaded', function() {
    // Single instance of charts
    let transactionChart = null;
    let platformChart = null;

    function destroyCharts() {
        if (transactionChart) {
            transactionChart.destroy();
            transactionChart = null;
        }
        if (platformChart) {
            platformChart.destroy();
            platformChart = null;
        }
    }

    function initializeCharts() {
        // Clean up existing charts first
        destroyCharts();

        // Transaction Volume Chart
        const transactionCtx = document.getElementById('transactionChart');
        if (transactionCtx) {
            transactionChart = new Chart(transactionCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: []
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false, // Disable animations
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        // Platform Distribution Chart
        const platformCtx = document.getElementById('platformChart');
        if (platformCtx) {
            platformChart = new Chart(platformCtx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: []
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    scales: {
                        x: {
                            stacked: true
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.dataset.label || '';
                                    const value = context.parsed.y || 0;
                                    return `${label}: $${value.toLocaleString()}`;
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    function updateCharts() {
        // Get filter values
        const startDate = $('input[name="start_date"]').val();
        const endDate = $('input[name="end_date"]').val();
        const accountId = $('#account_id').val();

        // Update Transaction Volume Chart
        $.ajax({
            url: 'php_action/getTransactionChartData.php',
            type: 'GET',
            data: {
                start_date: startDate,
                end_date: endDate,
                account_id: accountId,
                period: 'daily' // Default to daily view
            },
            success: function(response) {
                if (transactionChart) {
                    transactionChart.data = response;
                    transactionChart.update();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error updating transaction chart:', error);
            }
        });

        // Update Platform Distribution Chart
        $.ajax({
            url: 'php_action/getPlatformChartData.php',
            type: 'GET',
            data: {
                start_date: startDate,
                end_date: endDate,
                account_id: accountId
            },
            success: function(response) {
                if (platformChart) {
                    platformChart.data = response;
                    platformChart.update();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error updating platform chart:', error);
            }
        });
    }

    // Initialize charts when document is ready
    $(document).ready(function() {
        initializeCharts();
        updateCharts(); // Initial load

        // Period buttons click handler
        $('.btn-group button[data-period]').click(function() {
            $('.btn-group button').removeClass('active');
            $(this).addClass('active');
            updateCharts();
        });
    });

    // Event Listeners with debounce
    let updateTimeout;
    function debouncedUpdate() {
        clearTimeout(updateTimeout);
        updateTimeout = setTimeout(updateCharts, 250);
    }

    $('#account_id, input[name="start_date"], input[name="end_date"]').on('change', debouncedUpdate);
    
    $('.btn-group button').on('click', function() {
        $('.btn-group button').removeClass('active');
        $(this).addClass('active');
        debouncedUpdate();
    });

    // Initial update
    updateCharts();

    // Clean up on page unload
    window.addEventListener('unload', destroyCharts);

    // Add this to your existing JavaScript file
    let transactionTable = null;

    function initializeDataTable() {
        if ($.fn.DataTable.isDataTable('#transactionTable')) {
            $('#transactionTable').DataTable().destroy();
        }

        $('#transactionTable').DataTable({
            processing: true,
            serverSide: false, // Changed to false for simpler implementation
            ajax: {
                url: 'php_action/fetchSwapTransactions.php',
                type: 'GET',
                data: function(d) {
                    return {
                        ...d,
                        start_date: $('input[name="start_date"]').val(),
                        end_date: $('input[name="end_date"]').val(),
                        account_id: $('#account_id').val()
                    };
                },
                dataSrc: function(json) {
                    console.log('Received data:', json); // Debug log
                    return json.data || [];
                }
            },
            columns: [
                { data: 'transaction_date' },
                { data: 'account' },
                { data: 'platform' },
                { data: 'type', render: function(data) { return data || ''; } },
                { data: 'amount', render: function(data) { return data || ''; } },
                { data: 'status', render: function(data) { return data || ''; } },
                { data: 'actions', render: function(data) { return data || ''; } }
            ],
            order: [[0, 'desc']],
            pageLength: 10,
            responsive: true,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'csv',
                    text: '<i class="fas fa-download"></i> Export CSV',
                    className: 'btn btn-sm btn-outline-secondary',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5] // Exclude actions column
                    }
                }
            ]
        });
    }

    // Add event listeners for table actions
    $('#transactionTable').on('click', '.btn-view', function() {
        const id = $(this).data('id');
        viewTransaction(id);
    });

    $('#transactionTable').on('click', '.btn-edit', function() {
        const id = $(this).data('id');
        editTransaction(id);
    });

    $('#transactionTable').on('click', '.btn-delete', function() {
        const id = $(this).data('id');
        if (confirm('Are you sure you want to delete this transaction?')) {
            deleteTransaction(id);
        }
    });

    // Initialize everything when document is ready
    $(document).ready(function() {
        initializeDataTable();
        
        // Update table when filters change
        $('#reportFilter').on('submit', function(e) {
            e.preventDefault();
            $('#transactionTable').DataTable().ajax.reload();
        });

        // Add export function
        window.exportTableToCSV = function() {
            $('.buttons-csv').click();
        };
    });

    // Transaction action functions
    function viewTransaction(id) {
        // Implement view logic
        console.log('View transaction:', id);
    }

    function editTransaction(id) {
        // Implement edit logic
        console.log('Edit transaction:', id);
    }

    function deleteTransaction(id) {
        if (confirm('Are you sure you want to delete this transaction?')) {
            // Implement delete logic
            console.log('Delete transaction:', id);
        }
    }

    // Update dashboard function
    function updateDashboard() {
        if (transactionTable) {
            transactionTable.ajax.reload(null, false);
        }
        updateCharts();
    }
}); 