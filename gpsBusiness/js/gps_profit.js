var gpsProfitTable;
var profitChart;

$(document).ready(function() {
    // Initialize Date Range Picker
    $('#profitDateRange').daterangepicker({
        startDate: moment().startOf('month'),
        endDate: moment().endOf('month'),
        locale: {
            format: 'YYYY-MM-DD'
        },
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    });

    // Handle date range change
    $('#profitDateRange').on('apply.daterangepicker', function(ev, picker) {
        fetchProfitData(picker.startDate.format('YYYY-MM-DD'), picker.endDate.format('YYYY-MM-DD'));
    });

    // Initialize DataTable
    gpsProfitTable = $('#gpsProfitTable').DataTable({
        'ajax': {
            'url': 'php_action/fetchGpsProfit.php',
            'type': 'POST',
            'data': function(d) {
                var dateRange = $('#profitDateRange').val().split(' - ');
                d.startDate = dateRange[0];
                d.endDate = dateRange[1];
            }
        },
        'order': [[0, 'desc']],
        'columns': [
            { data: 'date', render: function(data) { return moment(data).format('DD-MM-YYYY'); } },
            { data: 'total_sales', render: $.fn.dataTable.render.number(',', '.', 2, '') },
            { data: 'total_purchase', render: $.fn.dataTable.render.number(',', '.', 2, '') },
            { data: 'total_credit', render: $.fn.dataTable.render.number(',', '.', 2, '') },
            { data: 'total_expenses', render: $.fn.dataTable.render.number(',', '.', 2, '') },
            { data: 'total_investment', render: $.fn.dataTable.render.number(',', '.', 2, '') },
            { data: 'gross_profit', render: function(data) { 
                return '<span class="' + (data >= 0 ? 'text-success' : 'text-danger') + '">' + 
                       $.fn.dataTable.render.number(',', '.', 2, '')(Math.abs(data)) + '</span>'; 
            }},
            { data: 'net_profit', render: function(data) { 
                return '<span class="' + (data >= 0 ? 'text-success' : 'text-danger') + '">' + 
                       $.fn.dataTable.render.number(',', '.', 2, '')(Math.abs(data)) + '</span>'; 
            }}
        ]
    });

    // Initialize Profit Chart
    var ctx = document.getElementById('profitChart').getContext('2d');
    profitChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Gross Profit',
                borderColor: '#00a65a',
                fill: false,
                data: []
            }, {
                label: 'Net Profit',
                borderColor: '#00c0ef',
                fill: false,
                data: []
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString('en-US', { minimumFractionDigits: 2 });
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let value = context.parsed.y;
                            let sign = value >= 0 ? '+' : '-';
                            return context.dataset.label + ': ' + sign + Math.abs(value).toLocaleString('en-US', { minimumFractionDigits: 2 });
                        }
                    }
                }
            }
        }
    });

    // Fetch data for the selected date range
    function fetchProfitData(startDate, endDate) {
        $.ajax({
            url: 'php_action/fetchProfitData.php',
            type: 'POST',
            data: {
                startDate: startDate,
                endDate: endDate
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update summary cards with formatted numbers
                    $('#totalSalesAmount').text(response.summary.totalSales.toLocaleString('en-US', { minimumFractionDigits: 2 }));
                    $('#totalPurchaseAmount').text(response.summary.totalPurchase.toLocaleString('en-US', { minimumFractionDigits: 2 }));
                    $('#totalExpenseAmount').text(response.summary.totalExpenses.toLocaleString('en-US', { minimumFractionDigits: 2 }));
                    $('#totalCreditAmount').text(response.summary.totalCredit.toLocaleString('en-US', { minimumFractionDigits: 2 }));
                    $('#totalInvestmentAmount').text(response.summary.totalInvestment.toLocaleString('en-US', { minimumFractionDigits: 2 }));
                    
                    // Update profit amounts with color coding
                    let grossProfitElement = $('#grossProfitAmount');
                    let netProfitElement = $('#netProfitAmount');
                    
                    grossProfitElement
                        .text(Math.abs(response.summary.grossProfit).toLocaleString('en-US', { minimumFractionDigits: 2 }))
                        .removeClass('text-success text-danger')
                        .addClass(response.summary.grossProfit >= 0 ? 'text-success' : 'text-danger');
                    
                    netProfitElement
                        .text(Math.abs(response.summary.netProfit).toLocaleString('en-US', { minimumFractionDigits: 2 }))
                        .removeClass('text-success text-danger')
                        .addClass(response.summary.netProfit >= 0 ? 'text-success' : 'text-danger');

                    // Update chart
                    profitChart.data.labels = response.chart.labels;
                    profitChart.data.datasets[0].data = response.chart.grossProfit;
                    profitChart.data.datasets[1].data = response.chart.netProfit;
                    profitChart.update();

                    // Refresh table
                    gpsProfitTable.ajax.reload();
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: response.messages || 'Could not fetch profit data',
                        icon: 'error'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'Error',
                    text: 'Could not fetch profit data',
                    icon: 'error'
                });
            }
        });
    }

    // Generate Report
    $('#generateReport').on('click', function() {
        var dateRange = $('#profitDateRange').val().split(' - ');
        window.location.href = 'php_action/generateProfitReport.php?startDate=' + dateRange[0] + '&endDate=' + dateRange[1];
    });

    // Initialize with current month's data
    var now = moment();
    fetchProfitData(now.startOf('month').format('YYYY-MM-DD'), now.endOf('month').format('YYYY-MM-DD'));
}); 