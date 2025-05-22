$(document).ready(function() {
    // Initialize charts
    let charts = {
        productsStock: null,
        materialsStock: null,
        productionTrend: null,
        efficiency: null,
        qualityMetrics: null,
        defectAnalysis: null,
        financial: null,
        dailyMovement: null,
        monthlyMovement: null
    };
    
    // Format number with commas and decimals
    function formatNumber(number, decimals = 2) {
        return number.toLocaleString('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }

    // Format currency with BR
    function formatCurrency(amount) {
        return 'Br ' + new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(amount);
    }

    // Update metrics display
    function updateMetrics(data) {
        // Production Products
        $('#totalProductionProducts').text(data.production_products.total_products);
        $('#activeProducts').text(data.production_products.active_products);
        $('#totalProductStock').text(formatNumber(data.production_products.total_stock));
        $('#totalProductValue').text(formatCurrency(data.production_products.total_stock_value));

        // Raw Materials
        $('#totalRawMaterials').text(data.raw_materials.total_materials);
        $('#activeMaterials').text(data.raw_materials.active_materials);
        $('#totalMaterialStock').text(formatNumber(data.raw_materials.total_stock));
        $('#totalMaterialValue').text(formatCurrency(data.raw_materials.total_stock_value));

        // Quality Rate
        $('#qualityRate').text(data.quality_rate + '%');
        $('#totalQualityChecks').text(data.quality.total_checks);
        $('#passedChecks').text(data.quality.passed_checks);

        // Sales Orders
        $('#totalSalesOrders').text(data.sales.total_orders);
        $('#completedSalesOrders').text(data.sales.completed_orders);
        $('#pendingSalesOrders').text(data.sales.pending_orders);
        $('#totalSalesAmount').text(formatCurrency(data.sales.total_amount));
        $('#totalPaidSales').text(formatCurrency(data.sales.total_paid));
        $('#totalSalesBalance').text(formatCurrency(data.sales.total_balance));

        // Purchases
        $('#totalPurchases').text(data.purchases.total_orders);
        $('#paidPurchases').text(data.purchases.paid_orders);
        $('#unpaidPurchases').text(data.purchases.unpaid_orders);
        $('#totalPurchaseAmount').text(formatCurrency(data.purchases.total_amount));
        $('#totalPaidPurchases').text(formatCurrency(data.purchases.total_paid));

        // Production Status
        $('#totalProductionOrders').text(data.production.total_orders);
        $('#completedProduction').text(data.production.completed_orders);
        $('#inProgressProduction').text(data.production.in_progress_orders);
        $('#productionEfficiency').text(data.production_efficiency + '%');

        // Net Revenue
        $('#netRevenue').text(formatCurrency(data.net_revenue));

        // VAT Information
        $('#totalSalesVAT').text(formatCurrency(data.sales.total_vat));
        $('#totalPurchaseVAT').text(formatCurrency(data.purchases.total_vat));
        $('#netVAT').text(formatCurrency(data.net_vat));

        // Update financial overview section
        $('#totalSales').text(formatCurrency(data.sales.total_amount));
        $('#totalPurchases').text(formatCurrency(data.purchases.total_amount));
        $('#netProfit').text(formatCurrency(data.net_revenue));

        // Update last refresh time
        $('#lastRefresh').text(new Date().toLocaleString());
    }

    // Fetch metrics data
    function fetchMetrics() {
        $.ajax({
            url: 'php_action/fetchAnnualReportMetrics.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    updateMetrics(response.data);
                } else {
                    console.error('Error fetching metrics:', response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax error:', error);
            }
        });
    }

    // Initialize metrics
    fetchMetrics();

    // Handle print button
    $('#printReport').on('click', function() {
        window.print();
    });

    // Handle PDF generation
    $('#generatePdf').on('click', function() {
        // Implementation for PDF generation
        // This can be added later based on your PDF generation requirements
    });

    // Set generated date
    $('#generatedDate').text(new Date().toLocaleString());
    $('#generatedBy').text($('#loggedInUserName').text() || 'System');

    // Refresh data every 5 minutes
    setInterval(fetchMetrics, 300000);

    // Fetch report data
    function fetchReportData() {
        // Fetch main metrics
        $.ajax({
            url: 'php_action/fetchAnnualReportMetrics.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    updateMetrics(response.data);
                }
            }
        });

        // Fetch inventory overview data
        $.ajax({
            url: 'php_action/fetchInventoryOverview.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    updateInventoryOverview(response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching inventory data:', error);
                // Show error in charts
                $('#productsStockChart, #materialsStockChart').each(function() {
                    $(this).parent().html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> Failed to load chart data
                        </div>
                    `);
                });
            }
        });

        // Fetch other report sections
        $.ajax({
            url: 'php_action/getAnnualReportData.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status) {
                    updateProductionTrendChart(response.data.production.trends);
                    updateEfficiencyChart(response.data.production.efficiency);
                    updateQualityMetricsChart(response.data.quality.metrics);
                    updateDefectAnalysisChart(response.data.quality.defects);
                    updateFinancialCharts(response.data.financial);
                    updateMovementCharts(response.data.movement);
                }
            }
        });
    }

    // Update report with data
    function updateReport(data) {
        // Update Executive Summary
        updateExecutiveSummary(data.summary);
        
        // Update Inventory Charts
        updateStockValueChart(data.inventory.stockValue);
        updateLowStockTable(data.inventory.lowStock);
        
        // Update Production Charts
        updateProductionTrendChart(data.production.trends);
        updateEfficiencyChart(data.production.efficiency);
        
        // Update Quality Charts
        updateQualityMetricsChart(data.quality.metrics);
        updateDefectAnalysisChart(data.quality.defects);
        
        // Update Financial Charts
        updateFinancialCharts(data.financial);
        
        // Update Movement Charts
        updateMovementCharts(data.movement);

        // Update footer information
        $('#generatedBy').text($('#user').text() || 'System');
        $('#generatedDate').text(new Date().toLocaleString());
    }

    // Update Executive Summary
    function updateExecutiveSummary(data) {
        $('#totalProducts').text(data.totalProducts.toLocaleString());
        $('#totalRevenue').text('Br ' + data.totalRevenue.toLocaleString());
        $('#totalOrders').text(data.totalOrders.toLocaleString());
        $('#qualityRate').text(data.qualityRate.toFixed(1) + '%');
    }

    // Stock Value Chart
    function updateStockValueChart(data) {
        if (charts.stockValue) charts.stockValue.destroy();
        
        charts.stockValue = new Chart($('#stockValueChart'), {
            type: 'pie',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.values,
                    backgroundColor: [
                        '#3498db', '#2ecc71', '#e74c3c', '#f1c40f', '#9b59b6'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    }

    // Low Stock Table
    function updateLowStockTable(data) {
        let html = '<table class="table table-bordered table-striped">';
        html += '<thead><tr><th>Product</th><th>Current Stock</th><th>Min Level</th><th>Status</th></tr></thead><tbody>';
        
        data.forEach(item => {
            const ratio = item.current_stock / item.min_stock_level;
            let status = ratio <= 0.5 ? 'danger' : (ratio <= 0.75 ? 'warning' : 'success');
            
            html += `<tr>
                <td>${item.name}</td>
                <td>${item.current_stock}</td>
                <td>${item.min_stock_level}</td>
                <td><span class="label label-${status}">${ratio <= 0.5 ? 'Critical' : (ratio <= 0.75 ? 'Warning' : 'OK')}</span></td>
            </tr>`;
        });
        
        html += '</tbody></table>';
        $('#lowStockTable').html(html);
    }

    // Production Trend Chart
    function updateProductionTrendChart(data) {
        if (charts.productionTrend) charts.productionTrend.destroy();
        
        charts.productionTrend = new Chart($('#productionTrendChart'), {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Target',
                    data: data.target,
                    borderColor: '#3498db',
                    fill: false
                }, {
                    label: 'Actual',
                    data: data.actual,
                    borderColor: '#2ecc71',
                    fill: false
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Efficiency Chart
    function updateEfficiencyChart(data) {
        if (charts.efficiency) charts.efficiency.destroy();
        
        charts.efficiency = new Chart($('#efficiencyChart'), {
            type: 'doughnut',
            data: {
                labels: ['Efficient', 'Gap'],
                datasets: [{
                    data: [data.efficiency, 100 - data.efficiency],
                    backgroundColor: ['#2ecc71', '#ecf0f1']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }

    // Quality Metrics Chart
    function updateQualityMetricsChart(data) {
        if (charts.qualityMetrics) charts.qualityMetrics.destroy();
        
        charts.qualityMetrics = new Chart($('#qualityMetricsChart'), {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Pass Rate',
                    data: data.passRate,
                    borderColor: '#2ecc71',
                    fill: false
                }, {
                    label: 'Fail Rate',
                    data: data.failRate,
                    borderColor: '#e74c3c',
                    fill: false
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
    }

    // Defect Analysis Chart
    function updateDefectAnalysisChart(data) {
        if (charts.defectAnalysis) charts.defectAnalysis.destroy();
        
        charts.defectAnalysis = new Chart($('#defectAnalysisChart'), {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Defect Rate (%)',
                    data: data.values,
                    backgroundColor: '#e74c3c'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
    }

    // Financial Charts
    function updateFinancialCharts(data) {
        if (charts.financial) charts.financial.destroy();
        
        charts.financial = new Chart($('#financialChart'), {
            type: 'bar',
            data: {
                labels: data.months,
                datasets: [{
                    label: 'Revenue',
                    data: data.revenue,
                    backgroundColor: '#2ecc71'
                }, {
                    label: 'Expenses',
                    data: data.expenses,
                    backgroundColor: '#e74c3c'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Update financial metrics with Br currency
        $('#totalSales').text('Br ' + data.totalSales.toLocaleString());
        $('#totalPurchases').text('Br ' + data.totalPurchases.toLocaleString());
        $('#netProfit').text('Br ' + data.netProfit.toLocaleString());
    }

    // Movement Charts
    function updateMovementCharts(data) {
        // Daily Movement Chart
        if (charts.dailyMovement) charts.dailyMovement.destroy();
        
        charts.dailyMovement = new Chart($('#dailyMovementChart'), {
            type: 'line',
            data: {
                labels: data.daily.labels,
                datasets: [{
                    label: 'In',
                    data: data.daily.in,
                    borderColor: '#2ecc71',
                    fill: false
                }, {
                    label: 'Out',
                    data: data.daily.out,
                    borderColor: '#e74c3c',
                    fill: false
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Monthly Movement Chart
        if (charts.monthlyMovement) charts.monthlyMovement.destroy();
        
        charts.monthlyMovement = new Chart($('#monthlyMovementChart'), {
            type: 'bar',
            data: {
                labels: data.monthly.labels,
                datasets: [{
                    label: 'Total Movements',
                    data: data.monthly.total,
                    backgroundColor: '#3498db'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Show error message
    function showError(message) {
        $('.chart-container').each(function() {
            $(this).html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> ${message}
                </div>
            `);
        });
    }

    // Update Inventory Overview
    function updateInventoryOverview(data) {
        // Update Products Chart
        const productsCtx = document.getElementById('productsStockChart');
        if (productsCtx) {
            if (charts.productsStock) {
                charts.productsStock.destroy();
            }
            charts.productsStock = new Chart(productsCtx, {
                type: 'pie',
                data: {
                    labels: data.products.labels,
                    datasets: [{
                        data: data.products.values,
                        backgroundColor: [
                            '#3498db', '#2ecc71', '#e74c3c', '#f1c40f', '#9b59b6'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right'
                        },
                        title: {
                            display: true,
                            text: 'Products Stock Distribution'
                        }
                    }
                }
            });
        }

        // Update Raw Materials Chart
        const materialsCtx = document.getElementById('materialsStockChart');
        if (materialsCtx) {
            if (charts.materialsStock) {
                charts.materialsStock.destroy();
            }
            charts.materialsStock = new Chart(materialsCtx, {
                type: 'pie',
                data: {
                    labels: data.materials.labels,
                    datasets: [{
                        data: data.materials.values,
                        backgroundColor: [
                            '#2ecc71', '#3498db', '#e74c3c', '#f1c40f', '#9b59b6'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right'
                        },
                        title: {
                            display: true,
                            text: 'Raw Materials Stock Distribution'
                        }
                    }
                }
            });
        }

        // Update Products Low Stock Table
        let productsHtml = '<table class="table table-bordered table-sm">';
        productsHtml += '<thead><tr><th>Product</th><th>Current Stock</th><th>Min Level</th><th>Status</th></tr></thead><tbody>';
        
        if (data.products.lowStock && data.products.lowStock.length > 0) {
            data.products.lowStock.forEach(item => {
                const ratio = item.current_stock / item.min_stock_level;
                let status = ratio <= 0.5 ? 'danger' : (ratio <= 0.75 ? 'warning' : 'success');
                
                productsHtml += `<tr>
                    <td>${item.name}</td>
                    <td class="text-right">${item.current_stock}</td>
                    <td class="text-right">${item.min_stock_level}</td>
                    <td><span class="badge bg-${status}">${ratio <= 0.5 ? 'Critical' : (ratio <= 0.75 ? 'Warning' : 'OK')}</span></td>
                </tr>`;
            });
        } else {
            productsHtml += '<tr><td colspan="4" class="text-center">No low stock products</td></tr>';
        }
        
        productsHtml += '</tbody></table>';
        $('#productsLowStockTable').html(productsHtml);

        // Update Raw Materials Low Stock Table
        let materialsHtml = '<table class="table table-bordered table-sm">';
        materialsHtml += '<thead><tr><th>Material</th><th>Current Stock</th><th>Min Level</th><th>Status</th></tr></thead><tbody>';
        
        if (data.materials.lowStock && data.materials.lowStock.length > 0) {
            data.materials.lowStock.forEach(item => {
                const ratio = item.current_stock / item.min_stock_level;
                let status = ratio <= 0.5 ? 'danger' : (ratio <= 0.75 ? 'warning' : 'success');
                
                materialsHtml += `<tr>
                    <td>${item.name}</td>
                    <td class="text-right">${item.current_stock}</td>
                    <td class="text-right">${item.min_stock_level}</td>
                    <td><span class="badge bg-${status}">${ratio <= 0.5 ? 'Critical' : (ratio <= 0.75 ? 'Warning' : 'OK')}</span></td>
                </tr>`;
            });
        } else {
            materialsHtml += '<tr><td colspan="4" class="text-center">No low stock materials</td></tr>';
        }
        
        materialsHtml += '</tbody></table>';
        $('#materialsLowStockTable').html(materialsHtml);

        // Update summary counts and values
        $('#totalProductCount').text(data.products.total || 0);
        $('#totalProductValue').text(formatCurrency(data.products.totalValue || 0));
        $('#totalMaterialCount').text(data.materials.total || 0);
        $('#totalMaterialValue').text(formatCurrency(data.materials.totalValue || 0));
    }

    // Initial load
    fetchReportData();

    // Refresh data every 5 minutes
    setInterval(fetchReportData, 300000);
}); 