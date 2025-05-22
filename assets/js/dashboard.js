$(document).ready(function() {
    // Initialize DataTable
    const transactionTable = $('#transactionTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: 'php_action/fetchSwapTransactions.php',
            type: 'POST',
            data: function(d) {
                return {
                    start_date: $('input[name="start_date"]').val(),
                    end_date: $('input[name="end_date"]').val(),
                    account_id: $('#account_id').val()
                };
            }
        },
        columns: [
            { data: 'transaction_date' },
            { data: 'account_owner' },
            { data: 'platform' },
            { 
                data: 'type',
                render: function(data) {
                    return `<span class="badge ${data === 'deposit' ? 'badge-success' : 'badge-warning'}">${data}</span>`;
                }
            },
            { 
                data: 'amount',
                render: function(data) {
                    return '$' + data;
                }
            },
            {
                data: 'status',
                render: function(data) {
                    return `<span class="badge ${data == 1 ? 'badge-success' : 'badge-danger'}">${data == 1 ? 'Active' : 'Inactive'}</span>`;
                }
            },
            {
                data: 'id',
                render: function(data) {
                    return `
                        <button class="btn btn-sm btn-info" onclick="viewTransactionDetails(${data})">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-primary" onclick="editTransactionDetails(${data})">
                            <i class="fas fa-edit"></i>
                        </button>
                    `;
                }
            }
        ],
        order: [[0, 'desc']],
        responsive: true
    });

    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap4'
    });

    // Initialize Charts
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    drawBorder: false
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    };

    window.transactionChart = new Chart(
        document.getElementById('transactionChart').getContext('2d'),
        {
            type: 'line',
            data: { labels: [], datasets: [] },
            options: {
                ...chartOptions,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                }
            }
        }
    );

    window.platformChart = new Chart(
        document.getElementById('platformChart').getContext('2d'),
        {
            type: 'doughnut',
            data: { labels: [], datasets: [] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        }
    );

    // Update data on filter change
    $('#account_id, input[name="start_date"], input[name="end_date"]').on('change', function() {
        updateDashboard();
    });

    // Initial load
    updateDashboard();
});

function updateDashboard() {
    updateMetrics();
    updateCharts();
    $('#transactionTable').DataTable().ajax.reload();
}

function updateMetrics() {
    const params = new URLSearchParams({
        start_date: $('input[name="start_date"]').val(),
        end_date: $('input[name="end_date"]').val(),
        account_id: $('#account_id').val()
    });

    fetch('php_action/getSwapMetrics.php?' + params)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Metrics error:', data.error);
                return;
            }
            Object.keys(data).forEach(key => {
                const element = document.getElementById(key);
                if (element) {
                    let value = data[key];
                    if (typeof value === 'number' && key.includes('amount') || key.includes('total')) {
                        value = '$' + value.toLocaleString(undefined, {minimumFractionDigits: 2});
                    }
                    element.textContent = value;
                }
            });
        })
        .catch(error => console.error('Error updating metrics:', error));
}

function updateCharts() {
    const params = new URLSearchParams({
        start_date: $('input[name="start_date"]').val(),
        end_date: $('input[name="end_date"]').val(),
        account_id: $('#account_id').val()
    });

    // Update Transaction Volume Chart
    fetch('php_action/getTransactionChartData.php?' + params)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Chart error:', data.error);
                return;
            }
            window.transactionChart.data = data;
            window.transactionChart.update();
        })
        .catch(error => console.error('Error updating transaction chart:', error));

    // Update Platform Distribution Chart
    fetch('php_action/getPlatformChartData.php?' + params)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Chart error:', data.error);
                return;
            }
            window.platformChart.data = data;
            window.platformChart.update();
        })
        .catch(error => console.error('Error updating platform chart:', error));
} 