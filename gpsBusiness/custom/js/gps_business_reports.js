$(document).ready(function() {
    // Initialize variables for charts
    let profitLossTrendChart = null;
    let revenueDistributionChart = null;
    let salesTrendChart = null;
    let topCustomersChart = null;
    let expenseTrendChart = null;
    let expenseDistributionChart = null;
    let distributionTrendChart = null;
    let investorDistributionChart = null;

    // Initialize DataTables
    const profitLossTable = $('#profitLossTable').DataTable({
        'processing': true,
        'serverSide': false,
        'ajax': {
            'url': 'php_action/fetchProfitLossData.php',
            'type': 'POST',
            'data': function(d) {
                return {
                    ...d,
                    start_date: getCurrentDateRange('profitLoss').start,
                    end_date: getCurrentDateRange('profitLoss').end,
                    cycle_id: $('#profitLossCycle').val(),
                    currency: $('#profitLossCurrency').val()
                };
            }
        },
        'columns': [
            { 'data': 'date' },
            { 'data': 'cycle_number' },
            { 
                'data': 'revenue',
                'render': function(data, type, row) {
                    return formatCurrency(data, row.currency);
                }
            },
            { 
                'data': 'cost_of_sales',
                'render': function(data, type, row) {
                    return formatCurrency(data, row.currency);
                }
            },
            { 
                'data': 'gross_profit',
                'render': function(data, type, row) {
                    return formatCurrency(data, row.currency);
                }
            },
            { 
                'data': 'expenses',
                'render': function(data, type, row) {
                    return formatCurrency(data, row.currency);
                }
            },
            { 
                'data': 'net_profit',
                'render': function(data, type, row) {
                    const profit = parseFloat(data);
                    const formattedValue = formatCurrency(Math.abs(profit), row.currency);
                    return `<span class="${profit >= 0 ? 'text-success' : 'text-danger'}">${formattedValue}</span>`;
                }
            }
        ],
        'order': [[0, 'desc']],
        'pageLength': 10,
        'responsive': true,
        'dom': 'Bfrtip',
        'buttons': ['copy', 'csv', 'excel', 'pdf', 'print']
    });

    const salesTable = $('#salesTable').DataTable({
        'processing': true,
        'serverSide': false,
        'ajax': {
            'url': 'php_action/fetchSalesData.php',
            'type': 'POST',
            'data': function(d) {
                const dateRange = $('#salesDateRange').val().split(' - ');
                return {
                    ...d,
                    start_date: dateRange[0],
                    end_date: dateRange[1],
                    cycle_id: $('#salesCycle').val(),
                    currency: $('#salesCurrency').val()
                };
            }
        },
        'columns': [
            { 'data': 'sale_date' },
            { 'data': 'cycle_number' },
            { 'data': 'buyer_name' },
            { 'data': 'quantity' },
            { 
                'data': 'unit_price',
                'render': function(data, type, row) {
                    return formatCurrency(data);
                }
            },
            { 
                'data': 'total',
                'render': function(data, type, row) {
                    return formatCurrency(data);
                }
            },
            { 'data': 'status' }
        ],
        'order': [[0, 'desc']],
        'pageLength': 10,
        'responsive': true,
        'dom': 'Bfrtip',
        'buttons': ['copy', 'csv', 'excel', 'pdf', 'print']
    });

    const expenseTable = $('#expenseTable').DataTable({
        'processing': true,
        'serverSide': false,
        'ajax': {
            'url': 'php_action/fetchExpenseData.php',
            'type': 'POST',
            'data': function(d) {
                return {
                    ...d,
                    start_date: getCurrentDateRange('expense').start,
                    end_date: getCurrentDateRange('expense').end,
                    cycle_id: $('#expenseCycle').val(),
                    expense_type: $('#expenseType').val()
                };
            }
        },
        'columns': [
            { 'data': 'expense_date' },
            { 'data': 'cycle_number' },
            { 'data': 'description' },
            { 'data': 'expense_type' },
            { 
                'data': 'amount_etb',
                'render': function(data) {
                    return formatCurrency(data, 'ETB');
                }
            },
            { 'data': 'payment_method' },
            { 'data': 'reference_number' }
        ],
        'order': [[0, 'desc']],
        'pageLength': 10,
        'responsive': true,
        'dom': 'Bfrtip',
        'buttons': ['copy', 'csv', 'excel', 'pdf', 'print']
    });

    const distributionTable = $('#distributionTable').DataTable({
        'processing': true,
        'serverSide': false,
        'ajax': {
            'url': 'php_action/fetchInvestorData.php',
            'type': 'POST',
            'data': function(d) {
                return {
                    ...d,
                    start_date: getCurrentDateRange('investor').start,
                    end_date: getCurrentDateRange('investor').end,
                    investor_id: $('#investorSelect').val(),
                    status: $('#investorStatus').val()
                };
            }
        },
        'columns': [
            { 'data': 'distribution_date' },
            { 'data': 'cycle_number' },
            { 'data': 'investor_name' },
            { 
                'data': 'share_percentage',
                'render': function(data) {
                    return data + '%';
                }
            },
            { 
                'data': 'amount_etb',
                'render': function(data) {
                    return formatCurrency(data, 'ETB');
                }
            },
            { 
                'data': 'status',
                'render': function(data) {
                    return `<span class="label label-${data === 'completed' ? 'success' : 'warning'}">${data}</span>`;
                }
            },
            { 'data': 'reinvested' }
        ],
        'order': [[0, 'desc']],
        'pageLength': 10,
        'responsive': true,
        'dom': 'Bfrtip',
        'buttons': ['copy', 'csv', 'excel', 'pdf', 'print']
    });

    // Load business cycles
    function loadBusinessCycles() {
        $.ajax({
            url: 'php_action/fetchBusinessCycles.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const cycles = ['profitLossCycle', 'salesCycle', 'expenseCycle'];
                    cycles.forEach(function(selectId) {
                        const select = $(`#${selectId}`);
                        select.empty().append('<option value="">All Cycles</option>');
                        response.data.forEach(function(cycle) {
                            select.append(`<option value="${cycle.id}">${cycle.cycle_number}</option>`);
                        });
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading business cycles:', error);
                showAlert('danger', 'Error loading business cycles. Please try again.');
            }
        });
    }

    // Initialize charts
    function initCharts() {
        // Profit & Loss Trend Chart
        const profitLossTrendCtx = document.getElementById('profitLossTrendChart').getContext('2d');
        profitLossTrendChart = new Chart(profitLossTrendCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Revenue',
                        borderColor: '#00c0ef',
                        data: []
                    },
                    {
                        label: 'Gross Profit',
                        borderColor: '#00a65a',
                        data: []
                    },
                    {
                        label: 'Net Profit',
                        borderColor: '#f56954',
                        data: []
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Revenue Distribution Chart
        const revenueDistributionCtx = document.getElementById('revenueDistributionChart').getContext('2d');
        revenueDistributionChart = new Chart(revenueDistributionCtx, {
            type: 'doughnut',
            data: {
                labels: [],
                datasets: [{
                    data: [],
                    backgroundColor: ['#00c0ef', '#00a65a', '#f39c12', '#f56954']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Sales Trend Chart
        const salesTrendCtx = document.getElementById('salesTrendChart');
        if (salesTrendCtx) {
            salesTrendChart = new Chart(salesTrendCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [
                        {
                            label: 'Sales Amount',
                            borderColor: '#00c0ef',
                            data: []
                        },
                        {
                            label: 'Quantity',
                            borderColor: '#00a65a',
                            data: []
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return formatCurrency(value);
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    if (context.dataset.label === 'Quantity') {
                                        return context.dataset.label + ': ' + context.parsed.y;
                                    }
                                    return context.dataset.label + ': ' + formatCurrency(context.parsed.y);
                                }
                            }
                        }
                    }
                }
            });
        }

        // Top Customers Chart
        const topCustomersCtx = document.getElementById('topCustomersChart');
        if (topCustomersCtx) {
            topCustomersChart = new Chart(topCustomersCtx, {
                type: 'pie',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: ['#00c0ef', '#00a65a', '#f39c12', '#f56954', '#3c8dbc']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = formatCurrency(context.parsed);
                                    return label + ': ' + value;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Expense Trend Chart
        const expenseTrendCtx = document.getElementById('expenseTrendChart').getContext('2d');
        expenseTrendChart = new Chart(expenseTrendCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Expenses',
                    borderColor: '#f56954',
                    data: []
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Expense Distribution Chart
        const expenseDistributionCtx = document.getElementById('expenseDistributionChart').getContext('2d');
        expenseDistributionChart = new Chart(expenseDistributionCtx, {
            type: 'doughnut',
            data: {
                labels: [],
                datasets: [{
                    data: [],
                    backgroundColor: ['#00c0ef', '#00a65a', '#f39c12', '#f56954']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Distribution Trend Chart
        const distributionTrendCtx = document.getElementById('distributionTrendChart').getContext('2d');
        distributionTrendChart = new Chart(distributionTrendCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Distributions',
                    borderColor: '#00a65a',
                    data: []
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Investor Distribution Chart
        const investorDistributionCtx = document.getElementById('investorDistributionChart').getContext('2d');
        investorDistributionChart = new Chart(investorDistributionCtx, {
            type: 'pie',
            data: {
                labels: [],
                datasets: [{
                    data: [],
                    backgroundColor: ['#00c0ef', '#00a65a', '#f39c12', '#f56954', '#3c8dbc']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    // Generate reports
    function generateProfitLossReport() {
        $.ajax({
            url: 'php_action/fetchReportData.php',
            type: 'POST',
            data: {
                start_date: getCurrentDateRange('profitLoss').start,
                end_date: getCurrentDateRange('profitLoss').end,
                cycle_id: $('#profitLossCycle').val(),
                currency: $('#profitLossCurrency').val()
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    updateProfitLossSummary(response.data.summary);
                    updateProfitLossCharts(response.data.charts);
                    profitLossTable.ajax.reload();
                } else {
                    showAlert('danger', response.messages || ['Error generating profit & loss report']);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error generating profit & loss report:', error);
                showAlert('danger', 'Error generating profit & loss report. Please try again.');
            }
        });
    }

    function generateSalesReport() {
        $.ajax({
            url: 'php_action/fetchSalesData.php',
            type: 'POST',
            data: {
                start_date: getCurrentDateRange('sales').start,
                end_date: getCurrentDateRange('sales').end,
                cycle_id: $('#salesCycle').val(),
                currency: $('#salesCurrency').val()
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update summary cards
                    updateSalesSummary(response.data.summary);
                    
                    // Update charts
                    if (salesTrendChart && response.data.charts.trend) {
                        salesTrendChart.data.labels = response.data.charts.trend.labels;
                        salesTrendChart.data.datasets[0].data = response.data.charts.trend.sales;
                        salesTrendChart.data.datasets[1].data = response.data.charts.trend.quantity;
                        salesTrendChart.update();
                    }
                    
                    if (topCustomersChart && response.data.charts.customers) {
                        topCustomersChart.data.labels = response.data.charts.customers.labels;
                        topCustomersChart.data.datasets[0].data = response.data.charts.customers.values;
                        topCustomersChart.update();
                    }
                    
                    // Reload table data
                    if (salesTable) {
                        salesTable.clear().rows.add(response.data.table).draw();
                    }
                } else {
                    showAlert('danger', response.messages || ['Error generating sales report']);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error generating sales report:', error);
                showAlert('danger', 'Error generating sales report. Please try again.');
            }
        });
    }

    function generateExpenseReport() {
        $.ajax({
            url: 'php_action/fetchExpenseData.php',
            type: 'POST',
            data: {
                start_date: getCurrentDateRange('expense').start,
                end_date: getCurrentDateRange('expense').end,
                cycle_id: $('#expenseCycle').val(),
                expense_type: $('#expenseType').val()
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    updateExpenseSummary(response.data.summary);
                    updateExpenseCharts(response.data.charts);
                    expenseTable.ajax.reload();
                } else {
                    showAlert('danger', response.messages || ['Error generating expense report']);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error generating expense report:', error);
                showAlert('danger', 'Error generating expense report. Please try again.');
            }
        });
    }

    function generateInvestorReport() {
        $.ajax({
            url: 'php_action/fetchInvestorData.php',
            type: 'POST',
            data: {
                start_date: getCurrentDateRange('investor').start,
                end_date: getCurrentDateRange('investor').end,
                investor_id: $('#investorSelect').val(),
                status: $('#investorStatus').val()
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    updateInvestorSummary(response.data.summary);
                    updateInvestorCharts(response.data.charts);
                    distributionTable.ajax.reload();
                } else {
                    showAlert('danger', response.messages || ['Error generating investor report']);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error generating investor report:', error);
                showAlert('danger', 'Error generating investor report. Please try again.');
            }
        });
    }

    // Update summary cards
    function updateProfitLossSummary(summary) {
        $('#totalRevenue').text(formatCurrency(summary.total_revenue, summary.currency));
        $('#grossProfit').text(formatCurrency(summary.gross_profit, summary.currency));
        $('#totalExpenses').text(formatCurrency(summary.total_expenses, summary.currency));
        $('#netProfit').text(formatCurrency(summary.net_profit, summary.currency));
    }

    function updateSalesSummary(summary) {
        $('#totalSales').text(formatCurrency(summary.total_amount, summary.currency));
        $('#totalQuantity').text(summary.total_quantity);
        $('#averageSaleAmount').text(formatCurrency(summary.average_sale, summary.currency));
        $('#totalCustomers').text(summary.total_customers);
    }

    function updateExpenseSummary(summary) {
        $('#totalExpensesAmount').text(formatCurrency(summary.total_amount, 'ETB'));
        $('#averageExpenseAmount').text(formatCurrency(summary.average_expense, 'ETB'));
        $('#totalExpenseCount').text(summary.total_expenses);
        $('#highestExpense').text(formatCurrency(summary.highest_expense, 'ETB'));
    }

    function updateInvestorSummary(summary) {
        $('#totalDistributed').text(formatCurrency(summary.total_distributed, 'ETB'));
        $('#averageDistribution').text(formatCurrency(summary.average_distribution, 'ETB'));
        $('#completedCount').text(summary.completed_count);
        $('#pendingCount').text(summary.pending_count);
    }

    // Update charts
    function updateProfitLossCharts(charts) {
        profitLossTrendChart.data.labels = charts.trend.labels;
        profitLossTrendChart.data.datasets[0].data = charts.trend.revenue;
        profitLossTrendChart.data.datasets[1].data = charts.trend.gross_profit;
        profitLossTrendChart.data.datasets[2].data = charts.trend.net_profit;
        profitLossTrendChart.update();

        revenueDistributionChart.data.labels = charts.distribution.labels;
        revenueDistributionChart.data.datasets[0].data = charts.distribution.values;
        revenueDistributionChart.update();
    }

    function updateSalesCharts(charts) {
        salesTrendChart.data.labels = charts.trend.labels;
        salesTrendChart.data.datasets[0].data = charts.trend.sales;
        salesTrendChart.data.datasets[1].data = charts.trend.quantity;
        salesTrendChart.update();

        topCustomersChart.data.labels = charts.customers.labels;
        topCustomersChart.data.datasets[0].data = charts.customers.values;
        topCustomersChart.update();
    }

    function updateExpenseCharts(charts) {
        expenseTrendChart.data.labels = charts.trend.labels;
        expenseTrendChart.data.datasets[0].data = charts.trend.expenses;
        expenseTrendChart.update();

        expenseDistributionChart.data.labels = charts.distribution.labels;
        expenseDistributionChart.data.datasets[0].data = charts.distribution.values;
        expenseDistributionChart.update();
    }

    function updateInvestorCharts(charts) {
        distributionTrendChart.data.labels = charts.trend.labels;
        distributionTrendChart.data.datasets[0].data = charts.trend.distributions;
        distributionTrendChart.update();

        investorDistributionChart.data.labels = charts.investors.labels;
        investorDistributionChart.data.datasets[0].data = charts.investors.values;
        investorDistributionChart.update();
    }

    // Handle date range changes
    function handleDateRangeChange(type) {
        const value = $(`#${type}DateRange`).val();
        if (value === 'custom') {
            $('#dateRangeModal').modal('show');
            $('#dateRangeModal').data('type', type);
        } else {
            generateReport(type);
        }
    }

    // Handle custom date range
    $('#applyDateRange').on('click', function() {
        const type = $('#dateRangeModal').data('type');
        const startDate = $('#customStartDate').val();
        const endDate = $('#customEndDate').val();

        if (!startDate || !endDate) {
            showAlert('danger', 'Please select both start and end dates');
            return;
        }

        setCurrentDateRange(type, startDate, endDate);
        $('#dateRangeModal').modal('hide');
        generateReport(type);
    });

    // Handle filter changes
    function handleFilterChange(type) {
        generateReport(type);
    }

    // Generate report based on type
    function generateReport(type) {
        switch(type) {
            case 'profitLoss':
                generateProfitLossReport();
                break;
            case 'sales':
                generateSalesReport();
                break;
            case 'expense':
                generateExpenseReport();
                break;
            case 'investor':
                generateInvestorReport();
                break;
        }
    }

    // Helper function to get current date range
    function getCurrentDateRange(type) {
        const value = $(`#${type}DateRange`).val();
        if (value === 'custom') {
            return {
                start: $('#customStartDate').val(),
                end: $('#customEndDate').val()
            };
        }
        return calculateDateRange(value);
    }

    // Helper function to set current date range
    function setCurrentDateRange(type, start, end) {
        $(`#${type}DateRange`).val('custom');
        $('#customStartDate').val(start);
        $('#customEndDate').val(end);
    }

    // Helper function to calculate date range
    function calculateDateRange(type) {
        const today = new Date();
        let start = new Date();
        let end = new Date();

        switch(type) {
            case 'this_month':
                start = new Date(today.getFullYear(), today.getMonth(), 1);
                end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                break;
            case 'last_month':
                start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                end = new Date(today.getFullYear(), today.getMonth(), 0);
                break;
            case 'this_quarter':
                const quarter = Math.floor(today.getMonth() / 3);
                start = new Date(today.getFullYear(), quarter * 3, 1);
                end = new Date(today.getFullYear(), (quarter + 1) * 3, 0);
                break;
            case 'last_quarter':
                const lastQuarter = Math.floor(today.getMonth() / 3) - 1;
                const year = lastQuarter < 0 ? today.getFullYear() - 1 : today.getFullYear();
                const quarterMonth = lastQuarter < 0 ? 3 : lastQuarter * 3;
                start = new Date(year, quarterMonth, 1);
                end = new Date(year, quarterMonth + 3, 0);
                break;
            case 'this_year':
                start = new Date(today.getFullYear(), 0, 1);
                end = new Date(today.getFullYear(), 11, 31);
                break;
            case 'last_year':
                start = new Date(today.getFullYear() - 1, 0, 1);
                end = new Date(today.getFullYear() - 1, 11, 31);
                break;
        }

        return {
            start: formatDate(start),
            end: formatDate(end)
        };
    }

    // Helper function to format currency
    function formatCurrency(amount, currency = 'ETB') {
        return currency + ' ' + parseFloat(amount).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    // Helper function to format date
    function formatDate(date) {
        return date.toISOString().split('T')[0];
    }

    // Helper function to show alerts
    function showAlert(type, messages) {
        let alertHtml = '<div class="alert alert-' + type + ' alert-dismissible">'+
            '<button type="button" class="close" data-dismiss="alert">&times;</button>';
        
        if (Array.isArray(messages)) {
            messages.forEach(function(message) {
                alertHtml += '<p><strong><i class="fas fa-' + (type === 'success' ? 'check' : 'times') + '"></i></strong> ' + message + '</p>';
            });
        } else {
            alertHtml += '<strong><i class="fas fa-' + (type === 'success' ? 'check' : 'times') + '"></i></strong> ' + messages;
        }
        
        alertHtml += '</div>';
        $('.remove-messages').html(alertHtml);
    }

    // Event listeners
    $('#profitLossDateRange').on('change', function() { handleDateRangeChange('profitLoss'); });
    $('#salesDateRange').on('change', function() { handleDateRangeChange('sales'); });
    $('#expenseDateRange').on('change', function() { handleDateRangeChange('expense'); });
    $('#investorDateRange').on('change', function() { handleDateRangeChange('investor'); });

    $('#profitLossCycle, #profitLossCurrency').on('change', function() { handleFilterChange('profitLoss'); });
    $('#salesCycle, #salesCurrency').on('change', function() { handleFilterChange('sales'); });
    $('#expenseCycle, #expenseType').on('change', function() { handleFilterChange('expense'); });
    $('#investorSelect, #investorStatus').on('change', function() { handleFilterChange('investor'); });

    $('#generateProfitLossReport').on('click', function() { generateReport('profitLoss'); });
    $('#generateSalesReport').on('click', function() { generateReport('sales'); });
    $('#generateExpenseReport').on('click', function() { generateReport('expense'); });
    $('#generateInvestorReport').on('click', function() { generateReport('investor'); });

    // Initialize components
    loadBusinessCycles();
    initCharts();
    
    // Set initial date ranges and generate reports
    const types = ['profitLoss', 'sales', 'expense', 'investor'];
    types.forEach(function(type) {
        setCurrentDateRange(type, calculateDateRange('this_month').start, calculateDateRange('this_month').end);
        generateReport(type);
    });
}); 