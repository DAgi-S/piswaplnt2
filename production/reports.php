<?php
require_once 'php_action/core.php';
require_once 'includes/header.php';
?>

<!-- Add print styles -->
<style>
    @media print {
        .no-print {
            display: none !important;
        }
        .print-only {
            display: block !important;
        }
        .print-full-width {
            width: 100% !important;
        }
        .print-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .production-template {
            padding: 20px;
            font-size: 12px;
        }
        .production-template table {
            width: 100%;
            border-collapse: collapse;
        }
        .production-template th, 
        .production-template td {
            border: 1px solid #000;
            padding: 5px;
        }
        .report-header {
            margin-bottom: 20px;
        }
        .report-header h2 {
            margin: 0;
            font-size: 24px;
        }
        .report-meta {
            margin: 10px 0;
            font-size: 14px;
        }
        .summary-cards {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .summary-card {
            border: 1px solid #000;
            padding: 10px;
            text-align: center;
            width: 23%;
        }
    }
</style>

<!-- Additional CSS for reports -->
<link rel="stylesheet" href="../assests/plugins/datatables/jquery.dataTables.min.css">
<link rel="stylesheet" href="../assests/plugins/select2/css/select2.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css">

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="fas fa-chart-line"></i> Reports Management
                </div>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-3 no-print">
                        <!-- Report Categories -->
                        <div class="list-group">
                            <h4>Production Reports</h4>
                            <a href="production_orders_report.php" class="list-group-item">
                                <i class="fas fa-clipboard-list"></i> Production Orders Report
                            </a>
                            <a href="#" class="list-group-item" data-report="production_efficiency">
                                <i class="fas fa-tachometer-alt"></i> Production Efficiency
                            </a>
                            <a href="#" class="list-group-item" data-report="material_consumption">
                                <i class="fas fa-boxes"></i> Material Consumption
                            </a>
                            
                            <h4 class="mt-4">Inventory Reports</h4>
                            <a href="#" class="list-group-item" data-report="raw_materials_stock">
                                <i class="fas fa-warehouse"></i> Raw Materials Stock
                            </a>
                            <a href="#" class="list-group-item" data-report="low_stock_alert">
                                <i class="fas fa-exclamation-triangle"></i> Low Stock Alert
                            </a>
                            <a href="#" class="list-group-item" data-report="material_movements">
                                <i class="fas fa-exchange-alt"></i> Material Movements
                            </a>
                            
                            <h4 class="mt-4">Quality Reports</h4>
                            <a href="#" class="list-group-item" data-report="quality_inspection">
                                <i class="fas fa-check-circle"></i> Quality Inspection Report
                            </a>
                            <a href="#" class="list-group-item" data-report="defect_analysis">
                                <i class="fas fa-exclamation-circle"></i> Defect Analysis
                            </a>
                            
                            <h4 class="mt-4">Cost Reports</h4>
                            <a href="#" class="list-group-item" data-report="production_cost">
                                <i class="fas fa-dollar-sign"></i> Production Cost Analysis
                            </a>
                            <a href="#" class="list-group-item" data-report="material_cost">
                                <i class="fas fa-coins"></i> Material Cost Analysis
                            </a>
                        </div>
                    </div>
                    
                    <div class="col-md-9 print-full-width">
                        <!-- Report Content Area -->
                        <div id="reportContent">
                            <div class="panel panel-default">
                                <div class="panel-heading no-print">
                                    <h3 class="panel-title">
                                        <span id="reportTitle">Select a Report</span>
                                        <div class="pull-right">
                                            <!-- Filter Options -->
                                            <div class="btn-group" id="reportFilters" style="display: none;">
                                                <select class="form-control input-sm" id="dateRange" style="display: inline-block; width: auto;">
                                                    <option value="today">Today</option>
                                                    <option value="yesterday">Yesterday</option>
                                                    <option value="last7days">Last 7 Days</option>
                                                    <option value="last30days">Last 30 Days</option>
                                                    <option value="thisMonth">This Month</option>
                                                    <option value="lastMonth">Last Month</option>
                                                    <option value="custom">Custom Range</option>
                                                </select>
                                                <div id="customDateRange" style="display: none; margin: 0 10px;">
                                                    <input type="date" class="form-control input-sm" id="startDate">
                                                    <input type="date" class="form-control input-sm" id="endDate">
                                                </div>
                                                <button class="btn btn-sm btn-primary" id="generateReport">
                                                    <i class="fas fa-sync"></i> Generate
                                                </button>
                                            </div>
                                            <!-- Export Options -->
                                            <button class="btn btn-sm btn-info" id="printReport">
                                                <i class="fas fa-print"></i> Print
                                            </button>
                                            <button class="btn btn-sm btn-success" id="exportExcel">
                                                <i class="fas fa-file-excel"></i> Export Excel
                                            </button>
                                            <button class="btn btn-sm btn-danger" id="exportPdf">
                                                <i class="fas fa-file-pdf"></i> Export PDF
                                            </button>
                                        </div>
                                    </h3>
                                </div>
                                <div class="panel-body">
                                    <!-- Print Header - Only visible when printing -->
                                    <div class="print-only print-header" style="display: none;">
                                        <h2>Production Management System</h2>
                                        <div class="report-meta">
                                            <p>Generated on: <span id="reportDate"></span></p>
                                            <p>Report Type: <span id="printReportTitle"></span></p>
                                            <p>Date Range: <span id="printDateRange"></span></p>
                                        </div>
                                    </div>
                                    
                                    <!-- Report Data Container -->
                                    <div id="reportData">
                                        <div class="text-center text-muted">
                                            <i class="fas fa-chart-bar fa-4x mb-3"></i>
                                            <h4>Please select a report from the menu</h4>
                                            <p>Choose a report type from the left menu to view detailed information</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.5/xlsx.full.min.js"></script>

<!-- Initialize Reports -->
<script>
$(document).ready(function() {
    // Initialize Bootstrap dropdowns
    $('.dropdown-toggle').dropdown();
    
    // Initialize toastr
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "timeOut": "5000"
    };

    // Handle date range selection
    $('#dateRange').change(function() {
        if ($(this).val() === 'custom') {
            $('#customDateRange').show();
        } else {
            $('#customDateRange').hide();
        }
    });

    // Handle report selection
    $('.list-group-item').click(function(e) {
        e.preventDefault();
        $('.list-group-item').removeClass('active');
        $(this).addClass('active');
        
        const reportType = $(this).data('report');
        $('#reportTitle').text($(this).text());
        $('#printReportTitle').text($(this).text());
        $('#reportDate').text(new Date().toLocaleString());
        
        // Show filters for reports that need them
        if (reportType) {
            $('#reportFilters').show();
            loadReport(reportType);
        }
    });

    // Handle report generation
    $('#generateReport').click(function() {
        const activeReport = $('.list-group-item.active').data('report');
        if (activeReport) {
            loadReport(activeReport);
        }
    });

    // Handle print
    $('#printReport').click(function() {
        window.print();
    });

    // Handle Excel export
    $('#exportExcel').click(function() {
        const activeReport = $('.list-group-item.active').data('report');
        if (activeReport) {
            const params = getFilterParams();
            window.location.href = `php_action/exportReport.php?type=${activeReport}&${params}`;
        }
    });

    // Handle PDF export
    $('#exportPdf').click(function() {
        const activeReport = $('.list-group-item.active').data('report');
        if (activeReport) {
            const params = getFilterParams();
            window.location.href = `php_action/exportReportPdf.php?type=${activeReport}&${params}`;
        }
    });

    // Helper function to get filter parameters
    function getFilterParams() {
        const dateRange = $('#dateRange').val();
        let params = `dateRange=${dateRange}`;
        
        if (dateRange === 'custom') {
            const startDate = $('#startDate').val();
            const endDate = $('#endDate').val();
            
            if (!startDate || !endDate) {
                toastr.error('Please select both start and end dates for custom range');
                return false;
            }
            
            if (new Date(startDate) > new Date(endDate)) {
                toastr.error('Start date cannot be after end date');
                return false;
            }
            
            params += `&startDate=${startDate}&endDate=${endDate}`;
        }
        
        return params;
    }

    // Function to load report data
    function loadReport(reportType) {
        const params = getFilterParams();
        if (params === false) return;

        $.ajax({
            url: 'php_action/getReport.php',
            type: 'POST',
            data: {
                type: reportType,
                ...Object.fromEntries(new URLSearchParams(params))
            },
            beforeSend: function() {
                $('#reportData').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-3x"></i><p>Loading report data...</p></div>');
            },
            success: function(response) {
                if (!response.success) {
                    toastr.error(response.error || 'Error loading report');
                    return;
                }

                $('#reportData').html(response.html);
                $('#printDateRange').text(response.dateRange);
                
                // Initialize any DataTables in the response
                if ($.fn.DataTable.isDataTable('#reportTable')) {
                    $('#reportTable').DataTable().destroy();
                }
                
                $('#reportTable').DataTable({
                    "order": [[0, "desc"]],
                    "pageLength": 25,
                    "responsive": true,
                    "dom": 'Bfrtip',
                    "buttons": [
                        'copy', 'csv', 'excel', 'pdf', 'print'
                    ]
                });

                // Update summary cards if available
                if (response.summary) {
                    Object.keys(response.summary).forEach(key => {
                        const value = response.summary[key];
                        const $card = $(`.summary-card:contains("${key}")`);
                        if ($card.length) {
                            $card.find('h3').text(value);
                        }
                    });
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = 'Error loading report';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMessage = response.error || errorMessage;
                } catch (e) {
                    errorMessage = error || errorMessage;
                }
                toastr.error(errorMessage);
            }
        });
    }

    // Initialize date inputs with current date
    const today = new Date();
    const todayStr = today.toISOString().split('T')[0];
    $('#startDate, #endDate').attr('max', todayStr);
    $('#startDate').on('change', function() {
        $('#endDate').attr('min', $(this).val());
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 