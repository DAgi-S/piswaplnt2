<?php
require_once 'php_action/core.php';
require_once 'includes/header.php';
?>

<style>
    /* Custom CSS for Reports Page */
    .report-card {
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        transition: transform 0.2s;
    }
    .report-card:hover {
        transform: translateY(-5px);
    }
    .metric-card {
        background: #fff;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .metric-value {
        font-size: 24px;
        font-weight: bold;
        margin: 10px 0;
    }
    .metric-label {
        color: #666;
        font-size: 14px;
    }
    .chart-container {
        background: #fff;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        position: relative;
        min-height: 300px;
    }
    .status-badge {
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 12px;
    }
    .status-completed { background: #28a745; color: white; }
    .status-pending { background: #ffc107; color: black; }
    .status-cancelled { background: #dc3545; color: white; }
    .filter-section {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255,255,255,0.8);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }
    @media print {
        .no-print { display: none !important; }
        .print-only { display: block !important; }
        .print-break-inside { page-break-inside: avoid; }
        
        /* Print-specific styles */
        body {
            font-size: 11px;
            line-height: 1.3;
            margin: 0;
            padding: 0;
        }
        
        .print-header {
            text-align: center;
            margin-bottom: 15px;
            padding: 10px 0;
            border-bottom: 1px solid #ddd;
        }
        
        .print-header h2 {
            font-size: 16px;
            margin: 0 0 5px 0;
        }
        
        .print-header h3 {
            font-size: 14px;
            margin: 0 0 5px 0;
        }
        
        .print-header p {
            font-size: 10px;
            margin: 2px 0;
        }
        
        .metric-card {
            width: 33.33%;
            float: left;
            padding: 8px;
            margin: 0;
            box-shadow: none;
            border: 1px solid #ddd;
            page-break-inside: avoid;
        }
        
        .metric-value {
            font-size: 14px;
            font-weight: bold;
            margin: 5px 0;
        }
        
        .metric-label {
            font-size: 10px;
            color: #333;
        }
        
        .chart-container {
            width: 50%;
            float: left;
            padding: 10px;
            margin: 0;
            box-shadow: none;
            min-height: 200px;
            page-break-inside: avoid;
        }
        
        .table {
            font-size: 10px;
            margin-top: 15px;
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th,
        .table td {
            padding: 4px !important;
            border: 1px solid #ddd !important;
        }
        
        .status-badge {
            padding: 2px 5px !important;
            font-size: 9px !important;
            border-radius: 3px !important;
        }
        
        .status-healthy { 
            background: #28a745 !important;
            color: white !important;
            border: none !important;
        }
        
        .status-lowstock { 
            background: #ffc107 !important;
            color: black !important;
            border: none !important;
        }
        
        .status-outofstock { 
            background: #dc3545 !important;
            color: white !important;
            border: none !important;
        }
        
        .currency-value {
            white-space: nowrap;
        }
        
        /* Clear floats */
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
        
        /* Hide DataTables controls when printing */
        .dataTables_length,
        .dataTables_filter,
        .dataTables_info,
        .dataTables_paginate {
            display: none !important;
        }
        
        /* Show all rows in print */
        .table-responsive {
            overflow: visible !important;
            width: 100% !important;
            margin: 15px 0 !important;
        }
        
        /* Ensure table takes full width in print */
        .table {
            width: 100% !important;
            margin: 0 !important;
            font-size: 10px !important;
        }
        
        /* Ensure all rows are visible in print */
        .table tbody tr {
            display: table-row !important;
            page-break-inside: avoid;
        }
        
        /* Adjust table cell padding for print */
        .table th,
        .table td {
            padding: 4px !important;
            font-size: 10px !important;
        }
        
        /* Status badge adjustments for print */
        .status-badge {
            padding: 2px 5px !important;
            font-size: 9px !important;
            border: 1px solid #ddd !important;
        }
        
        /* Hide cart/POS icon in print preview */
        .btn-floating,
        [href*="pos.php"],
        .cart-fixed-button {
            display: none !important;
        }
        
        /* Hide any floating buttons */
        .fixed-action-btn,
        .floating-button,
        .float-button {
            display: none !important;
        }
        
        /* Ensure the icon doesn't show on any page during print */
        body::after {
            display: none !important;
        }
        
        /* Remove any fixed positioned elements during print */
        .fixed-bottom,
        .position-fixed,
        .fixed-right {
            display: none !important;
        }
        
        /* Force page breaks where needed */
        .page-break-after {
            page-break-after: always;
        }
        
        .page-break-before {
            page-break-before: always;
        }
        
        /* Hide non-essential elements */
        .no-print,
        .dataTables_filter,
        .dataTables_length,
        .dataTables_paginate,
        .dataTables_info {
            display: none !important;
        }
    }
    .status-healthy { 
        background: #28a745; 
        color: white; 
    }
    .status-lowstock { 
        background: #ffc107; 
        color: black; 
    }
    .status-outofstock { 
        background: #dc3545; 
        color: white; 
    }
    
    /* Sales Order Report Styles */
    .status-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.9em;
        font-weight: 500;
    }
    
    .status-completed {
        background-color: #d4edda;
        color: #155724;
    }
    
    .status-cancelled {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .status-pending {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .currency-value {
        text-align: right;
        font-family: monospace;
    }
    
    @media print {
        .status-badge {
            border: 1px solid #ddd;
        }
        
        .currency-value {
            white-space: nowrap;
        }
    }
</style>

<!-- Additional CSS -->
<link rel="stylesheet" href="../assests/plugins/datatables/jquery.dataTables.min.css">
<link rel="stylesheet" href="../assests/plugins/select2/css/select2.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.css">

<div class="row">
    <div class="col-md-12">
        <ol class="breadcrumb no-print">
            <li><a href="dashboard.php">Home</a></li>
            <li class="active">Comprehensive Reports</li>
        </ol>

        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="fas fa-chart-line"></i> Comprehensive Reports Dashboard
                </div>
            </div>
            <div class="panel-body">
                <!-- Filters Section -->
                <div class="row no-print">
                    <div class="col-md-12">
                        <div class="filter-section">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Report Type</label>
                                        <select class="form-control" id="reportType">
                                            <option value="production_summary">Production Summary</option>
                                            <option value="raw_material">Raw Material Status</option>
                                            <option value="purchase_order">Purchase Order</option>
                                            <option value="sales_order">Sales Order</option>
                                            <option value="sales_performance">Sales Performance</option>
                                            <option value="sales_analysis">Sales Analysis</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Date Range</label>
                                        <select class="form-control" id="dateRange">
                                            <option value="today">Today</option>
                                            <option value="yesterday">Yesterday</option>
                                            <option value="last7days">Last 7 Days</option>
                                            <option value="last30days">Last 30 Days</option>
                                            <option value="thisMonth">This Month</option>
                                            <option value="lastMonth">Last Month</option>
                                            <option value="custom">Custom Range</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4" id="customDateRange" style="display: none;">
                                    <div class="form-group">
                                        <label>Custom Date Range</label>
                                        <div class="input-group">
                                            <input type="date" class="form-control" id="startDate">
                                            <span class="input-group-addon">to</span>
                                            <input type="date" class="form-control" id="endDate">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <button class="btn btn-primary btn-block" id="generateReport">
                                            <i class="fas fa-sync"></i> Generate
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Print Header -->
                <div class="print-only" style="display: none;">
                    <div class="print-header">
                        <h2>Production Management System</h2>
                        <h3 id="printReportTitle"></h3>
                        <p>Generated on: <span id="printDate"></span></p>
                        <p>Date Range: <span id="printDateRange"></span></p>
                    </div>
                </div>

                <!-- Report Content -->
                <div id="reportContent">
                    <!-- Summary Cards Row -->
                    <div class="row clearfix" id="summaryCards"></div>
                    
                    <!-- Charts Row -->
                    <div class="row clearfix" id="chartsRow">
                        <div class="col-md-6">
                            <div class="chart-container">
                                <div class="loading-overlay" style="display: none;">
                                    <i class="fas fa-spinner fa-spin fa-3x"></i>
                                </div>
                                <canvas id="chartOne"></canvas>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-container">
                                <div class="loading-overlay" style="display: none;">
                                    <i class="fas fa-spinner fa-spin fa-3x"></i>
                                </div>
                                <canvas id="chartTwo"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Detailed Data Table -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered" id="reportTable">
                                    <thead>
                                        <tr>
                                            <th>Loading data...</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Please wait...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Export Options -->
                <div class="row no-print">
                    <div class="col-md-12 text-right">
                        <button class="btn btn-info" id="printReport">
                            <i class="fas fa-print"></i> Print
                        </button>
                        <button class="btn btn-success" id="exportExcel">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </button>
                        <button class="btn btn-danger" id="exportPdf">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Required JavaScript -->
<script src="../assests/jquery/jquery.min.js"></script>
<script src="../assests/bootstrap/js/bootstrap.min.js"></script>
<script src="../assests/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../assests/plugins/select2/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<!-- Additional JavaScript for header functionality -->
<script src="../assests/plugins/sweetalert2/js/sweetalert2.min.js"></script>
<script src="../assests/plugins/fileinput/js/fileinput.min.js"></script>
<script src="../assests/jquery-ui/jquery-ui.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>

<!-- Initialize header dropdowns -->
<script>
$(document).ready(function() {
    // Initialize all dropdowns
    $('.dropdown-toggle').dropdown();
    
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Initialize popovers
    $('[data-toggle="popover"]').popover();
    
    // Initialize Select2
    $('.select2').select2();
    
    // Initialize Bootstrap Select
    $('.selectpicker').selectpicker();
});
</script>

<script>
$(document).ready(function() {
    // Chart instances
    let chartOne = null;
    let chartTwo = null;

    // Initialize Select2
    $('#reportType, #dateRange').select2();
    
    // Handle date range selection
    $('#dateRange').change(function() {
        $('#customDateRange').toggle($(this).val() === 'custom');
    });

    // Initialize date inputs with current date
    const today = new Date();
    const todayStr = today.toISOString().split('T')[0];
    $('#startDate, #endDate').attr('max', todayStr);
    $('#startDate').on('change', function() {
        $('#endDate').attr('min', $(this).val());
    });

    // Show loading state
    function showLoading() {
        $('.loading-overlay').show();
        $('#reportContent').find('.table-responsive').addClass('loading');
    }

    // Hide loading state
    function hideLoading() {
        $('.loading-overlay').hide();
        $('#reportContent').find('.table-responsive').removeClass('loading');
    }

    // Destroy existing charts
    function destroyCharts() {
        if (chartOne) {
            chartOne.destroy();
            chartOne = null;
        }
        if (chartTwo) {
            chartTwo.destroy();
            chartTwo = null;
        }
    }

    // Generate Report
    $('#generateReport').click(function() {
        const reportType = $('#reportType').val();
        const dateRange = $('#dateRange').val();
        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();

        if (dateRange === 'custom' && (!startDate || !endDate)) {
            alert('Please select both start and end dates for custom range');
            return;
        }

        showLoading();
        destroyCharts();

        // Fetch report data
        $.ajax({
            url: 'php_action/fetchNewReports.php',
            type: 'POST',
            data: {
                type: reportType,
                dateRange: dateRange,
                startDate: startDate,
                endDate: endDate
            },
            success: function(response) {
                if (!response.success) {
                    alert(response.error || 'Error loading report');
                    hideLoading();
                    return;
                }
                
                updateReportView(reportType, response);
                updatePrintInfo(reportType, dateRange, startDate, endDate);
                hideLoading();
            },
            error: function(xhr, status, error) {
                alert('Error loading report: ' + error);
                hideLoading();
            }
        });
    });

    // Update report view based on type and data
    function updateReportView(reportType, data) {
        // Clear previous content
        $('#summaryCards').empty();
        destroyCharts();
        
        switch(reportType) {
            case 'production_summary':
                updateProductionSummary(data);
                break;
            case 'raw_material':
                updateRawMaterialReport(data);
                break;
            case 'purchase_order':
                updatePurchaseOrderReport(data);
                break;
            case 'sales_order':
                updateSalesOrderReport(data);
                break;
            case 'sales_performance':
                updateSalesPerformance(data);
                break;
            case 'sales_analysis':
                updateSalesAnalysis(data);
                break;
            case 'cost_analysis':
                updateCostAnalysis(data);
                break;
        }
    }

    function updateProductionSummary(data) {
        // Summary Cards - 3x2 grid layout
        const summaryHTML = `
            <div class="col-md-4 col-print-4">
                <div class="metric-card">
                    <div class="metric-value">${data.summary.total_orders || 0}</div>
                    <div class="metric-label">Total Orders</div>
                </div>
            </div>
            <div class="col-md-4 col-print-4">
                <div class="metric-card">
                    <div class="metric-value">${data.summary.completed_orders || 0}</div>
                    <div class="metric-label">Completed Orders</div>
                </div>
            </div>
            <div class="col-md-4 col-print-4">
                <div class="metric-card">
                    <div class="metric-value">${data.summary.ongoing_orders || 0}</div>
                    <div class="metric-label">Ongoing Orders</div>
                </div>
            </div>
            <div class="col-md-4 col-print-4">
                <div class="metric-card">
                    <div class="metric-value">${data.summary.avg_efficiency || 0}%</div>
                    <div class="metric-label">Average Efficiency</div>
                </div>
            </div>
            <div class="col-md-4 col-print-4">
                <div class="metric-card">
                    <div class="metric-value">${data.summary.cancelled_orders || 0}</div>
                    <div class="metric-label">Cancelled Orders</div>
                </div>
            </div>
            <div class="col-md-4 col-print-4">
                <div class="metric-card">
                    <div class="metric-value">${data.summary.total_quantity || 0}</div>
                    <div class="metric-label">Total Quantity</div>
                </div>
            </div>`;
        $('#summaryCards').html(summaryHTML);

        // Initialize DataTable
        if ($.fn.DataTable.isDataTable('#reportTable')) {
            $('#reportTable').DataTable().destroy();
        }

        const tableHTML = `
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Product</th>
                    <th>Target Qty</th>
                    <th>Completed Qty</th>
                    <th>Efficiency</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                ${data.orders.map(order => `
                    <tr>
                        <td>${order.order_id}</td>
                        <td>${order.product_name}</td>
                        <td>${order.target_quantity}</td>
                        <td>${order.completed_quantity}</td>
                        <td>${order.efficiency}%</td>
                        <td>${order.start_date}</td>
                        <td>${order.end_date || '-'}</td>
                        <td><span class="status-badge status-${order.status.toLowerCase()}">${order.status}</span></td>
                    </tr>
                `).join('')}
            </tbody>`;
        
        $('#reportTable').html(tableHTML).DataTable({
            "order": [[0, "desc"]],
            "pageLength": 10,
            "dom": 'Bfrtip',
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
            "drawCallback": function(settings) {
                if (window.matchMedia('print').matches) {
                    this.api().page.len(-1).draw();
                }
            }
        });

        // Add print event handler right after DataTable initialization
        window.addEventListener('beforeprint', function() {
            const dataTable = $('#reportTable').DataTable();
            dataTable.page.len(-1).draw();
        });

        window.addEventListener('afterprint', function() {
            const dataTable = $('#reportTable').DataTable();
            dataTable.page.len(10).draw();
        });

        // Charts
        const ctx1 = document.getElementById('chartOne').getContext('2d');
        chartOne = new Chart(ctx1, {
            type: 'pie',
            data: {
                labels: ['Completed', 'Ongoing', 'Cancelled'],
                datasets: [{
                    data: [
                        data.summary.completed_orders || 0,
                        data.summary.ongoing_orders || 0,
                        data.summary.cancelled_orders || 0
                    ],
                    backgroundColor: ['#28a745', '#ffc107', '#dc3545']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                title: {
                    display: true,
                    text: 'Order Status Distribution'
                }
            }
        });

        // Efficiency Trend Chart
        const ctx2 = document.getElementById('chartTwo').getContext('2d');
        chartTwo = new Chart(ctx2, {
            type: 'line',
            data: {
                labels: data.orders.slice(-7).map(order => order.start_date),
                datasets: [{
                    label: 'Efficiency %',
                    data: data.orders.slice(-7).map(order => order.efficiency),
                    borderColor: '#007bff',
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                title: {
                    display: true,
                    text: 'Efficiency Trend (Last 7 Orders)'
                },
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            max: 100
                        }
                    }]
                }
            }
        });
    }

    function updateRawMaterialReport(data) {
        // Summary Cards - 3x2 grid layout for raw material metrics
        const summaryHTML = `
            <div class="col-md-4 col-print-4">
                <div class="metric-card">
                    <div class="metric-value">${data.summary.total_materials || 0}</div>
                    <div class="metric-label">Total Raw Materials</div>
                </div>
            </div>
            <div class="col-md-4 col-print-4">
                <div class="metric-card">
                    <div class="metric-value">${data.summary.low_stock_items || 0}</div>
                    <div class="metric-label">Low Stock Items</div>
                </div>
            </div>
            <div class="col-md-4 col-print-4">
                <div class="metric-card">
                    <div class="metric-value">${data.summary.out_of_stock || 0}</div>
                    <div class="metric-label">Out of Stock</div>
                </div>
            </div>
            <div class="col-md-4 col-print-4">
                <div class="metric-card">
                    <div class="metric-value">${formatCurrency(data.summary.total_value || 0)}</div>
                    <div class="metric-label">Total Stock Value</div>
                </div>
            </div>
            <div class="col-md-4 col-print-4">
                <div class="metric-card">
                    <div class="metric-value">${data.summary.reserved_quantity || 0}</div>
                    <div class="metric-label">Reserved Quantity</div>
                </div>
            </div>
            <div class="col-md-4 col-print-4">
                <div class="metric-card">
                    <div class="metric-value">${data.summary.active_materials || 0}</div>
                    <div class="metric-label">Active Materials</div>
                </div>
            </div>`;
        $('#summaryCards').html(summaryHTML);

        // Initialize DataTable for Raw Materials
        if ($.fn.DataTable.isDataTable('#reportTable')) {
            $('#reportTable').DataTable().destroy();
        }

        const tableHTML = `
            <thead>
                <tr>
                    <th>Material Code</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Unit</th>
                    <th>Current Stock</th>
                    <th>Reserved Qty</th>
                    <th>Min Level</th>
                    <th>Cost/Unit</th>
                    <th>Total Value</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                ${data.materials.map(item => `
                    <tr>
                        <td>${item.material_code}</td>
                        <td>${item.name}</td>
                        <td>${item.category_name || '-'}</td>
                        <td>${item.unit}</td>
                        <td>${item.current_stock}</td>
                        <td>${item.reserved_quantity || 0}</td>
                        <td>${item.min_stock_level}</td>
                        <td class="currency-value">${formatCurrency(item.cost_per_unit || 0)}</td>
                        <td class="currency-value">${formatCurrency(item.total_value || 0)}</td>
                        <td><span class="status-badge status-${getStockStatus(item)}">${getStockStatusLabel(item)}</span></td>
                    </tr>
                `).join('')}
            </tbody>`;
        
        $('#reportTable').html(tableHTML).DataTable({
            "order": [[4, "asc"]],
            "pageLength": 10,
            "dom": 'Bfrtip',
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
            "drawCallback": function(settings) {
                if (window.matchMedia('print').matches) {
                    this.api().page.len(-1).draw();
                }
            }
        });

        // Stock Level Distribution Chart
        const ctx1 = document.getElementById('chartOne').getContext('2d');
        chartOne = new Chart(ctx1, {
            type: 'pie',
            data: {
                labels: ['Healthy Stock', 'Low Stock', 'Out of Stock'],
                datasets: [{
                    data: [
                        data.summary.healthy_stock || 0,
                        data.summary.low_stock_items || 0,
                        data.summary.out_of_stock || 0
                    ],
                    backgroundColor: ['#28a745', '#ffc107', '#dc3545']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                title: {
                    display: true,
                    text: 'Raw Material Stock Distribution'
                }
            }
        });

        // Stock Movement Trend Chart
        const ctx2 = document.getElementById('chartTwo').getContext('2d');
        chartTwo = new Chart(ctx2, {
            type: 'line',
            data: {
                labels: data.movements.dates,
                datasets: [{
                    label: 'Stock In',
                    data: data.movements.stock_in,
                    borderColor: '#28a745',
                    fill: false
                }, {
                    label: 'Stock Out',
                    data: data.movements.stock_out,
                    borderColor: '#dc3545',
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                title: {
                    display: true,
                    text: 'Raw Material Movement Trend'
                },
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                }
            }
        });
    }

    function updatePurchaseOrderReport(data) {
        // Summary Cards - 3x2 grid layout for purchase order metrics
        const summaryHTML = `
            <div class="col-md-4 col-print-4">
                <div class="metric-card">
                    <div class="metric-value">${data.summary.total_orders || 0}</div>
                    <div class="metric-label">Total Orders</div>
                </div>
            </div>
            <div class="col-md-4 col-print-4">
                <div class="metric-card">
                    <div class="metric-value">${data.summary.paid_orders || 0}</div>
                    <div class="metric-label">Paid Orders</div>
                </div>
            </div>
            <div class="col-md-4 col-print-4">
                <div class="metric-card">
                    <div class="metric-value">${data.summary.unpaid_orders || 0}</div>
                    <div class="metric-label">Unpaid Orders</div>
                </div>
            </div>
            <div class="col-md-4 col-print-4">
                <div class="metric-card">
                    <div class="metric-value">${formatCurrency(data.summary.total_amount || 0)}</div>
                    <div class="metric-label">Total Amount</div>
                </div>
            </div>
            <div class="col-md-4 col-print-4">
                <div class="metric-card">
                    <div class="metric-value">${formatCurrency(data.summary.total_paid || 0)}</div>
                    <div class="metric-label">Total Paid</div>
                </div>
            </div>
            <div class="col-md-4 col-print-4">
                <div class="metric-card">
                    <div class="metric-value">${data.summary.total_suppliers || 0}</div>
                    <div class="metric-label">Total Suppliers</div>
                </div>
            </div>`;
        $('#summaryCards').html(summaryHTML);

        // Initialize DataTable for Purchase Orders
        if ($.fn.DataTable.isDataTable('#reportTable')) {
            $('#reportTable').DataTable().destroy();
        }

        const tableHTML = `
            <thead>
                <tr>
                    <th>PO Number</th>
                    <th>Date</th>
                    <th>Supplier</th>
                    <th>Sub Total</th>
                    <th>VAT</th>
                    <th>Grand Total</th>
                    <th>Paid Amount</th>
                    <th>Status</th>
                    <th>Created By</th>
                </tr>
            </thead>
            <tbody>
                ${data.orders.map(order => `
                    <tr>
                        <td>${order.purchase_number}</td>
                        <td>${order.purchase_date}</td>
                        <td>${order.supplier_name}</td>
                        <td class="currency-value">${formatCurrency(order.sub_total)}</td>
                        <td class="currency-value">${formatCurrency(order.vat_amount)}</td>
                        <td class="currency-value">${formatCurrency(order.grand_total)}</td>
                        <td class="currency-value">${formatCurrency(order.paid_amount)}</td>
                        <td><span class="status-badge status-${getPaymentStatusClass(order.payment_status)}">${order.payment_status}</span></td>
                        <td>${order.created_by}</td>
                    </tr>
                `).join('')}
            </tbody>`;
        
        $('#reportTable').html(tableHTML).DataTable({
            "order": [[1, "desc"]],
            "pageLength": 10,
            "dom": 'Bfrtip',
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
            "drawCallback": function(settings) {
                if (window.matchMedia('print').matches) {
                    this.api().page.len(-1).draw();
                }
            }
        });

        // Purchase Orders Distribution Chart
        const ctx1 = document.getElementById('chartOne').getContext('2d');
        chartOne = new Chart(ctx1, {
            type: 'pie',
            data: {
                labels: ['Paid', 'Unpaid', 'Partial'],
                datasets: [{
                    data: [
                        data.summary.paid_orders || 0,
                        data.summary.unpaid_orders || 0,
                        data.summary.partial_orders || 0
                    ],
                    backgroundColor: ['#28a745', '#dc3545', '#ffc107']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                title: {
                    display: true,
                    text: 'Purchase Orders Payment Status Distribution'
                }
            }
        });

        // Purchase Trend Chart
        const ctx2 = document.getElementById('chartTwo').getContext('2d');
        chartTwo = new Chart(ctx2, {
            type: 'line',
            data: {
                labels: data.trends.dates,
                datasets: [{
                    label: 'Order Count',
                    data: data.trends.counts,
                    borderColor: '#007bff',
                    fill: false,
                    yAxisID: 'y-axis-1'
                }, {
                    label: 'Total Amount (br)',
                    data: data.trends.amounts,
                    borderColor: '#28a745',
                    fill: false,
                    yAxisID: 'y-axis-2'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                title: {
                    display: true,
                    text: 'Purchase Orders Trend'
                },
                scales: {
                    yAxes: [{
                        id: 'y-axis-1',
                        type: 'linear',
                        position: 'left',
                        ticks: {
                            beginAtZero: true
                        },
                        scaleLabel: {
                            display: true,
                            labelString: 'Order Count'
                        }
                    }, {
                        id: 'y-axis-2',
                        type: 'linear',
                        position: 'right',
                        ticks: {
                            beginAtZero: true,
                            callback: function(value) {
                                return 'br' + formatCurrency(value);
                            }
                        },
                        scaleLabel: {
                            display: true,
                            labelString: 'Total Amount (br)'
                        }
                    }]
                }
            }
        });
    }

    function updateSalesOrderReport(data) {
        // Summary Cards - 3x2 grid layout for sales order metrics
        const summaryHTML = `
            <div class="col-md-4 col-print-4">
                <div class="metric-card">
                    <div class="metric-value">${data.summary.total_orders || 0}</div>
                    <div class="metric-label">Total Orders</div>
                </div>
            </div>
            <div class="col-md-4 col-print-4">
                <div class="metric-card">
                    <div class="metric-value">${data.summary.paid_orders || 0}</div>
                    <div class="metric-label">Paid Orders</div>
                </div>
            </div>
            <div class="col-md-4 col-print-4">
                <div class="metric-card">
                    <div class="metric-value">${data.summary.unpaid_orders || 0}</div>
                    <div class="metric-label">Unpaid Orders</div>
                </div>
            </div>
            <div class="col-md-4 col-print-4">
                <div class="metric-card">
                    <div class="metric-value">${formatCurrency(data.summary.total_amount || 0)}</div>
                    <div class="metric-label">Total Amount</div>
                </div>
            </div>
            <div class="col-md-4 col-print-4">
                <div class="metric-card">
                    <div class="metric-value">${formatCurrency(data.summary.total_paid || 0)}</div>
                    <div class="metric-label">Total Paid</div>
                </div>
            </div>
            <div class="col-md-4 col-print-4">
                <div class="metric-card">
                    <div class="metric-value">${data.summary.total_clients || 0}</div>
                    <div class="metric-label">Total Clients</div>
                </div>
            </div>`;
        $('#summaryCards').html(summaryHTML);

        // Initialize DataTable for Sales Orders
        if ($.fn.DataTable.isDataTable('#reportTable')) {
            $('#reportTable').DataTable().destroy();
        }

        const tableHTML = `
            <thead>
                <tr>
                    <th>Order Number</th>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Sub Total</th>
                    <th>Tax</th>
                    <th>Discount</th>
                    <th>Total</th>
                    <th>Paid Amount</th>
                    <th>Payment Status</th>
                    <th>Order Status</th>
                    <th>Created By</th>
                </tr>
            </thead>
            <tbody>
                ${data.orders.map(order => `
                    <tr>
                        <td>${order.order_number}</td>
                        <td>${order.order_date}</td>
                        <td>${order.client_name}</td>
                        <td class="currency-value">${formatCurrency(order.subtotal)}</td>
                        <td class="currency-value">${formatCurrency(order.tax_amount)}</td>
                        <td class="currency-value">${formatCurrency(order.discount_amount)}</td>
                        <td class="currency-value">${formatCurrency(order.total_amount)}</td>
                        <td class="currency-value">${formatCurrency(order.paid_amount)}</td>
                        <td><span class="status-badge status-${getPaymentStatusClass(order.payment_status)}">${order.payment_status}</span></td>
                        <td><span class="status-badge status-${getOrderStatusClass(order.order_status)}">${order.order_status}</span></td>
                        <td>${order.created_by}</td>
                    </tr>
                `).join('')}
            </tbody>`;
        
        $('#reportTable').html(tableHTML).DataTable({
            "order": [[1, "desc"]],
            "pageLength": 10,
            "dom": 'Bfrtip',
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
            "drawCallback": function(settings) {
                if (window.matchMedia('print').matches) {
                    this.api().page.len(-1).draw();
                }
            }
        });

        // Sales Orders Distribution Chart
        const ctx1 = document.getElementById('chartOne').getContext('2d');
        chartOne = new Chart(ctx1, {
            type: 'pie',
            data: {
                labels: ['Paid', 'Unpaid', 'Partial'],
                datasets: [{
                    data: [
                        data.summary.paid_orders || 0,
                        data.summary.unpaid_orders || 0,
                        data.summary.partial_orders || 0
                    ],
                    backgroundColor: ['#28a745', '#dc3545', '#ffc107']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                title: {
                    display: true,
                    text: 'Sales Orders Payment Status Distribution'
                }
            }
        });

        // Sales Trend Chart
        const ctx2 = document.getElementById('chartTwo').getContext('2d');
        chartTwo = new Chart(ctx2, {
            type: 'line',
            data: {
                labels: data.trends.dates,
                datasets: [{
                    label: 'Order Count',
                    data: data.trends.counts,
                    borderColor: '#007bff',
                    fill: false,
                    yAxisID: 'y-axis-1'
                }, {
                    label: 'Total Amount (br)',
                    data: data.trends.amounts,
                    borderColor: '#28a745',
                    fill: false,
                    yAxisID: 'y-axis-2'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                title: {
                    display: true,
                    text: 'Sales Orders Trend'
                },
                scales: {
                    yAxes: [{
                        id: 'y-axis-1',
                        type: 'linear',
                        position: 'left',
                        ticks: {
                            beginAtZero: true
                        },
                        scaleLabel: {
                            display: true,
                            labelString: 'Order Count'
                        }
                    }, {
                        id: 'y-axis-2',
                        type: 'linear',
                        position: 'right',
                        ticks: {
                            beginAtZero: true,
                            callback: function(value) {
                                return 'br' + formatCurrency(value);
                            }
                        },
                        scaleLabel: {
                            display: true,
                            labelString: 'Total Amount (br)'
                        }
                    }]
                }
            }
        });
    }

    function updateSalesPerformance(data) {
        // Summary Cards - 3x2 grid layout for sales performance metrics
        const summaryHTML = `
            <div class="col-md-4 col-print-4">
                <div class="metric-card">
                    <div class="metric-value">${data.summary.total_orders || 0}</div>
                    <div class="metric-label">Total Orders</div>
                </div>
            </div>
            <div class="col-md-4 col-print-4">
                <div class="metric-card">
                    <div class="metric-value">${data.summary.total_customers || 0}</div>
                    <div class="metric-label">Total Customers</div>
                </div>
            </div>
            <div class="col-md-4 col-print-4">
                <div class="metric-card">
                    <div class="metric-value">${formatCurrency(data.summary.total_revenue || 0)}</div>
                    <div class="metric-label">Total Revenue</div>
                </div>
            </div>
            <div class="col-md-4 col-print-4">
                <div class="metric-card">
                    <div class="metric-value">${formatCurrency(data.summary.total_collected || 0)}</div>
                    <div class="metric-label">Total Collected</div>
                </div>
            </div>
            <div class="col-md-4 col-print-4">
                <div class="metric-card">
                    <div class="metric-value">${formatCurrency(data.summary.total_outstanding || 0)}</div>
                    <div class="metric-label">Outstanding Amount</div>
                </div>
            </div>
            <div class="col-md-4 col-print-4">
                <div class="metric-card">
                    <div class="metric-value">${data.summary.payment_success_rate || 0}%</div>
                    <div class="metric-label">Payment Success Rate</div>
                </div>
            </div>`;
        $('#summaryCards').html(summaryHTML);

        // Initialize DataTable for Top Products
        if ($.fn.DataTable.isDataTable('#reportTable')) {
            $('#reportTable').DataTable().destroy();
        }

        const tableHTML = `
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Order Count</th>
                    <th>Total Quantity</th>
                    <th>Total Revenue</th>
                </tr>
            </thead>
            <tbody>
                ${data.topProducts.map(product => `
                    <tr>
                        <td>${product.product_name}</td>
                        <td>${product.order_count}</td>
                        <td>${product.total_quantity}</td>
                        <td class="currency-value">${formatCurrency(product.total_revenue)}</td>
                    </tr>
                `).join('')}
            </tbody>`;
        
        $('#reportTable').html(tableHTML).DataTable({
            "order": [[3, "desc"]],
            "pageLength": 5,
            "dom": 'Bfrtip',
            "lengthMenu": [[5, 10, 25, -1], [5, 10, 25, "All"]],
            "drawCallback": function(settings) {
                if (window.matchMedia('print').matches) {
                    this.api().page.len(-1).draw();
                }
            }
        });

        // Sales Trend Chart
        const ctx1 = document.getElementById('chartOne').getContext('2d');
        chartOne = new Chart(ctx1, {
            type: 'line',
            data: {
                labels: data.trends.dates,
                datasets: [{
                    label: 'Daily Revenue',
                    data: data.trends.revenue,
                    borderColor: '#28a745',
                    fill: false,
                    yAxisID: 'y-axis-1'
                }, {
                    label: 'Order Count',
                    data: data.trends.orders,
                    borderColor: '#007bff',
                    fill: false,
                    yAxisID: 'y-axis-2'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                title: {
                    display: true,
                    text: 'Sales Performance Trend'
                },
                scales: {
                    yAxes: [{
                        id: 'y-axis-1',
                        type: 'linear',
                        position: 'left',
                        ticks: {
                            beginAtZero: true,
                            callback: function(value) {
                                return 'br' + formatCurrency(value);
                            }
                        },
                        scaleLabel: {
                            display: true,
                            labelString: 'Revenue (br)'
                        }
                    }, {
                        id: 'y-axis-2',
                        type: 'linear',
                        position: 'right',
                        ticks: {
                            beginAtZero: true
                        },
                        scaleLabel: {
                            display: true,
                            labelString: 'Order Count'
                        }
                    }]
                }
            }
        });

        // Top Customers Chart
        const ctx2 = document.getElementById('chartTwo').getContext('2d');
        chartTwo = new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: data.topCustomers.map(customer => customer.customer_name),
                datasets: [{
                    label: 'Total Spent',
                    data: data.topCustomers.map(customer => customer.total_spent),
                    backgroundColor: '#17a2b8',
                    borderColor: '#17a2b8',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                title: {
                    display: true,
                    text: 'Top 10 Customers by Revenue'
                },
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            callback: function(value) {
                                return 'br' + formatCurrency(value);
                            }
                        }
                    }]
                }
            }
        });
    }

    function updateSalesAnalysis(data) {
        // Summary Cards - 3x2 grid layout for sales analysis metrics
        const summaryHTML = `
            <div class="col-md-4 col-print-4">
                <div class="metric-card">
                    <div class="metric-value">${data.summary.total_orders || 0}</div>
                    <div class="metric-label">Total Orders</div>
                </div>
            </div>
            <div class="col-md-4 col-print-4">
                <div class="metric-card">
                    <div class="metric-value">${formatCurrency(data.summary.total_revenue || 0)}</div>
                    <div class="metric-label">Total Revenue</div>
                </div>
            </div>
            <div class="col-md-4 col-print-4">
                <div class="metric-card">
                    <div class="metric-value">${formatCurrency(data.summary.avg_order_value || 0)}</div>
                    <div class="metric-label">Average Order Value</div>
                </div>
            </div>
            <div class="col-md-4 col-print-4">
                <div class="metric-card">
                    <div class="metric-value">${data.summary.unique_customers || 0}</div>
                    <div class="metric-label">Unique Customers</div>
                </div>
            </div>
            <div class="col-md-4 col-print-4">
                <div class="metric-card">
                    <div class="metric-value">${formatCurrency(data.summary.total_units_sold || 0)}</div>
                    <div class="metric-label">Total Units Sold</div>
                </div>
            </div>
            <div class="col-md-4 col-print-4">
                <div class="metric-card">
                    <div class="metric-value">${formatCurrency(data.summary.avg_unit_price || 0)}</div>
                    <div class="metric-label">Average Unit Price</div>
                </div>
            </div>`;
        $('#summaryCards').html(summaryHTML);

        // Initialize DataTable for Category Analysis
        if ($.fn.DataTable.isDataTable('#reportTable')) {
            $('#reportTable').DataTable().destroy();
        }

        const tableHTML = `
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Orders</th>
                    <th>Units Sold</th>
                    <th>Revenue</th>
                    <th>Average Price</th>
                </tr>
            </thead>
            <tbody>
                ${data.categoryAnalysis.map(category => `
                    <tr>
                        <td>${category.category_name || 'Uncategorized'}</td>
                        <td>${category.order_count}</td>
                        <td>${category.units_sold}</td>
                        <td class="currency-value">${formatCurrency(category.revenue)}</td>
                        <td class="currency-value">${formatCurrency(category.avg_price)}</td>
                    </tr>
                `).join('')}
            </tbody>`;
        
        $('#reportTable').html(tableHTML).DataTable({
            "order": [[3, "desc"]],
            "pageLength": 10,
            "dom": 'Bfrtip',
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
            "drawCallback": function(settings) {
                if (window.matchMedia('print').matches) {
                    this.api().page.len(-1).draw();
                }
            }
        });

        // Monthly Trends Chart
        const ctx1 = document.getElementById('chartOne').getContext('2d');
        chartOne = new Chart(ctx1, {
            type: 'line',
            data: {
                labels: data.monthlyTrends.months,
                datasets: [{
                    label: 'Revenue',
                    data: data.monthlyTrends.revenue,
                    borderColor: '#28a745',
                    fill: false,
                    yAxisID: 'y-axis-1'
                }, {
                    label: 'Average Order Value',
                    data: data.monthlyTrends.avg_order_value,
                    borderColor: '#007bff',
                    fill: false,
                    yAxisID: 'y-axis-1'
                }, {
                    label: 'Orders',
                    data: data.monthlyTrends.orders,
                    borderColor: '#ffc107',
                    fill: false,
                    yAxisID: 'y-axis-2'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                title: {
                    display: true,
                    text: 'Monthly Sales Trends'
                },
                scales: {
                    yAxes: [{
                        id: 'y-axis-1',
                        type: 'linear',
                        position: 'left',
                        ticks: {
                            beginAtZero: true,
                            callback: function(value) {
                                return 'br' + formatCurrency(value);
                            }
                        },
                        scaleLabel: {
                            display: true,
                            labelString: 'Amount (br)'
                        }
                    }, {
                        id: 'y-axis-2',
                        type: 'linear',
                        position: 'right',
                        ticks: {
                            beginAtZero: true
                        },
                        scaleLabel: {
                            display: true,
                            labelString: 'Order Count'
                        }
                    }]
                }
            }
        });

        // Payment Methods Chart
        const ctx2 = document.getElementById('chartTwo').getContext('2d');
        chartTwo = new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: data.paymentAnalysis.map(p => p.payment_method),
                datasets: [{
                    label: 'Transaction Count',
                    data: data.paymentAnalysis.map(p => p.transaction_count),
                    backgroundColor: '#17a2b8',
                    yAxisID: 'y-axis-1'
                }, {
                    label: 'Total Amount',
                    data: data.paymentAnalysis.map(p => p.total_amount),
                    backgroundColor: '#28a745',
                    yAxisID: 'y-axis-2'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                title: {
                    display: true,
                    text: 'Payment Method Analysis'
                },
                scales: {
                    yAxes: [{
                        id: 'y-axis-1',
                        type: 'linear',
                        position: 'left',
                        ticks: {
                            beginAtZero: true
                        },
                        scaleLabel: {
                            display: true,
                            labelString: 'Transaction Count'
                        }
                    }, {
                        id: 'y-axis-2',
                        type: 'linear',
                        position: 'right',
                        ticks: {
                            beginAtZero: true,
                            callback: function(value) {
                                return 'br' + formatCurrency(value);
                            }
                        },
                        scaleLabel: {
                            display: true,
                            labelString: 'Amount (br)'
                        }
                    }]
                }
            }
        });
    }

    function updateCostAnalysis(data) {
        let content = `
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Cost Analysis Summary</h4>
                            <div class="card-header-action">
                                <button class="btn btn-primary" onclick="printReport()">
                                    <i class="fas fa-print"></i> Print Report
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                                    <div class="card card-statistic-1">
                                        <div class="card-icon bg-primary">
                                            <i class="fas fa-dollar-sign"></i>
                                        </div>
                                        <div class="card-wrap">
                                            <div class="card-header">
                                                <h4>Total Material Cost</h4>
                                            </div>
                                            <div class="card-body">
                                                br ${numberWithCommas(data.summary.total_material_cost.toFixed(2))}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                                    <div class="card card-statistic-1">
                                        <div class="card-icon bg-danger">
                                            <i class="fas fa-trash"></i>
                                        </div>
                                        <div class="card-wrap">
                                            <div class="card-header">
                                                <h4>Total Wastage Cost</h4>
                                            </div>
                                            <div class="card-body">
                                                br ${numberWithCommas(data.summary.total_wastage_cost.toFixed(2))}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                                    <div class="card card-statistic-1">
                                        <div class="card-icon bg-warning">
                                            <i class="fas fa-box"></i>
                                        </div>
                                        <div class="card-wrap">
                                            <div class="card-header">
                                                <h4>Units Produced</h4>
                                            </div>
                                            <div class="card-body">
                                                br ${numberWithCommas(data.summary.total_units_produced)}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                                    <div class="card card-statistic-1">
                                        <div class="card-icon bg-success">
                                            <i class="fas fa-calculator"></i>
                                        </div>
                                        <div class="card-wrap">
                                            <div class="card-header">
                                                <h4>Cost Per Unit</h4>
                                            </div>
                                            <div class="card-body">
                                                br${numberWithCommas(data.summary.cost_per_unit.toFixed(2))}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-8">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4>Monthly Cost Trends</h4>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="costTrendsChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4>Additional Metrics</h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="statistic-details">
                                                <div class="statistic-details-item">
                                                    <div class="detail-value">br${numberWithCommas(data.summary.avg_wastage_percent)}%</div>
                                                    <div class="detail-name">Average Wastage</div>
                                                </div>
                                                <div class="statistic-details-item">
                                                    <div class="detail-value">${data.summary.total_orders}</div>
                                                    <div class="detail-name">Total Orders</div>
                                                </div>
                                                <div class="statistic-details-item">
                                                    <div class="detail-value">${data.summary.total_products}</div>
                                                    <div class="detail-name">Total Products</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4>Cost Analysis by Category</h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Category</th>
                                                            <th>Material Cost</th>
                                                            <th>Wastage Cost</th>
                                                            <th>Units Produced</th>
                                                            <th>Cost Per Unit</th>
                                                            <th>Avg. Wastage %</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        ${data.categoryAnalysis.map(category => `
                                                            <tr>
                                                                <td>${category.category_name}</td>
                                                                <td>br${numberWithCommas(category.material_cost.toFixed(2))}</td>
                                                                <td>br${numberWithCommas(category.wastage_cost.toFixed(2))}</td>
                                                                <td>${numberWithCommas(category.units_produced)}</td>
                                                                <td>br${numberWithCommas(category.cost_per_unit.toFixed(2))}</td>
                                                                <td>${category.avg_wastage}%</td>
                                                            </tr>
                                                        `).join('')}
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('#report-result').html(content);

        // Initialize the cost trends chart
        const ctx = document.getElementById('costTrendsChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.monthlyTrends.months,
                datasets: [{
                    label: 'Total Cost',
                    data: data.monthlyTrends.total_cost,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }, {
                    label: 'Wastage Cost',
                    data: data.monthlyTrends.wastage_cost,
                    borderColor: 'rgb(255, 99, 132)',
                    tension: 0.1
                }, {
                    label: 'Cost Per Unit',
                    data: data.monthlyTrends.cost_per_unit,
                    borderColor: 'rgb(153, 102, 255)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'br' + numberWithCommas(value);
                            }
                        }
                    }
                }
            }
        });
    }

    function updatePrintInfo(reportType, dateRange, startDate, endDate) {
        $('#printReportTitle').text($('#reportType option:selected').text());
        $('#printDate').text(new Date().toLocaleString());
        
        let dateRangeText = $('#dateRange option:selected').text();
        if (dateRange === 'custom') {
            dateRangeText = `${startDate} to ${endDate}`;
        }
        $('#printDateRange').text(dateRangeText);
    }

    // Handle Print
    $('#printReport').click(function() {
        // Update print header information before printing
        $('#printReportTitle').text($('#reportType option:selected').text());
        $('#printDate').text(new Date().toLocaleString());
        
        let dateRange = $('#dateRange').val();
        let dateRangeText = $('#dateRange option:selected').text();
        if (dateRange === 'custom') {
            dateRangeText = `${$('#startDate').val()} to ${$('#endDate').val()}`;
        }
        $('#printDateRange').text(dateRangeText);

        // Show print-only elements
        $('.print-only').show();
        
        // Ensure all data is visible in tables
        if ($.fn.DataTable.isDataTable('#reportTable')) {
            const dataTable = $('#reportTable').DataTable();
            dataTable.page.len(-1).draw();
        }

        // Print the document
        window.print();

        // Hide print-only elements after printing
        $('.print-only').hide();

        // Restore table pagination after printing
        if ($.fn.DataTable.isDataTable('#reportTable')) {
            const dataTable = $('#reportTable').DataTable();
            dataTable.page.len(10).draw();
        }
    });

    // Handle Excel Export
    $('#exportExcel').click(function() {
        const table = document.getElementById('reportTable');
        const wb = XLSX.utils.table_to_book(table);
        XLSX.writeFile(wb, 'report.xlsx');
    });

    // Handle PDF Export
    $('#exportPdf').click(function() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        doc.autoTable({ html: '#reportTable' });
        doc.save('report.pdf');
    });

    // Helper function to format currency values
    function formatCurrency(value) {
        return parseFloat(value).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    // Helper function to get payment status class
    function getPaymentStatusClass(status) {
        switch(status.toLowerCase()) {
            case 'paid': return 'completed';
            case 'unpaid': return 'cancelled';
            case 'partial': return 'pending';
            default: return 'pending';
        }
    }

    // Helper function to get order status class
    function getOrderStatusClass(status) {
        switch(status.toLowerCase()) {
            case 'completed': return 'completed';
            case 'cancelled': return 'cancelled';
            case 'pending': return 'pending';
            case 'processing': return 'pending';
            default: return 'pending';
        }
    }

    // Helper function to determine stock status
    function getStockStatus(item) {
        if (item.current_stock <= 0) return 'outofstock';
        if (item.current_stock <= item.min_stock_level) return 'lowstock';
        return 'healthy';
    }

    // Helper function to get status label
    function getStockStatusLabel(item) {
        if (item.current_stock <= 0) return 'Out of Stock';
        if (item.current_stock <= item.min_stock_level) return 'Low Stock';
        return 'Healthy';
    }

    // Helper function for number formatting
    function numberWithCommas(x) {
        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    // Trigger initial report generation
    $('#generateReport').click();
});
</script>

<?php require_once 'includes/footer.php'; ?> 