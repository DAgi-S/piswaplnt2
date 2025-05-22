<?php
require_once 'php_action/core.php';
require_once 'php_action/db_connect.php';

// Set the page title
$title = "Category Transaction Report";

// Check if user is logged in and has permission
if (!isset($_SESSION['userId']) || !hasPermission('view_accounts')) {
    header('location: index.php');
    exit();
}

// Include header before account checks
require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <!-- Breadcrumb only shows in screen view -->
        <ol class="breadcrumb no-print">
            <li><a href="dashboard.php">Home</a></li>
            <li><a href="accounts.php">Accounts</a></li>
            <li class="active">Category Report</li>
        </ol>

        <?php
        // Check if active account is set
        if (!isset($_SESSION['active_account'])) {
            echo '<div class="alert alert-warning">Please select an account first from the <a href="accounts.php">Accounts page</a>.</div>';
        } else {
            // Get current account data
            $sql = "SELECT a.* FROM accounts a WHERE a.id = ?";
            $stmt = $connect->prepare($sql);
            $stmt->bind_param("i", $_SESSION['active_account']);
            $stmt->execute();
            $result = $stmt->get_result();
            $accountData = $result->fetch_assoc();

            if (!$accountData) {
                echo '<div class="alert alert-warning">Selected account not found. Please select another account from the <a href="accounts.php">Accounts page</a>.</div>';
            } else {
        ?>

        <div class="container">
            <button onclick="window.print()" class="btn btn-primary print-button no-print">
                <i class="fa fa-print"></i> Print Report
            </button>

            <!-- Filters Section -->
            <div class="filters-section no-print">
                <div class="row">
                    <div class="col-md-2">
                        <h5>Start Year</h5>
                        <select id="start-year" class="form-control">
                            <?php
                            $currentYear = date('Y');
                            $startYear = 2020;
                            for($year = $currentYear; $year >= $startYear; $year--) {
                                echo "<option value='$year'>$year</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <h5>End Year</h5>
                        <select id="end-year" class="form-control">
                            <?php
                            for($year = $currentYear; $year >= $startYear; $year--) {
                                $selected = ($year == $currentYear) ? 'selected' : '';
                                echo "<option value='$year' $selected>$year</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <h5>Category</h5>
                        <select id="category-select" class="form-control">
                            <option value="">All Categories</option>
                            <?php
                            $sql = "SELECT * FROM digital_categories ORDER BY category_name ASC";
                            $result = $connect->query($sql);
                            while($row = $result->fetch_assoc()) {
                                echo "<option value='".$row['category_id']."'>".$row['category_name']."</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <h5>&nbsp;</h5>
                        <button id="generate-report" class="btn btn-primary">Generate Report</button>
                    </div>
                </div>
            </div>

            <div id="printable-area">
                <div class="report-header">
                    <h2>CATEGORY TRANSACTION REPORT</h2>
                    <div class="account-info">
                        <p><strong>Account Owner:</strong> <?php echo htmlspecialchars($accountData['account_owner']); ?></p>
                        <p><strong>Platform:</strong> <?php echo htmlspecialchars($accountData['account_platform']); ?></p>
                        <p><strong>Currency:</strong> <?php echo htmlspecialchars($accountData['Currency']); ?></p>
                        <p><strong>Period:</strong> <span id="report-period"></span></p>
                        <p><strong>Category:</strong> <span id="report-category">All Categories</span></p>
                    </div>
                </div>

                <!-- Annual Summary -->
                <div class="annual-summary">
                    <h3>Annual Summary</h3>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th class="text-right">Total Amount</th>
                                    <th class="text-right">Transaction Count</th>
                                </tr>
                            </thead>
                            <tbody id="annual-summary-body">
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Monthly Sections -->
                <?php
                $months = [
                    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                ];
                ?>
                <div id="monthly-sections">
                    <!-- Monthly sections will be populated by JavaScript -->
                </div>
            </div>
        </div>

        <style>
        .report-header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #333;
        }
        .report-header h2 {
            font-size: 18px;
            margin-bottom: 10px;
            font-weight: bold;
            color: #333;
        }
        .account-info {
            font-size: 12px;
            margin-bottom: 5px;
        }
        .account-info p {
            margin-bottom: 3px;
        }
        .month-section {
            margin-bottom: 20px;
        }
        .month-section h4 {
            font-size: 14px;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #666;
            color: #333;
        }
        .category-table {
            margin-bottom: 0;
            font-size: 10px;
        }
        .category-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .filters-section {
            background: #fff;
            padding: 15px;
            margin-bottom: 20px;
        }
        .print-button {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
        }
        .annual-summary {
            margin-bottom: 20px;
        }
        .annual-summary h3 {
            font-size: 16px;
            margin-bottom: 10px;
            color: #333;
        }
        .breadcrumb {
            display: none !important;
        }
        @media print {
            body {
                background: white;
                font-size: 10px;
                margin: 0;
                padding: 0;
            }
            .container {
                width: 100% !important;
                max-width: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .no-print, .breadcrumb {
                display: none !important;
            }
            .month-section {
                page-break-inside: avoid;
                margin-bottom: 15px;
                padding: 0;
            }
            .category-table {
                border-collapse: collapse;
                width: 100%;
            }
            .category-table th,
            .category-table td {
                padding: 4px;
                border: 1px solid #ddd;
                font-size: 10px;
            }
            .category-table th {
                background-color: #f5f5f5 !important;
                -webkit-print-color-adjust: exact;
            }
            .print-button {
                display: none;
            }
            .report-header {
                margin-bottom: 20px;
            }
            .report-header h2 {
                font-size: 16px;
                margin-bottom: 10px;
            }
            .account-info {
                font-size: 10px;
            }
            .account-info p {
                margin-bottom: 2px;
            }
            .annual-summary table,
            .category-table {
                width: 100% !important;
            }
            .annual-summary th,
            .annual-summary td {
                padding: 4px;
                font-size: 10px;
            }
            @page {
                margin: 1cm;
                size: A4;
            }
            .table-responsive {
                overflow-x: visible !important;
            }
            .row {
                margin: 0 !important;
            }
            .col-md-12 {
                padding: 0 !important;
            }
        }
        .year-section {
            margin-bottom: 30px;
        }
        .year-header {
            background-color: #f8f9fa;
            padding: 10px 15px;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
        }
        .year-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }
        @media print {
            .year-header {
                background-color: #f8f9fa !important;
                -webkit-print-color-adjust: exact;
                padding: 8px;
                margin-bottom: 15px;
            }
            .year-section {
                margin-bottom: 20px;
            }
        }
        </style>

        <!-- Add DataTables CSS -->
        <link rel="stylesheet" href="assests/plugins/datatables/jquery.dataTables.min.css">
        <link rel="stylesheet" href="assests/plugins/datatables/buttons.dataTables.min.css">
        <link rel="stylesheet" href="assests/plugins/select2/select2.min.css">

        <!-- Add DataTables JS -->
        <script src="assests/plugins/datatables/jquery.dataTables.min.js"></script>
        <script src="assests/plugins/datatables/dataTables.buttons.min.js"></script>
        <script src="assests/plugins/datatables/buttons.flash.min.js"></script>
        <script src="assests/plugins/datatables/jszip.min.js"></script>
        <script src="assests/plugins/datatables/pdfmake.min.js"></script>
        <script src="assests/plugins/datatables/vfs_fonts.js"></script>
        <script src="assests/plugins/datatables/buttons.html5.min.js"></script>
        <script src="assests/plugins/datatables/buttons.print.min.js"></script>
        <script src="assests/plugins/select2/select2.min.js"></script>

        <script>
        $(document).ready(function() {
            // Initialize select2 for better dropdown experience
            $('#start-year, #end-year, #category-select').select2();

            // Validate year range selection
            $('#start-year, #end-year').on('change', function() {
                var startYear = parseInt($('#start-year').val());
                var endYear = parseInt($('#end-year').val());
                
                if (startYear > endYear) {
                    $('#end-year').val(startYear).trigger('change');
                }
            });

            function formatCurrency(amount) {
                return parseFloat(amount).toFixed(2);
            }

            function generateReport() {
                const startYear = $('#start-year').val();
                const endYear = $('#end-year').val();
                const categoryId = $('#category-select').val();
                $('#report-period').text(startYear + (startYear !== endYear ? ' - ' + endYear : ''));
                $('#report-category').text($('#category-select option:selected').text());

                // Reset all tables
                $('#annual-summary-body').empty();
                $('#monthly-sections').empty();

                // Fetch data for the selected year range and category
                $.ajax({
                    url: 'php_action/fetchCategoryReport.php',
                    type: 'POST',
                    data: { 
                        startYear: startYear,
                        endYear: endYear,
                        categoryId: categoryId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.error) {
                            alert(response.message);
                            return;
                        }

                        // Update annual summary
                        response.annual_summary.forEach(function(category) {
                            const row = `<tr>
                                <td>${category.category_name}</td>
                                <td class="text-right">${formatCurrency(category.total_amount)}</td>
                                <td class="text-right">${category.transaction_count}</td>
                            </tr>`;
                            $('#annual-summary-body').append(row);
                        });

                        // Process monthly data grouped by year
                        const months = {
                            1: 'January', 2: 'February', 3: 'March', 4: 'April',
                            5: 'May', 6: 'June', 7: 'July', 8: 'August',
                            9: 'September', 10: 'October', 11: 'November', 12: 'December'
                        };

                        // Group transactions by year
                        const yearlyData = {};
                        response.monthly.forEach(function(month) {
                            month.transactions.forEach(function(trans) {
                                const year = trans.date.substring(0, 4);
                                if (!yearlyData[year]) {
                                    yearlyData[year] = {};
                                }
                                if (!yearlyData[year][month.month]) {
                                    yearlyData[year][month.month] = {
                                        transactions: [],
                                        total: 0
                                    };
                                }
                                yearlyData[year][month.month].transactions.push(trans);
                                yearlyData[year][month.month].total += parseFloat(trans.amount);
                            });
                        });

                        // Generate HTML for each year
                        Object.keys(yearlyData).sort().reverse().forEach(function(year) {
                            let yearHtml = `
                                <div class="year-section">
                                    <div class="year-header">
                                        <h3>${year}</h3>
                                    </div>`;

                            Object.keys(months).forEach(function(month) {
                                const monthData = yearlyData[year][month] || { transactions: [], total: 0 };
                                if (monthData.transactions.length > 0) {
                                    yearHtml += `
                                        <div class="month-section">
                                            <h4>${months[month]}</h4>
                                            <div class="table-responsive">
                                                <table class="table table-bordered category-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Date</th>
                                                            <th>Type</th>
                                                            <th>Name</th>
                                                            <th>Platform</th>
                                                            <th>Category</th>
                                                            <th>Comment</th>
                                                            <th class="text-right">Amount</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>`;

                                    monthData.transactions.forEach(function(trans) {
                                        yearHtml += `
                                            <tr>
                                                <td>${trans.date}</td>
                                                <td>${trans.type}</td>
                                                <td>${trans.name}</td>
                                                <td>${trans.platform}</td>
                                                <td>${trans.category}</td>
                                                <td>${trans.comment || ''}</td>
                                                <td class="text-right">${formatCurrency(trans.amount)}</td>
                                            </tr>`;
                                    });

                                    yearHtml += `
                                                    </tbody>
                                                    <tfoot>
                                                        <tr>
                                                            <td colspan="6" class="text-right"><strong>Monthly Total:</strong></td>
                                                            <td class="text-right"><strong>${formatCurrency(monthData.total)}</strong></td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>`;
                                }
                            });

                            yearHtml += '</div>';
                            $('#monthly-sections').append(yearHtml);
                        });
                    },
                    error: function() {
                        alert('An error occurred while generating the report');
                    }
                });
            }

            $('#generate-report').click(generateReport);
            generateReport(); // Generate report for current year on page load
        });
        </script>

        <?php
            }
        }
        ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 