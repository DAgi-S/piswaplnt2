# Advanced Reporting System Implementation Guide

## Overview
This guide provides instructions for implementing a modern, responsive reporting system with print optimization, dynamic charts, and interactive data tables.

## File Structure
```
project/
├── reports/
│   ├── newreports.php          # Main reporting page
│   ├── php_action/
│   │   ├── core.php            # Core functionality
│   │   ├── fetchNewReports.php # Data fetching API
│   │   └── exportReport.php    # Export functionality
│   └── assets/
│       ├── css/
│       └── js/
```

## Required Dependencies
```html
<!-- CSS Dependencies -->
<link rel="stylesheet" href="../assets/plugins/datatables/jquery.dataTables.min.css">
<link rel="stylesheet" href="../assets/plugins/select2/css/select2.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.css">

<!-- JavaScript Dependencies -->
<script src="../assets/jquery/jquery.min.js"></script>
<script src="../assets/bootstrap/js/bootstrap.min.js"></script>
<script src="../assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../assets/plugins/select2/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
```

## CSS Implementation Guide

### 1. Regular View Styles
```css
/* Base Card Styles */
.report-card {
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    transition: transform 0.2s;
}

/* Metric Card Styles */
.metric-card {
    background: #fff;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Chart Container Styles */
.chart-container {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    min-height: 300px;
    position: relative;
}

/* Status Badge Styles */
.status-badge {
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 12px;
}
```

### 2. Print-Specific Styles
```css
@media print {
    /* Hide Non-Printable Elements */
    .no-print { display: none !important; }
    .print-only { display: block !important; }
    
    /* Print Layout Optimization */
    body {
        font-size: 11px;
        line-height: 1.3;
        margin: 0;
        padding: 0;
    }
    
    /* Print Header Styles */
    .print-header {
        text-align: center;
        margin-bottom: 15px;
        padding: 10px 0;
        border-bottom: 1px solid #ddd;
    }
    
    /* Metric Cards Print Layout */
    .metric-card {
        width: 33.33%;
        float: left;
        padding: 8px;
        border: 1px solid #ddd;
        page-break-inside: avoid;
    }
    
    /* Table Print Optimization */
    .table {
        font-size: 10px !important;
        width: 100% !important;
        margin: 0 !important;
    }
    
    /* Hide UI Elements in Print */
    .dataTables_length,
    .dataTables_filter,
    .dataTables_info,
    .dataTables_paginate,
    .btn-floating,
    [href*="pos.php"],
    .cart-fixed-button {
        display: none !important;
    }
}
```

## HTML Structure Guide

### 1. Main Container Structure
```html
<div class="panel panel-default">
    <!-- Filter Section -->
    <div class="filter-section no-print">
        <!-- Date Range and Report Type Filters -->
    </div>

    <!-- Print Header -->
    <div class="print-only">
        <!-- Print-specific header information -->
    </div>

    <!-- Report Content -->
    <div id="reportContent">
        <!-- Summary Cards (3x2 Grid) -->
        <div class="row clearfix" id="summaryCards"></div>
        
        <!-- Charts (2 Column Layout) -->
        <div class="row clearfix" id="chartsRow">
            <!-- Chart Containers -->
        </div>
        
        <!-- Data Table -->
        <div class="table-responsive">
            <!-- Report Table -->
        </div>
    </div>
</div>
```

### 2. Summary Cards Template
```javascript
const summaryHTML = `
    <div class="col-md-4 col-print-4">
        <div class="metric-card">
            <div class="metric-value">${value}</div>
            <div class="metric-label">${label}</div>
        </div>
    </div>`;
```

## JavaScript Implementation Guide

### 1. Initialize Components
```javascript
$(document).ready(function() {
    // Initialize Select2 for filters
    $('#reportType, #dateRange').select2();
    
    // Initialize date inputs
    const today = new Date().toISOString().split('T')[0];
    $('#startDate, #endDate').attr('max', today);
});
```

### 2. Chart Management
```javascript
// Chart instances
let chartOne = null;
let chartTwo = null;

// Destroy existing charts
function destroyCharts() {
    if (chartOne) chartOne.destroy();
    if (chartTwo) chartTwo.destroy();
    chartOne = chartTwo = null;
}
```

### 3. DataTable Configuration
```javascript
$('#reportTable').DataTable({
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
```

### 4. Print Event Handlers
```javascript
window.addEventListener('beforeprint', function() {
    const dataTable = $('#reportTable').DataTable();
    dataTable.page.len(-1).draw();
});

window.addEventListener('afterprint', function() {
    const dataTable = $('#reportTable').DataTable();
    dataTable.page.len(10).draw();
});
```

## Backend Implementation Guide

### 1. Data Structure
```php
// Example response structure
$response = [
    'success' => true,
    'summary' => [
        'total_orders' => 0,
        'completed_orders' => 0,
        'ongoing_orders' => 0,
        'avg_efficiency' => 0,
        'cancelled_orders' => 0,
        'total_quantity' => 0
    ],
    'orders' => [],
    'charts' => [
        'distribution' => [],
        'trend' => []
    ]
];
```

### 2. API Endpoint Structure
```php
// fetchNewReports.php
<?php
header('Content-Type: application/json');
require_once 'core.php';

$type = $_POST['type'] ?? '';
$dateRange = $_POST['dateRange'] ?? '';
$startDate = $_POST['startDate'] ?? '';
$endDate = $_POST['endDate'] ?? '';

// Implement data fetching logic
// Return JSON response
```

## Best Practices

1. **Print Optimization**
   - Use specific print media queries
   - Hide unnecessary UI elements
   - Optimize font sizes and spacing
   - Handle page breaks appropriately

2. **Performance**
   - Destroy charts before creating new ones
   - Use efficient data loading strategies
   - Implement proper error handling

3. **Responsive Design**
   - Use Bootstrap grid system
   - Implement mobile-friendly layouts
   - Handle different screen sizes

4. **Code Organization**
   - Separate concerns (HTML, CSS, JS)
   - Use meaningful variable names
   - Comment complex logic
   - Implement proper error handling

## Common Customization Points

1. **Metric Cards**
   - Adjust grid layout (currently 3x2)
   - Modify card styles
   - Add/remove metrics

2. **Charts**
   - Change chart types
   - Modify colors and labels
   - Adjust chart options

3. **Data Table**
   - Customize columns
   - Modify pagination
   - Adjust sorting options

4. **Filters**
   - Add custom filter options
   - Modify date range options
   - Add additional filter types

## Changelog
- Initial version: Advanced reporting system with print optimization
- Added: 3x2 metric card layout
- Added: Print-specific styles and optimizations
- Added: Dynamic chart management
- Added: Comprehensive data table features 