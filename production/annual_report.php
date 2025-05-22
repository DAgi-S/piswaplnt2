<?php
require_once 'php_action/core.php';
require_once 'includes/header.php';

// Fetch company settings
$sql = "SELECT setting_key, setting_value FROM company_settings";
$result = $connect->query($sql);
$companySettings = array();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $companySettings[$row['setting_key']] = $row['setting_value'];
    }
}
?>

<link href="custom/css/annual_report.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-chart-line"></i> Annual Business Report
                    </div>
                    <div class="action-buttons no-print d-flex align-items-center">
                        <div class="year-selector me-3">
                            <select id="reportYear" class="form-control">
                                <?php
                                $currentYear = date('Y');
                                for($year = $currentYear; $year >= $currentYear - 5; $year--) {
                                    $selected = ($year == $currentYear) ? 'selected' : '';
                                    echo "<option value=\"$year\" $selected>$year</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <button class="btn btn-success me-2" id="applyYear">
                            <i class="fas fa-sync"></i> Apply Year
                        </button>
                        <button class="btn btn-primary me-2" id="generatePdf">
                            <i class="fas fa-file-pdf"></i> Generate PDF
                        </button>
                        <button class="btn btn-info" id="printReport">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </div>
            </div>
            <div class="panel-body">
                <!-- Report Header -->
                <div class="report-header">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="company-info">
                                <h2 class="company-name"><?php echo htmlspecialchars($companySettings['company_name'] ?? 'Company Name'); ?></h2>
                                <p><strong>TIN:</strong> <?php echo htmlspecialchars($companySettings['company_tin'] ?? 'N/A'); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($companySettings['company_phone'] ?? 'N/A'); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($companySettings['company_email'] ?? 'N/A'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="company-info text-right">
                                <?php if (!empty($companySettings['company_logo'])): ?>
                                <div class="company-logo">
                                    <img src="<?php echo htmlspecialchars($companySettings['company_logo']); ?>" alt="Company Logo" style="max-height: 100px;">
                                </div>
                                <?php endif; ?>
                                <p><strong>Address:</strong> <?php echo htmlspecialchars($companySettings['company_address'] ?? 'N/A'); ?></p>
                                <p><strong>Website:</strong> <?php echo htmlspecialchars($companySettings['company_website'] ?? 'N/A'); ?></p>
                               
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-4">
                        <h2>Annual Business Report</h2>
                        <p class="report-period" id="reportPeriod">January 2024 - December 2024</p>
                    </div>
                </div>

                <!-- Executive Summary -->
                <div class="section executive-summary">
                    <h3>Executive Summary</h3>
                    <div class="row metrics-grid">
                        <!-- Production Metrics -->
                        <div class="col-md-4 col-sm-6">
                            <div class="metric-card">
                                <i class="fas fa-industry"></i>
                                <h4>Total Production Products</h4>
                                <div class="metric-value" id="totalProductionProducts">0</div>
                                <small class="text-muted">Active Products</small>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="metric-card">
                                <i class="fas fa-boxes"></i>
                                <h4>Raw Materials</h4>
                                <div class="metric-value" id="totalRawMaterials">0</div>
                                <small class="text-muted">Active Materials</small>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="metric-card">
                                <i class="fas fa-check-circle"></i>
                                <h4>Quality Rate</h4>
                                <div class="metric-value" id="qualityRate">0%</div>
                                <small class="text-muted">Production Success Rate</small>
                            </div>
                        </div>

                        <!-- Sales Metrics -->
                        <div class="col-md-4 col-sm-6">
                            <div class="metric-card">
                                <i class="fas fa-shopping-cart"></i>
                                <h4>Total Sales Orders</h4>
                                <div class="metric-value" id="totalSalesOrders">0</div>
                                <div class="metric-amount" id="totalSalesAmount">Br 0.00</div>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="metric-card">
                                <i class="fas fa-cart-plus"></i>
                                <h4>Total Purchases</h4>
                                <div class="metric-value" id="totalPurchases">Br 0.00</div>
                                <div class="metric-amount" id="totalPurchaseAmount">Br 0.00</div>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="metric-card">
                                <i class="fas fa-dollar-sign"></i>
                                <h4>Net Revenue</h4>
                                <div class="metric-value" id="netRevenue">Br 0.00</div>
                                <small class="text-muted">Sales - Purchases</small>
                            </div>
                        </div>

                        <!-- Tax Metrics -->
                        <div class="col-md-4 col-sm-6">
                            <div class="metric-card">
                                <i class="fas fa-receipt"></i>
                                <h4>Total VAT from Sales</h4>
                                <div class="metric-value" id="totalSalesVAT">Br 0.00</div>
                                <small class="text-muted">15% of Sales</small>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="metric-card">
                                <i class="fas fa-file-invoice-dollar"></i>
                                <h4>Total VAT from Purchases</h4>
                                <div class="metric-value" id="totalPurchaseVAT">Br 0.00</div>
                                <small class="text-muted">15% of Purchases</small>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="metric-card">
                                <i class="fas fa-balance-scale"></i>
                                <h4>Net VAT</h4>
                                <div class="metric-value" id="netVAT">Br 0.00</div>
                                <small class="text-muted">Sales VAT - Purchase VAT</small>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                // Global variables for charts
                let financialChart = null;
                let salesChart = null;
                let productionTrendChart = null;
                let averageEfficiencyChart = null;
                let qualityMetricsChart = null;
                let defectAnalysisChart = null;
                let productsStockChart = null;
                let materialsStockChart = null;

                // Helper function for safe fetch calls
                async function safeFetch(url) {
                    try {
                        const response = await fetch(url);
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return await response.json();
                    } catch (error) {
                        console.error('Fetch error:', error);
                        throw error;
                    }
                }

                // Helper function to format currency
                function formatCurrency(number) {
                    return new Intl.NumberFormat('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }).format(number || 0);
                }

                // Helper function to format numbers
                function formatNumber(number) {
                    return new Intl.NumberFormat('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }).format(number || 0);
                }

                // Function to safely destroy charts
                function destroyCharts() {
                    const charts = [
                        financialChart,
                        salesChart,
                        productionTrendChart,
                        averageEfficiencyChart,
                        qualityMetricsChart,
                        defectAnalysisChart,
                        productsStockChart,
                        materialsStockChart
                    ];

                    charts.forEach(chart => {
                        if (chart) {
                            chart.destroy();
                        }
                    });

                    // Reset chart variables
                    financialChart = null;
                    salesChart = null;
                    productionTrendChart = null;
                    averageEfficiencyChart = null;
                    qualityMetricsChart = null;
                    defectAnalysisChart = null;
                    productsStockChart = null;
                    materialsStockChart = null;
                }

                // Function to update report period
                function updateReportPeriod(year) {
                    const reportPeriod = document.getElementById('reportPeriod');
                    if (reportPeriod) {
                        reportPeriod.textContent = `January ${year} - December ${year}`;
                    }
                }

                // Main refresh function
                async function refreshData(year) {
                    try {
                        showLoadingIndicator();
                        destroyCharts();

                        // Update report period
                        updateReportPeriod(year);

                        // Fetch all data in parallel
                        await Promise.all([
                            fetchExecutiveSummary(year),
                            fetchFinancialData(year),
                            fetchSalesPerformanceData(year),
                            fetchProductionAnalysis(year),
                            fetchQualityControl(year),
                            fetchInventoryOverview(year)
                        ]);

                        hideLoadingIndicator();
                    } catch (error) {
                        console.error('Error refreshing data:', error);
                        hideLoadingIndicator();
                        
                        // Show error message
                        const errorMessage = document.createElement('div');
                        errorMessage.className = 'alert alert-danger alert-dismissible fade show';
                        errorMessage.innerHTML = `
                            <strong>Error!</strong> An error occurred while refreshing the data. Please try again.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        `;
                        document.querySelector('.page-heading').insertAdjacentElement('afterend', errorMessage);
                        
                        throw error;
                    }
                }

                // Initialize the page
                document.addEventListener('DOMContentLoaded', function() {
                    const currentYear = new Date().getFullYear();
                    
                    // Set initial year in all dropdowns
                    const yearDropdowns = ['reportYear', 'financialYear', 'salesYear'];
                    yearDropdowns.forEach(id => {
                        const dropdown = document.getElementById(id);
                        if (dropdown) {
                            dropdown.value = currentYear;
                        }
                    });

                    // Initial data load
                    refreshData(currentYear).catch(error => {
                        console.error('Error during initial load:', error);
                    });

                    // Handle Apply Year button click
                    document.getElementById('applyYear').addEventListener('click', async function() {
                        const selectedYear = document.getElementById('reportYear').value;
                        try {
                            await refreshData(selectedYear);
                            
                            // Update all year dropdowns to match
                            yearDropdowns.forEach(id => {
                                const dropdown = document.getElementById(id);
                                if (dropdown) {
                                    dropdown.value = selectedYear;
                                }
                            });
                        } catch (error) {
                            console.error('Error refreshing data:', error);
                        }
                    });

                    // Handle individual section year changes
                    ['financialYear', 'salesYear'].forEach(id => {
                        const dropdown = document.getElementById(id);
                        if (dropdown) {
                            dropdown.addEventListener('change', async function() {
                                const selectedYear = this.value;
                                try {
                                    document.getElementById('reportYear').value = selectedYear;
                                    await refreshData(selectedYear);
                                } catch (error) {
                                    console.error('Error updating section:', error);
                                    alert('Error updating section data. Please try again.');
                                }
                            });
                        }
                    });
                });

                // Loading indicator functions
                function showLoadingIndicator() {
                    const loadingOverlay = document.getElementById('loadingOverlay') || createLoadingOverlay();
                    loadingOverlay.style.display = 'flex';
                }

                function hideLoadingIndicator() {
                    const loadingOverlay = document.getElementById('loadingOverlay');
                    if (loadingOverlay) {
                        loadingOverlay.style.display = 'none';
                    }
                }

                function createLoadingOverlay() {
                    const overlay = document.createElement('div');
                    overlay.id = 'loadingOverlay';
                    overlay.innerHTML = `
                        <div class="loading-spinner">
                            <i class="fas fa-spinner fa-spin"></i>
                            <span>Refreshing Report Data...</span>
                        </div>
                    `;
                    document.body.appendChild(overlay);
                    return overlay;
                }
                </script>

                <!-- Inventory Overview -->
                <div class="section inventory-overview">
                    <h3>Inventory Overview</h3>
                    <div class="row inventory-grid">
                        <!-- Products Chart -->
                        <div class="col-md-6 col-print-6 inventory-item">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h4 class="card-title">Products Stock Distribution</h4>
                                    <div class="chart-container">
                                        <canvas id="productsStockChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Raw Materials Chart -->
                        <div class="col-md-6 col-print-6 inventory-item">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h4 class="card-title">Raw Materials Stock Distribution</h4>
                                    <div class="chart-container">
                                        <canvas id="materialsStockChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Products Low Stock -->
                        <div class="col-md-6 col-print-6 inventory-item">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h4 class="card-title">Products Low Stock Alert</h4>
                                    <div class="table-responsive" id="productsLowStockTable"></div>
                                    <div class="inventory-summary mt-3">
                                        <div class="row">
                                            <div class="col-6">
                                                <strong>Total Products:</strong>
                                                <span id="totalProductCount">0</span>
                                            </div>
                                            <div class="col-6 text-end">
                                                <strong>Stock Value:</strong>
                                                <span id="totalProductValue">Br 0.00</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Raw Materials Low Stock -->
                        <div class="col-md-6 col-print-6 inventory-item">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h4 class="card-title">Raw Materials Low Stock Alert</h4>
                                    <div class="table-responsive" id="materialsLowStockTable"></div>
                                    <div class="inventory-summary mt-3">
                                        <div class="row">
                                            <div class="col-6">
                                                <strong>Total Materials:</strong>
                                                <span id="totalMaterialCount">0</span>
                                            </div>
                                            <div class="col-6 text-end">
                                                <strong>Stock Value:</strong>
                                                <span id="totalMaterialValue">Br 0.00</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                // Function to fetch and update Inventory Overview data
                async function fetchInventoryOverview(year) {
                    try {
                        const response = await fetch(`php_action/fetchInventoryOverview.php?year=${year}`);
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        const data = await response.json();
                        
                        if (!data.success) {
                            throw new Error(data.error || 'Failed to fetch inventory data');
                        }

                        // Update Products Stock Chart
                        if (window.productsStockChart) {
                            window.productsStockChart.data.labels = data.data.productsStock.labels;
                            window.productsStockChart.data.datasets[0].data = data.data.productsStock.data;
                            window.productsStockChart.update();
                        }

                        // Update Materials Stock Chart
                        if (window.materialsStockChart) {
                            window.materialsStockChart.data.labels = data.data.materialsStock.labels;
                            window.materialsStockChart.data.datasets[0].data = data.data.materialsStock.data;
                            window.materialsStockChart.update();
                        }

                        // Update Products Low Stock Table
                        const productsTable = document.getElementById('productsLowStockTable');
                        productsTable.innerHTML = generateLowStockTable(data.data.productsLowStock);
                        document.getElementById('totalProductCount').textContent = data.data.productsStock.totalCount;
                        document.getElementById('totalProductValue').textContent = 'Br ' + formatNumber(data.data.productsStock.totalValue);

                        // Update Materials Low Stock Table
                        const materialsTable = document.getElementById('materialsLowStockTable');
                        materialsTable.innerHTML = generateLowStockTable(data.data.materialsLowStock);
                        document.getElementById('totalMaterialCount').textContent = data.data.materialsStock.totalCount;
                        document.getElementById('totalMaterialValue').textContent = 'Br ' + formatNumber(data.data.materialsStock.totalValue);

                    } catch (error) {
                        console.error('Error fetching inventory overview:', error);
                    }
                }

                // Helper function to generate low stock table HTML
                function generateLowStockTable(items) {
                    if (!items || items.length === 0) {
                        return '<p class="text-center">No low stock items found</p>';
                    }

                    return `
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Item Name</th>
                                    <th>Current Stock</th>
                                    <th>Reorder Level</th>
                                    <th>Stock Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${items.map(item => `
                                    <tr>
                                        <td>${item.name}</td>
                                        <td>${item.quantity}</td>
                                        <td>${item.reorder_level}</td>
                                        <td>Br ${formatNumber(item.stock_value)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    `;
                }

                // Add to your existing document.ready function
                document.addEventListener('DOMContentLoaded', function() {
                    // Initial load with current year
                    const currentYear = document.getElementById('reportYear').value;
                    fetchInventoryOverview(currentYear);

                    // Update when year changes (add this to your existing year change handler)
                    document.getElementById('applyYear').addEventListener('click', function() {
                        const selectedYear = document.getElementById('reportYear').value;
                        fetchInventoryOverview(selectedYear);
                    });
                });
                </script>

                <!-- Production Analysis -->
                <div class="section production-analysis print-new-page">
                    <h3>Production Analysis</h3>
                    <div class="row">
                        <!-- Monthly Production Trends -->
                        <div class="col-md-8 col-print-8">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h4 class="card-title">Monthly Production Trends</h4>
                                    <div class="chart-container">
                                        <canvas id="productionTrendChart"></canvas>
                                    </div>
                                    <div class="chart-legend mt-3">
                                        <div class="row">
                                            <div class="col-6">
                                                <span class="legend-item">
                                                    <i class="fas fa-square text-primary"></i> Target Production
                                                </span>
                                            </div>
                                            <div class="col-6">
                                                <span class="legend-item">
                                                    <i class="fas fa-square text-success"></i> Actual Production
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Average Efficiency -->
                        <div class="col-md-4 col-print-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h4 class="card-title">Average Efficiency</h4>
                                    <div class="chart-container">
                                        <canvas id="averageEfficiencyChart"></canvas>
                                    </div>
                                    <div class="efficiency-stats mt-3">
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="stat-item">
                                                    <label>Monthly Avg:</label>
                                                    <span id="monthlyAvgEfficiency">0%</span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="stat-item">
                                                    <label>Peak:</label>
                                                    <span id="peakEfficiency">0%</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="efficiency-rate text-center mt-2">
                                            <label>Overall Average:</label>
                                            <span id="overallAvgEfficiency">0%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Production Statistics -->
                    <div class="row mt-4">
                        <div class="col-md-3 col-print-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="stat-card">
                                        <i class="fas fa-tasks"></i>
                                        <h5>Total Orders</h5>
                                        <div class="stat-value" id="totalProductionOrders">0</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-print-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="stat-card">
                                        <i class="fas fa-spinner"></i>
                                        <h5>In Progress</h5>
                                        <div class="stat-value" id="inProgressOrders">0</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-print-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="stat-card">
                                        <i class="fas fa-check-circle"></i>
                                        <h5>Completed</h5>
                                        <div class="stat-value" id="completedOrders">0</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-print-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="stat-card">
                                        <i class="fas fa-percentage"></i>
                                        <h5>Completion Rate</h5>
                                        <div class="stat-value" id="completionRate">0%</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                // Function to fetch production analysis data
                async function fetchProductionAnalysis(year) {
                    try {
                        const data = await safeFetch(`php_action/fetchProductionAnalysis.php?year=${year}`);
                        
                        if (!data.success) {
                            throw new Error(data.error || 'Failed to fetch production data');
                        }

                        // Update production statistics
                        document.getElementById('totalProductionOrders').textContent = data.statistics.totalOrders;
                        document.getElementById('inProgressOrders').textContent = data.statistics.inProgress;
                        document.getElementById('completedOrders').textContent = data.statistics.completed;
                        document.getElementById('completionRate').textContent = data.statistics.completionRate + '%';

                        // Update efficiency metrics
                        document.getElementById('monthlyAvgEfficiency').textContent = data.efficiency.monthly + '%';
                        document.getElementById('overallAvgEfficiency').textContent = data.efficiency.overall + '%';
                        document.getElementById('peakEfficiency').textContent = data.efficiency.peak + '%';

                        // Create production trend chart
                        const trendCtx = document.getElementById('productionTrendChart').getContext('2d');
                        productionTrendChart = new Chart(trendCtx, {
                            type: 'line',
                            data: {
                                labels: data.productionTrends.labels,
                                datasets: [{
                                    label: 'Target Production',
                                    data: data.productionTrends.targetProduction,
                                    borderColor: '#007bff',
                                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                                    borderWidth: 2,
                                    fill: true,
                                    tension: 0.4
                                }, {
                                    label: 'Actual Production',
                                    data: data.productionTrends.actualProduction,
                                    borderColor: '#28a745',
                                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                                    borderWidth: 2,
                                    fill: true,
                                    tension: 0.4
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            font: { size: 11 }
                                        }
                                    },
                                    x: {
                                        ticks: {
                                            font: { size: 11 }
                                        }
                                    }
                                },
                                plugins: {
                                    legend: {
                                        display: true,
                                        position: 'bottom',
                                        labels: {
                                            font: { size: 11 }
                                        }
                                    }
                                }
                            }
                        });

                        // Create efficiency chart
                        const efficiencyCtx = document.getElementById('averageEfficiencyChart').getContext('2d');
                        averageEfficiencyChart = new Chart(efficiencyCtx, {
                            type: 'doughnut',
                            data: {
                                labels: ['Efficiency', 'Remaining'],
                                datasets: [{
                                    data: [data.efficiency.monthly, 100 - data.efficiency.monthly],
                                    backgroundColor: ['#28a745', '#f8f9fa'],
                                    borderWidth: 0
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                cutout: '70%',
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                return context.label + ': ' + context.raw + '%';
                                            }
                                        }
                                    }
                                }
                            }
                        });

                    } catch (error) {
                        console.error('Error in fetchProductionAnalysis:', error);
                        throw error;
                    }
                }

                // Add to your existing document.ready function
                document.addEventListener('DOMContentLoaded', function() {
                    // Initial load with current year
                    const currentYear = document.getElementById('reportYear').value;
                    fetchProductionAnalysis(currentYear);

                    // Update when year changes (add this to your existing year change handler)
                    document.getElementById('applyYear').addEventListener('click', function() {
                        const selectedYear = document.getElementById('reportYear').value;
                        fetchProductionAnalysis(selectedYear);
                    });
                });
                </script>

                <!-- Quality Control -->
                <div class="section quality-control print-new-page">
                    <h3>Quality Control</h3>
                    <div class="row">
                        <!-- Quality Metrics Chart -->
                        <div class="col-md-6 col-print-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h4 class="card-title">Quality Metrics Overview</h4>
                                    <div class="chart-container">
                                        <canvas id="qualityMetricsChart"></canvas>
                                    </div>
                                    <div class="quality-stats mt-3">
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="stat-item">
                                                    <label>Pass Rate:</label>
                                                    <span id="qualityPassRate">0%</span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="stat-item">
                                                    <label>Rejection Rate:</label>
                                                    <span id="qualityRejectionRate">0%</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Defect Analysis Chart -->
                        <div class="col-md-6 col-print-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h4 class="card-title">Defect Analysis</h4>
                                    <div class="chart-container">
                                        <canvas id="defectAnalysisChart"></canvas>
                                    </div>
                                    <div class="defect-stats mt-3">
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="stat-item">
                                                    <label>Total Inspections:</label>
                                                    <span id="totalInspections">0</span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="stat-item">
                                                    <label>Critical Issues:</label>
                                                    <span id="criticalDefects">0</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quality Statistics -->
                    <div class="row mt-4">
                        <div class="col-md-3 col-print-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="stat-card">
                                        <i class="fas fa-clipboard-check"></i>
                                        <h5>Total QC Checks</h5>
                                        <div class="stat-value" id="totalQCChecks">0</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-print-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="stat-card">
                                        <i class="fas fa-check-circle"></i>
                                        <h5>Passed Items</h5>
                                        <div class="stat-value" id="passedItems">0</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-print-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="stat-card">
                                        <i class="fas fa-times-circle"></i>
                                        <h5>Failed Items</h5>
                                        <div class="stat-value" id="failedItems">0</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-print-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="stat-card">
                                        <i class="fas fa-chart-line"></i>
                                        <h5>Quality Trend</h5>
                                        <div class="stat-value" id="qualityTrend">0%</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Defects Table -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Top Quality Issues</h4>
                                    <div class="table-responsive" id="topDefectsTable">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Defect Type</th>
                                                    <th>Occurrences</th>
                                                    <th>Severity</th>
                                                    <th>Impact</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody id="topDefectsBody">
                                                <!-- Data will be populated by JavaScript -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                // Function to fetch quality control data
                async function fetchQualityControl(year) {
                    try {
                        const data = await safeFetch(`php_action/fetchQualityControl.php?year=${year}`);
                        
                        if (!data.success) {
                            throw new Error(data.error || 'Failed to fetch quality control data');
                        }

                        // Update quality statistics
                        document.getElementById('totalQCChecks').textContent = data.metrics.totalChecks;
                        document.getElementById('passedItems').textContent = data.metrics.passedChecks;
                        document.getElementById('failedItems').textContent = data.metrics.failedChecks;
                        document.getElementById('qualityTrend').textContent = data.trendChange + '%';

                        // Update quality stats
                        document.getElementById('qualityPassRate').textContent = data.metrics.passRate + '%';
                        document.getElementById('qualityRejectionRate').textContent = data.metrics.rejectionRate + '%';
                        document.getElementById('totalInspections').textContent = data.statistics.totalInspections;
                        document.getElementById('criticalDefects').textContent = data.statistics.criticalDefects;

                        // Create quality metrics chart
                        const metricsCtx = document.getElementById('qualityMetricsChart').getContext('2d');
                        qualityMetricsChart = new Chart(metricsCtx, {
                            type: 'doughnut',
                            data: {
                                labels: ['Pass Rate', 'Rejection Rate'],
                                datasets: [{
                                    data: [data.metrics.passRate, data.metrics.rejectionRate],
                                    backgroundColor: ['#28a745', '#dc3545'],
                                    borderWidth: 0
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                cutout: '70%',
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: {
                                            font: { size: 11 }
                                        }
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                return context.label + ': ' + context.raw + '%';
                                            }
                                        }
                                    }
                                }
                            }
                        });

                        // Create defect analysis chart
                        const defectCtx = document.getElementById('defectAnalysisChart').getContext('2d');
                        defectAnalysisChart = new Chart(defectCtx, {
                            type: 'bar',
                            data: {
                                labels: data.defectAnalysis.map(d => d.defect_type),
                                datasets: [{
                                    label: 'Defect Count',
                                    data: data.defectAnalysis.map(d => d.count),
                                    backgroundColor: 'rgba(255, 99, 132, 0.8)',
                                    borderColor: 'rgba(255, 99, 132, 1)',
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            font: { size: 11 }
                                        }
                                    },
                                    x: {
                                        ticks: {
                                            font: { size: 11 }
                                        }
                                    }
                                },
                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                }
                            }
                        });

                        // Update top defects table
                        updateTopDefectsTable(data.defectAnalysis);

                    } catch (error) {
                        console.error('Error in fetchQualityControl:', error);
                        throw error;
                    }
                }

                // Helper function to update top defects table
                function updateTopDefectsTable(defects) {
                    const tbody = document.getElementById('topDefectsBody');
                    if (!defects || !defects.length) {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center">No defects data available</td></tr>';
                        return;
                    }

                    tbody.innerHTML = defects.map(defect => `
                        <tr>
                            <td>${defect.defect_type}</td>
                            <td>${defect.count}</td>
                            <td>${defect.severity}</td>
                            <td>${defect.impact_level}</td>
                            <td><span class="badge ${getSeverityBadgeClass(defect.severity)}">${defect.severity}</span></td>
                        </tr>
                    `).join('');
                }

                // Helper function for severity badge classes in quality control
                function getSeverityBadgeClass(severity) {
                    switch(severity.toLowerCase()) {
                        case 'critical':
                            return 'bg-danger';
                        case 'major':
                            return 'bg-warning';
                        case 'minor':
                            return 'bg-info';
                        default:
                            return 'bg-secondary';
                    }
                }

                // Add to your existing document.ready function
                document.addEventListener('DOMContentLoaded', function() {
                    // Initial load with current year
                    const currentYear = document.getElementById('reportYear').value;
                    fetchQualityControl(currentYear);

                    // Update when year changes (add this to your existing year change handler)
                    document.getElementById('applyYear').addEventListener('click', function() {
                        const selectedYear = document.getElementById('reportYear').value;
                        fetchQualityControl(selectedYear);
                    });
                });
                </script>

                <!-- Financial Overview -->
                <div class="section financial-overview print-new-page">
                    <h3>Financial Overview</h3>
                    <div class="row">
                        <!-- Revenue vs Expenses Chart -->
                        <div class="col-md-8 col-print-8">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h4 class="card-title">Revenue vs Expenses</h4>
                                    <div class="chart-container financial-chart">
                                        <canvas id="financialChart"></canvas>
                                    </div>
                                    <div class="financial-legend mt-3">
                                        <div class="row">
                                            <div class="col-6">
                                                <span class="legend-item">
                                                    <i class="fas fa-square text-success"></i> Revenue
                                                </span>
                                            </div>
                                            <div class="col-6">
                                                <span class="legend-item">
                                                    <i class="fas fa-square text-danger"></i> Expenses
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Financial Summary -->
                        <div class="col-md-4 col-print-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h4 class="card-title">Financial Summary</h4>
                                    <div class="financial-summary">
                                        <div class="summary-item">
                                            <label>Total Revenue:</label>
                                            <span class="amount text-success" id="totalRevenue">Br 0.00</span>
                                        </div>
                                        <div class="summary-item">
                                            <label>Total Expenses:</label>
                                            <span class="amount text-danger" id="totalExpenses">Br 0.00</span>
                                        </div>
                                        <div class="summary-item">
                                            <label>Gross Profit:</label>
                                            <span class="amount" id="grossProfit">Br 0.00</span>
                                        </div>
                                        <div class="summary-item total">
                                            <label>Net Profit:</label>
                                            <span class="amount" id="netProfit">Br 0.00</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Metrics -->
                    <div class="row mt-4">
                        <div class="col-md-3 col-print-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="financial-metric">
                                        <i class="fas fa-chart-line"></i>
                                        <div class="metric-details">
                                            <label>Profit Margin</label>
                                            <span class="metric-value" id="profitMargin">0%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-print-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="financial-metric">
                                        <i class="fas fa-money-bill-wave"></i>
                                        <div class="metric-details">
                                            <label>Cash Flow</label>
                                            <span class="metric-value" id="cashFlow">Br 0.00</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-print-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="financial-metric">
                                        <i class="fas fa-chart-bar"></i>
                                        <div class="metric-details">
                                            <label>Revenue Growth</label>
                                            <span class="metric-value" id="revenueGrowth">0%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-print-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="financial-metric">
                                        <i class="fas fa-balance-scale"></i>
                                        <div class="metric-details">
                                            <label>Cost Ratio</label>
                                            <span class="metric-value" id="costRatio">0%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <style>
                    /* Financial Overview Specific Styles */
                    .financial-overview {
                        font-size: 11px;
                    }
                    
                    .financial-overview .card-title {
                        font-size: 11px;
                        text-align: left;
                        margin-bottom: 15px;
                    }
                    
                    .financial-overview .chart-container {
                        height: 300px;
                        margin-bottom: 15px;
                        position: relative;
                    }

                    .financial-overview .financial-chart {
                        min-height: 300px;
                        width: 100%;
                    }
                    
                    .financial-legend {
                        text-align: left;
                        padding: 10px 0;
                    }
                    
                    .financial-legend .legend-item {
                        display: inline-flex;
                        align-items: center;
                        margin-right: 15px;
                        font-size: 11px;
                    }
                    
                    .financial-legend i {
                        margin-right: 5px;
                        font-size: 11px;
                    }
                    
                    .financial-summary .summary-item {
                        display: flex;
                        justify-content: space-between;
                        margin-bottom: 10px;
                        padding: 5px 0;
                        font-size: 11px;
                    }
                    
                    .financial-summary .summary-item label {
                        text-align: left;
                        margin: 0;
                        font-weight: 500;
                    }
                    
                    .financial-summary .summary-item .amount {
                        text-align: right;
                        font-weight: 600;
                    }
                    
                    .financial-summary .total {
                        border-top: 1px solid #eee;
                        margin-top: 10px;
                        padding-top: 10px;
                    }
                    
                    .financial-metric {
                        display: flex;
                        align-items: center;
                    }
                    
                    .financial-metric i {
                        font-size: 20px;
                        margin-right: 10px;
                        color: #007bff;
                    }
                    
                    .financial-metric .metric-details {
                        text-align: left;
                    }
                    
                    .financial-metric label {
                        display: block;
                        margin: 0;
                        font-size: 11px;
                    }
                    
                    .financial-metric .metric-value {
                        font-weight: 600;
                        text-align: left;
                        font-size: 11px;
                    }

                    .text-success {
                        color: #28a745;
                    }

                    .text-danger {
                        color: #dc3545;
                    }
                    
                    @media print {
                        .financial-overview {
                            page-break-inside: avoid;
                        }
                        
                        .financial-overview .chart-container {
                            height: 250px !important;
                            page-break-inside: avoid;
                        }

                        .financial-overview .financial-chart {
                            min-height: 250px !important;
                        }
                        
                        .financial-metric i {
                            font-size: 16px;
                        }

                        .financial-summary .summary-item,
                        .financial-metric label,
                        .financial-metric .metric-value,
                        .financial-legend .legend-item {
                            font-size: 11px !important;
                        }

                        .card {
                            break-inside: avoid;
                        }

                        .col-print-8,
                        .col-print-4,
                        .col-print-3 {
                            float: left;
                            page-break-inside: avoid;
                        }

                        .financial-overview .card-title {
                            font-size: 11px !important;
                        }
                    }
                    </style>

                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        let financialChart = null;

                        // Function to fetch financial data
                        async function fetchFinancialData(year) {
                            try {
                                const data = await safeFetch(`php_action/fetchFinancialData.php?year=${year}`);
                                
                                // Update financial metrics
                                document.getElementById('totalRevenue').textContent = formatCurrency(data.totalRevenue);
                                document.getElementById('totalExpenses').textContent = formatCurrency(data.totalExpenses);
                                document.getElementById('grossProfit').textContent = formatCurrency(data.grossProfit);
                                document.getElementById('netProfit').textContent = formatCurrency(data.netProfit);
                                document.getElementById('profitMargin').textContent = data.profitMargin + '%';
                                document.getElementById('cashFlow').textContent = formatCurrency(data.cashFlow);
                                document.getElementById('revenueGrowth').textContent = data.revenueGrowth + '%';
                                document.getElementById('costRatio').textContent = data.costRatio + '%';

                                // Destroy existing chart if it exists
                                if (financialChart) {
                                    financialChart.destroy();
                                }

                                // Create new financial chart
                                const ctx = document.getElementById('financialChart').getContext('2d');
                                financialChart = new Chart(ctx, {
                                    type: 'line',
                                    data: {
                                        labels: data.chartData.labels,
                                        datasets: [{
                                            label: 'Revenue',
                                            data: data.chartData.revenue,
                                            borderColor: 'rgba(75, 192, 192, 1)',
                                            tension: 0.4,
                                            fill: false
                                        }, {
                                            label: 'Expenses',
                                            data: data.chartData.expenses,
                                            borderColor: 'rgba(255, 99, 132, 1)',
                                            tension: 0.4,
                                            fill: false
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
                                                        return formatCurrency(value);
                                                    }
                                                }
                                            }
                                        },
                                        plugins: {
                                            tooltip: {
                                                callbacks: {
                                                    label: function(context) {
                                                        return context.dataset.label + ': ' + formatCurrency(context.parsed.y);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                });
                            } catch (error) {
                                console.error('Error in fetchFinancialData:', error);
                                throw error;
                            }
                        }

                        // Function to format currency
                        function formatCurrency(number) {
                            return new Intl.NumberFormat('en-US', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            }).format(number);
                        }

                        // Initialize financial data with current year
                        let currentYear = new Date().getFullYear();
                        fetchFinancialData(currentYear);

                        // Handle year selection change
                        $('#financialYear').on('change', function() {
                            fetchFinancialData($(this).val());
                        });
                    });
                    </script>
                </div>

                <!-- Sales Performance -->
                <div class="section sales-performance print-new-page">
                    <h3>Sales Performance Analysis</h3>
                    <div class="row">
                        <!-- Monthly Sales Trends -->
                        <div class="col-md-8 col-print-8">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h4 class="card-title">Monthly Sales Trends</h4>
                                    <div class="d-flex align-items-center">
                                        <select id="salesYear" class="form-control mr-2" style="width: auto;">
                                            <?php
                                            $currentYear = date('Y');
                                            for($year = $currentYear; $year >= $currentYear - 4; $year--) {
                                                echo "<option value='$year'>$year</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container sales-chart">
                                        <canvas id="monthlySalesChart"></canvas>
                                    </div>
                                    <div class="sales-legend mt-3">
                                        <div class="row">
                                            <div class="col-4">
                                                <span class="legend-item">
                                                    <i class="fas fa-square text-primary"></i> Revenue
                                                </span>
                                            </div>
                                            <div class="col-4">
                                                <span class="legend-item">
                                                    <i class="fas fa-square text-success"></i> Orders
                                                </span>
                                            </div>
                                            <div class="col-4">
                                                <span class="legend-item">
                                                    <i class="fas fa-square text-warning"></i> Average Order Value
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sales Summary -->
                        <div class="col-md-4 col-print-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h4 class="card-title">Sales Summary</h4>
                                    <div class="sales-summary">
                                        <div class="summary-item">
                                            <label>Total Orders:</label>
                                            <span class="amount text-primary" id="totalSalesCount">0</span>
                                        </div>
                                        <div class="summary-item">
                                            <label>Total Revenue:</label>
                                            <span class="amount text-success" id="totalSalesRevenue">Br 0.00</span>
                                        </div>
                                        <div class="summary-item">
                                            <label>Average Order Value:</label>
                                            <span class="amount" id="avgOrderValue">Br 0.00</span>
                                        </div>
                                        <div class="summary-item total">
                                            <label>Collection Rate:</label>
                                            <span class="amount" id="collectionRate">0%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sales Statistics -->
                    <div class="row mt-4">
                        <div class="col-md-3 col-print-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="sales-metric">
                                        <i class="fas fa-shopping-cart"></i>
                                        <div class="metric-details">
                                            <label>Completed Orders</label>
                                            <span class="metric-value" id="completedSales">0</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-print-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="sales-metric">
                                        <i class="fas fa-money-bill-wave"></i>
                                        <div class="metric-details">
                                            <label>Paid Amount</label>
                                            <span class="metric-value" id="totalPaidAmount">Br 0.00</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-print-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="sales-metric">
                                        <i class="fas fa-clock"></i>
                                        <div class="metric-details">
                                            <label>Pending Amount</label>
                                            <span class="metric-value" id="totalPendingAmount">Br 0.00</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-print-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="sales-metric">
                                        <i class="fas fa-chart-line"></i>
                                        <div class="metric-details">
                                            <label>Growth Rate</label>
                                            <span class="metric-value" id="salesGrowthRate">0%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Sales Table -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Recent Sales</h4>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Order #</th>
                                                    <th>Date</th>
                                                    <th>Client</th>
                                                    <th>Amount</th>
                                                    <th>Paid</th>
                                                    <th>Balance</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody id="recentSalesBody">
                                                <!-- Data will be populated by JavaScript -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <style>
                    /* Sales Performance Specific Styles */
                    .sales-performance {
                        font-size: 11px;
                    }
                    
                    .sales-performance .card-title {
                        font-size: 11px;
                        text-align: left;
                        margin-bottom: 15px;
                    }
                    
                    .sales-performance .chart-container {
                        height: 300px;
                        margin-bottom: 15px;
                        position: relative;
                    }

                    .sales-performance .sales-chart {
                        min-height: 300px;
                        width: 100%;
                    }
                    
                    .sales-legend {
                        text-align: left;
                        padding: 10px 0;
                    }
                    
                    .sales-legend .legend-item {
                        display: inline-flex;
                        align-items: center;
                        margin-right: 15px;
                        font-size: 11px;
                    }
                    
                    .sales-legend i {
                        margin-right: 5px;
                        font-size: 11px;
                    }
                    
                    .sales-summary .summary-item {
                        display: flex;
                        justify-content: space-between;
                        margin-bottom: 10px;
                        padding: 5px 0;
                        font-size: 11px;
                    }
                    
                    .sales-summary .summary-item label {
                        text-align: left;
                        margin: 0;
                        font-weight: 500;
                    }
                    
                    .sales-summary .summary-item .amount {
                        text-align: right;
                        font-weight: 600;
                    }
                    
                    .sales-summary .total {
                        border-top: 1px solid #eee;
                        margin-top: 10px;
                        padding-top: 10px;
                    }
                    
                    .sales-metric {
                        display: flex;
                        align-items: center;
                    }
                    
                    .sales-metric i {
                        font-size: 20px;
                        margin-right: 10px;
                        color: #007bff;
                    }
                    
                    .sales-metric .metric-details {
                        text-align: left;
                    }
                    
                    .sales-metric label {
                        display: block;
                        margin: 0;
                        font-size: 11px;
                    }
                    
                    .sales-metric .metric-value {
                        font-weight: 600;
                        text-align: left;
                        font-size: 11px;
                    }

                    .sales-performance table {
                        font-size: 11px;
                    }

                    .sales-performance table th {
                        font-weight: 600;
                        background-color: #f8f9fa;
                    }

                    .text-primary { color: #007bff; }
                    .text-success { color: #28a745; }
                    .text-warning { color: #ffc107; }
                    
                    @media print {
                        .sales-performance {
                            page-break-inside: avoid;
                        }
                        
                        .sales-performance .chart-container {
                            height: 250px !important;
                            page-break-inside: avoid;
                        }

                        .sales-performance .sales-chart {
                            min-height: 250px !important;
                        }
                        
                        .sales-metric i {
                            font-size: 16px;
                        }

                        .sales-summary .summary-item,
                        .sales-metric label,
                        .sales-metric .metric-value,
                        .sales-legend .legend-item,
                        .sales-performance table {
                            font-size: 11px !important;
                        }

                        .card {
                            break-inside: avoid;
                        }

                        .col-print-8,
                        .col-print-4,
                        .col-print-3 {
                            float: left;
                            page-break-inside: avoid;
                        }

                        .sales-performance .card-title {
                            font-size: 11px !important;
                        }
                    }
                    </style>

                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        let salesChart = null;

                        // Function to fetch sales performance data
                        async function fetchSalesPerformanceData(year) {
                            try {
                                const data = await safeFetch(`php_action/fetchSalesPerformanceData.php?year=${year}`);
                                
                                if (!data.success) {
                                    throw new Error(data.error || 'Failed to fetch data');
                                }

                                // Update summary values
                                document.getElementById('totalSalesCount').textContent = formatNumber(data.totalSales);
                                document.getElementById('totalSalesRevenue').textContent = 'Br ' + formatNumber(data.totalRevenue);
                                document.getElementById('avgOrderValue').textContent = 'Br ' + formatNumber(data.averageOrderValue);
                                document.getElementById('collectionRate').textContent = data.collectionRate + '%';
                                
                                // Update metrics
                                document.getElementById('completedSales').textContent = formatNumber(data.completedSales);
                                document.getElementById('totalPaidAmount').textContent = 'Br ' + formatNumber(data.totalPaidAmount);
                                document.getElementById('totalPendingAmount').textContent = 'Br ' + formatNumber(data.totalPendingAmount);
                                document.getElementById('salesGrowthRate').textContent = data.growthRate + '%';

                                // Destroy existing chart if it exists
                                if (salesChart) {
                                    salesChart.destroy();
                                }
                                
                                // Create new sales chart
                                const ctx = document.getElementById('monthlySalesChart').getContext('2d');
                                salesChart = new Chart(ctx, {
                                    type: 'line',
                                    data: {
                                        labels: data.monthlyData.labels,
                                        datasets: [
                                            {
                                                label: 'Revenue',
                                                data: data.monthlyData.revenue,
                                                borderColor: '#007bff',
                                                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                                                borderWidth: 2,
                                                fill: true,
                                                tension: 0.4
                                            },
                                            {
                                                label: 'Orders',
                                                data: data.monthlyData.orders,
                                                borderColor: '#28a745',
                                                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                                                borderWidth: 2,
                                                fill: true,
                                                tension: 0.4
                                            },
                                            {
                                                label: 'Average Order Value',
                                                data: data.monthlyData.averageValue,
                                                borderColor: '#ffc107',
                                                backgroundColor: 'rgba(255, 193, 7, 0.1)',
                                                borderWidth: 2,
                                                fill: true,
                                                tension: 0.4
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
                                                    font: { size: 11 },
                                                    callback: function(value) {
                                                        return 'Br ' + formatNumber(value);
                                                    }
                                                }
                                            },
                                            x: {
                                                ticks: {
                                                    font: { size: 11 }
                                                }
                                            }
                                        },
                                        plugins: {
                                            legend: {
                                                display: false
                                            },
                                            tooltip: {
                                                mode: 'index',
                                                intersect: false,
                                                callbacks: {
                                                    label: function(context) {
                                                        let label = context.dataset.label;
                                                        let value = context.raw;
                                                        if (label === 'Revenue' || label === 'Average Order Value') {
                                                            return label + ': Br ' + formatNumber(value);
                                                        }
                                                        return label + ': ' + formatNumber(value);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                });

                                // Populate recent sales table
                                populateRecentSales(data.recentSales);
                            } catch (error) {
                                console.error('Error in fetchSalesPerformanceData:', error);
                                throw error;
                            }
                        }

                // Helper function to populate recent sales table
                function populateRecentSales(sales) {
                    const tbody = document.getElementById('recentSalesBody');
                    if (!sales || !sales.length) {
                        tbody.innerHTML = '<tr><td colspan="7" class="text-center">No recent sales found</td></tr>';
                        return;
                    }

                    tbody.innerHTML = sales.map(sale => `
                        <tr>
                            <td>${sale.order_number}</td>
                            <td>${sale.order_date}</td>
                            <td>${sale.client_name}</td>
                            <td>Br ${formatNumber(sale.total_amount)}</td>
                            <td>Br ${formatNumber(sale.paid_amount)}</td>
                            <td>Br ${formatNumber(sale.balance)}</td>
                            <td><span class="badge ${getBadgeClass(sale.payment_status)}">${sale.payment_status}</span></td>
                        </tr>
                    `).join('');
                }

                // Helper function for badge classes
                function getBadgeClass(status) {
                    switch(status.toLowerCase()) {
                        case 'paid':
                            return 'bg-success';
                        case 'partial':
                            return 'bg-warning';
                        default:
                            return 'bg-danger';
                    }
                }

                // Helper function to format currency
                function formatCurrency(number) {
                    return new Intl.NumberFormat('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }).format(number);
                }

                // Helper function to format numbers
                function formatNumber(number) {
                    return new Intl.NumberFormat('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }).format(number);
                }

                // Initialize sales data with current year
                let currentYear = new Date().getFullYear();
                fetchSalesPerformanceData(currentYear);

                // Handle year selection change
                $('#salesYear').on('change', function() {
                    fetchSalesPerformanceData($(this).val());
                });
            });
            </script>
                </div>

                <!-- Report Footer -->
                <div class="report-footer">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Generated by:</strong> <span id="generatedBy"><?php echo htmlspecialchars($companySettings['company_name'] ?? 'Company Name'); ?></span></p>
                            <p><strong>Generated on:</strong> <span id="generatedDate"></span></p>
                        </div>
                        <div class="col-md-6 text-right">
                            <p><strong>Company:</strong> <?php echo htmlspecialchars($companySettings['company_name'] ?? 'Company Name'); ?></p>
                            <p><strong>Financial Year:</strong> 2024</p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($companySettings['company_phone'] ?? 'N/A'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script src="custom/js/annual_report.js"></script>

<!-- Add Font Awesome CSS in the head section -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<!-- Add custom styles -->
<style>
.metric-value {
    font-size: 24px !important;
    font-weight: bold;
    margin-bottom: 5px;
}

.currency-value {
    font-size: 12px !important;
    color: #28a745;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: none;
    margin-bottom: 1rem;
}

.card-body {
    padding: 1.25rem;
}

.text-muted {
    font-size: 12px;
}

/* Add animation for number changes */
.metric-value {
    transition: all 0.3s ease;
}

/* Responsive font sizes */
@media (max-width: 768px) {
    .metric-value {
        font-size: 20px !important;
    }
    .currency-value {
        font-size: 10px !important;
    }
}

/* General styles */
.inventory-overview .card {
    margin-bottom: 20px;
    height: 100%;
}

.inventory-overview .chart-container {
    position: relative;
    height: 250px;
}

.inventory-overview .card-title {
    font-size: 1rem;
    margin-bottom: 1rem;
}

.inventory-summary {
    border-top: 1px solid #dee2e6;
    padding-top: 10px;
    margin-top: 10px;
}

/* Print-specific styles */
@media print {
    .inventory-overview {
        page-break-inside: avoid;
    }

    .col-print-6 {
        width: 50%;
        float: left;
    }

    .inventory-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        page-break-inside: avoid;
    }

    .inventory-item {
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .chart-container {
        height: 200px !important;
    }

    .card {
        border: 1px solid #dee2e6 !important;
        box-shadow: none !important;
    }

    .table-responsive {
        max-height: 200px !important;
        overflow-y: hidden !important;
    }

    .badge {
        border: 1px solid #000 !important;
        padding: 2px 5px !important;
    }

    .badge-danger, .bg-danger {
        background-color: #fff !important;
        color: #000 !important;
        border-color: #dc3545 !important;
    }

    .badge-warning, .bg-warning {
        background-color: #fff !important;
        color: #000 !important;
        border-color: #ffc107 !important;
    }

    .badge-success, .bg-success {
        background-color: #fff !important;
        color: #000 !important;
        border-color: #28a745 !important;
    }
}

/* Responsive styles */
@media (max-width: 768px) {
    .inventory-grid {
        display: block;
    }

    .inventory-item {
        margin-bottom: 20px;
    }
}

/* Production Analysis Styles */
.production-analysis {
    margin-top: 2rem;
}

.production-analysis .card {
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.production-analysis .chart-container {
    position: relative;
    height: 300px;
    margin-bottom: 1rem;
}

.production-analysis .card-title {
    color: #333;
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
}

.chart-legend .legend-item {
    display: inline-flex;
    align-items: center;
    margin-right: 1rem;
    font-size: 0.9rem;
}

.chart-legend i {
    margin-right: 0.5rem;
    font-size: 0.8rem;
}

.efficiency-stats {
    padding-top: 1rem;
    border-top: 1px solid #eee;
}

.stat-item {
    text-align: center;
}

.stat-item label {
    display: block;
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 0.3rem;
}

.stat-item span {
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
}

.efficiency-rate {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px dashed #eee;
}

.efficiency-rate label {
    display: block;
    font-size: 0.9rem;
    color: #666;
}

.efficiency-rate span {
    font-size: 1.5rem;
    font-weight: 700;
    color: #28a745;
}

.stat-card {
    text-align: center;
}

.stat-card i {
    font-size: 2rem;
    color: #007bff;
    margin-bottom: 0.5rem;
}

.stat-card h5 {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 0.5rem;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #333;
}

/* Print-specific styles */
@media print {
    .print-new-page {
        page-break-before: always;
    }

    .production-analysis {
        margin-top: 0;
    }

    .col-print-8 {
        width: 66.666667%;
        float: left;
    }

    .col-print-4 {
        width: 33.333333%;
        float: left;
    }

    .col-print-3 {
        width: 25%;
        float: left;
    }

    .production-analysis .chart-container {
        height: 250px !important;
    }

    .stat-card i {
        font-size: 1.5rem;
    }

    .stat-value {
        font-size: 1.2rem;
    }

    .efficiency-rate span {
        font-size: 1.2rem;
    }

    .chart-legend {
        font-size: 0.8rem;
    }

    .production-analysis .card {
        border: 1px solid #dee2e6 !important;
        box-shadow: none !important;
    }
}

/* Add these styles to hide POS cart icon and adjust print view */
.cart-fixed-button,
.btn-floating,
[href*="pos.php"],
.float-button,
.floating-button,
.fixed-action-btn {
    display: none !important;
}

@media print {
    .no-print,
    .cart-fixed-button,
    .btn-floating,
    [href*="pos.php"],
    .float-button,
    .floating-button,
    .fixed-action-btn,
    .action-buttons {
        display: none !important;
    }

    .page-heading {
        text-align: center;
        margin-bottom: 20px;
    }

    .panel {
        border: none !important;
        box-shadow: none !important;
    }

    .panel-heading {
        border-bottom: 2px solid #333 !important;
        margin-bottom: 20px !important;
    }

    /* Ensure proper page breaks */
    .print-new-page {
        page-break-before: always;
    }

    .print-avoid-break {
        page-break-inside: avoid;
    }
}

/* Adjust button styling */
.action-buttons {
    display: inline-flex;
    gap: 10px;
}

.action-buttons .btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.action-buttons .btn i {
    font-size: 14px;
}

/* Year selector styles */
.year-selector {
    min-width: 120px;
}

.year-selector select {
    height: 38px;
    border-radius: 4px;
    border: 1px solid #ced4da;
    padding: 0.375rem 0.75rem;
    font-size: 14px;
}

.action-buttons {
    display: inline-flex;
    gap: 10px;
    align-items: center;
}

.action-buttons .btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.action-buttons .btn i {
    font-size: 14px;
}
</style>

<!-- Add JavaScript for number formatting -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Function to format numbers with BR currency and commas
    function formatCurrency(number) {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(number);
    }

    // Update all currency values with proper formatting
    document.querySelectorAll('[id^="total"], [id^="net"]').forEach(element => {
        if (element.textContent.includes('.')) {
            const number = parseFloat(element.textContent.replace(/[^\d.-]/g, ''));
            if (!isNaN(number)) {
                element.textContent = formatCurrency(number);
            }
        }
    });

    // Handle PDF Generation
    document.getElementById('generatePdf').addEventListener('click', function() {
        window.print();
    });

    // Handle Print
    document.getElementById('printReport').addEventListener('click', function() {
        window.print();
    });

    // Handle Year Selection
    document.getElementById('applyYear').addEventListener('click', function() {
        const selectedYear = document.getElementById('reportYear').value;
        refreshData(selectedYear);
    });

    // Function to refresh report data based on selected year
    function refreshData(year) {
        // Show loading indicator
        showLoadingIndicator();

        // Update report period text
        document.querySelector('.report-period').textContent = `January ${year} - December ${year}`;

        // Fetch new data for each section with the selected year
        Promise.all([
            fetchFinancialData(year),
            fetchSalesPerformanceData(year),
            fetchProductionAnalysis(year),
            fetchQualityControl(year),
            fetchInventoryData(year)
        ]).then(() => {
            // Hide loading indicator
            hideLoadingIndicator();
        }).catch(error => {
            console.error('Error refreshing report data:', error);
            hideLoadingIndicator();
            alert('Error refreshing report data. Please try again.');
        });
    }

    // Loading indicator functions
    function showLoadingIndicator() {
        // Create loading overlay if it doesn't exist
        if (!document.getElementById('loadingOverlay')) {
            const overlay = document.createElement('div');
            overlay.id = 'loadingOverlay';
            overlay.innerHTML = `
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    <span>Refreshing Report Data...</span>
                </div>
            `;
            document.body.appendChild(overlay);
        }
        document.getElementById('loadingOverlay').style.display = 'flex';
    }

    function hideLoadingIndicator() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    }

    // Remove any floating cart buttons that might be added dynamically
    function removeFloatingButtons() {
        const floatingElements = document.querySelectorAll('.cart-fixed-button, .btn-floating, .float-button, .floating-button, .fixed-action-btn');
        floatingElements.forEach(element => element.remove());
    }

    // Run on load and periodically check
    removeFloatingButtons();
    setInterval(removeFloatingButtons, 1000);
});

// Add loading overlay styles
const style = document.createElement('style');
style.textContent = `
    #loadingOverlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.8);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }

    .loading-spinner {
        text-align: center;
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .loading-spinner i {
        font-size: 24px;
        color: #007bff;
        margin-bottom: 10px;
    }

    .loading-spinner span {
        display: block;
        margin-top: 10px;
        color: #666;
    }
`;
document.head.appendChild(style);
</script>

<script>
// Function to update report period
function updateReportPeriod(year) {
    document.getElementById('reportPeriod').textContent = `January ${year} - December ${year}`;
}

// Initialize report period with current year
let currentYear = new Date().getFullYear();
updateReportPeriod(currentYear);

// Handle year selection changes
$('#financialYear, #salesYear').on('change', function() {
    const selectedYear = $(this).val();
    updateReportPeriod(selectedYear);
});
</script> 

<script>
// Function to fetch executive summary data
async function fetchExecutiveSummary(year) {
    try {
        const data = await safeFetch(`php_action/fetchExecutiveSummary.php?year=${year}`);
        
        if (!data.success) {
            throw new Error(data.error || 'Failed to fetch executive summary data');
        }

        // Update production metrics
        document.getElementById('totalProductionProducts').textContent = data.totalProducts || 0;
        document.getElementById('totalRawMaterials').textContent = data.totalMaterials || 0;
        document.getElementById('qualityRate').textContent = (data.qualityRate || 0) + '%';

        // Update sales metrics
        document.getElementById('totalSalesOrders').textContent = data.totalOrders || 0;
        document.getElementById('totalSalesAmount').textContent = 'Br ' + formatNumber(data.totalSalesAmount || 0);
        document.getElementById('totalPurchases').textContent = 'Br ' + formatNumber(data.totalPurchases || 0);
        document.getElementById('netRevenue').textContent = 'Br ' + formatNumber(data.netRevenue || 0);

        // Update VAT metrics
        document.getElementById('totalSalesVAT').textContent = 'Br ' + formatNumber(data.totalSalesVAT || 0);
        document.getElementById('totalPurchaseVAT').textContent = 'Br ' + formatNumber(data.totalPurchaseVAT || 0);
        document.getElementById('netVAT').textContent = 'Br ' + formatNumber(data.netVAT || 0);

    } catch (error) {
        console.error('Error in fetchExecutiveSummary:', error);
        throw error;
    }
}

// Update the refresh functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize with current year
    const currentYear = new Date().getFullYear();
    
    // Set the initial year in all year dropdowns
    const yearDropdowns = ['reportYear', 'financialYear', 'salesYear'];
    yearDropdowns.forEach(id => {
        const dropdown = document.getElementById(id);
        if (dropdown) {
            dropdown.value = currentYear;
        }
    });

    // Initial data load
    refreshData(currentYear);

    // Handle Apply Year button click
    document.getElementById('applyYear').addEventListener('click', async function() {
        const selectedYear = document.getElementById('reportYear').value;
        try {
            await refreshData(selectedYear);
            
            // Update all year dropdowns to match
            yearDropdowns.forEach(id => {
                const dropdown = document.getElementById(id);
                if (dropdown) {
                    dropdown.value = selectedYear;
                }
            });

            // Update report period
            updateReportPeriod(selectedYear);
            
        } catch (error) {
            console.error('Error refreshing data:', error);
            // Show user-friendly error message
            const errorMessage = document.createElement('div');
            errorMessage.className = 'alert alert-danger alert-dismissible fade show';
            errorMessage.innerHTML = `
                <strong>Error!</strong> An error occurred while refreshing the data. Please try again.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            document.querySelector('.page-heading').insertAdjacentElement('afterend', errorMessage);
        }
    });

    // Handle individual section year changes
    ['financialYear', 'salesYear'].forEach(id => {
        const dropdown = document.getElementById(id);
        if (dropdown) {
            dropdown.addEventListener('change', async function() {
                const selectedYear = this.value;
                try {
                    // Update main year dropdown
                    document.getElementById('reportYear').value = selectedYear;
                    await refreshData(selectedYear);
                } catch (error) {
                    console.error('Error updating section:', error);
                    alert('Error updating section data. Please try again.');
                }
            });
        }
    });
});

// Add error handling styles
const errorStyles = document.createElement('style');
errorStyles.textContent = `
    .alert {
        padding: 1rem;
        margin-bottom: 1rem;
        border: 1px solid transparent;
        border-radius: 0.25rem;
    }
    .alert-danger {
        color: #721c24;
        background-color: #f8d7da;
        border-color: #f5c6cb;
    }
    .alert-dismissible {
        padding-right: 4rem;
    }
    .alert-dismissible .btn-close {
        position: absolute;
        top: 0;
        right: 0;
        padding: 1.25rem 1rem;
        background: transparent;
        border: 0;
        font-size: 1.25rem;
        cursor: pointer;
    }
`;
document.head.appendChild(errorStyles);
</script> 

<script>
// Global variables and functions
window.fetchFinancialData = async function(year) {
    try {
        const response = await fetch(`php_action/fetchFinancialData.php?year=${year}`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Failed to fetch financial data');
        }

        // Update financial metrics
        document.getElementById('totalRevenue').textContent = 'Br ' + formatNumber(data.totalRevenue);
        document.getElementById('totalExpenses').textContent = 'Br ' + formatNumber(data.totalExpenses);
        document.getElementById('grossProfit').textContent = 'Br ' + formatNumber(data.grossProfit);
        document.getElementById('netProfit').textContent = 'Br ' + formatNumber(data.netProfit);
        document.getElementById('profitMargin').textContent = data.profitMargin + '%';
        document.getElementById('cashFlow').textContent = 'Br ' + formatNumber(data.cashFlow);
        document.getElementById('revenueGrowth').textContent = data.revenueGrowth + '%';
        document.getElementById('costRatio').textContent = data.costRatio + '%';

        // Destroy existing chart if it exists
        if (window.financialChart) {
            window.financialChart.destroy();
        }

        // Create new financial chart
        const ctx = document.getElementById('financialChart').getContext('2d');
        window.financialChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.chartData.labels,
                datasets: [{
                    label: 'Revenue',
                    data: data.chartData.revenue,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Expenses',
                    data: data.chartData.expenses,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    tension: 0.4,
                    fill: true
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
                                return 'Br ' + formatNumber(value);
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': Br ' + formatNumber(context.parsed.y);
                            }
                        }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error in fetchFinancialData:', error);
        throw error;
    }
};

// Helper function to format numbers
window.formatNumber = function(number) {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(number || 0);
};

// Global chart variables
window.financialChart = null;
window.salesChart = null;sa
window.productionTrendChart = null;
window.averageEfficiencyChart = null;
window.qualityMetricsChart = null;
window.defectAnalysisChart = null;
window.productsStockChart = null;
window.materialsStockChart = null;

// ... rest of the existing code ...
</script> 

<script>
// Function to fetch and update Inventory Overview data
async function fetchInventoryOverview() {
    try {
        const response = await safeFetch('php_action/fetchInventoryOverview.php');
        if (!response.success) {
            throw new Error(response.message || 'Failed to fetch inventory data');
        }
        const data = response.data;

        // Update low stock products table
        const productsTableBody = document.querySelector('#lowStockProductsTable tbody');
        if (productsTableBody) {
            productsTableBody.innerHTML = '';
            
            data.lowStockProducts.forEach(product => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${product.name}</td>
                    <td>${formatNumber(product.quantity)}</td>
                    <td>${formatNumber(product.reorderLevel)}</td>
                    <td><span class="badge ${product.status === 'Out of Stock' ? 'bg-danger' : 'bg-warning'}">${product.status}</span></td>
                `;
                productsTableBody.appendChild(row);
            });
        }

        // Update low stock materials table
        const materialsTableBody = document.querySelector('#lowStockMaterialsTable tbody');
        if (materialsTableBody) {
            materialsTableBody.innerHTML = '';
            
            data.lowStockMaterials.forEach(material => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${material.name}</td>
                    <td>${formatNumber(material.quantity)}</td>
                    <td>${formatNumber(material.reorderLevel)}</td>
                    <td><span class="badge ${material.status === 'Out of Stock' ? 'bg-danger' : 'bg-warning'}">${material.status}</span></td>
                `;
                materialsTableBody.appendChild(row);
            });
        }

        // Update stock distribution charts
        if (data.productsStock && data.materialsStock) {
            // Destroy existing charts first
            if (window.productsStockChart) {
                window.productsStockChart.destroy();
            }
            if (window.materialsStockChart) {
                window.materialsStockChart.destroy();
            }

            // Create products stock chart
            const productsCtx = document.getElementById('productsStockChart');
            if (productsCtx) {
                window.productsStockChart = new Chart(productsCtx, {
                    type: 'doughnut',
                    data: {
                        labels: data.productsStock.labels,
                        datasets: [{
                            data: data.productsStock.data,
                            backgroundColor: ['#28a745', '#ffc107']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }

            // Create materials stock chart
            const materialsCtx = document.getElementById('materialsStockChart');
            if (materialsCtx) {
                window.materialsStockChart = new Chart(materialsCtx, {
                    type: 'doughnut',
                    data: {
                        labels: data.materialsStock.labels,
                        datasets: [{
                            data: data.materialsStock.data,
                            backgroundColor: ['#28a745', '#ffc107']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }
        }
    } catch (error) {
        console.error('Error fetching inventory overview:', error);
        showError('Error fetching inventory data');
    }
}

// Modified refreshData function
async function refreshData() {
    try {
        showLoading();
        // Destroy all charts before fetching new data
        destroyCharts();
        
        await Promise.all([
            fetchExecutiveSummary(selectedYear),
            fetchFinancialData(selectedYear),
            fetchSalesPerformance(selectedYear),
            fetchProductionAnalysis(selectedYear),
            fetchQualityControl(selectedYear),
            fetchInventoryOverview() // Add this line
        ]);
        hideLoading();
        showSuccess('Data refreshed successfully');
    } catch (error) {
        console.error('Error refreshing data:', error);
        hideLoading();
        showError('An error occurred while refreshing the data. Please try again.');
    }
}

// ... existing code ...
</script> 

<!-- Inventory Overview Section -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title">Inventory Overview</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6>Low Stock Products</h6>
                <div class="table-responsive">
                    <table class="table table-bordered" id="lowStockProductsTable">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Current Quantity</th>
                                <th>Reorder Level</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-md-6">
                <h6>Low Stock Raw Materials</h6>
                <div class="table-responsive">
                    <table class="table table-bordered" id="lowStockMaterialsTable">
                        <thead>
                            <tr>
                                <th>Material Name</th>
                                <th>Current Quantity</th>
                                <th>Reorder Level</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div> 