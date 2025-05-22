<?php
// Include header
require_once 'includes/header.php';
require_once 'php_action/core.php';
require_once 'includes/auth_check.php';

// Check if user has permission to view expenses
$userId = $_SESSION['userId'];

// Get user's role
$roleQuery = "SELECT role_id FROM users WHERE user_id = ?";
$stmt = $connect->prepare($roleQuery);
$stmt->bind_param('i', $userId);
$stmt->execute();
$roleResult = $stmt->get_result();
$roleRow = $roleResult->fetch_assoc();

if (!$roleRow) {
    // No role assigned
    header('Location: dashboard.php');
    exit();
}

$userRole = $roleRow['role_id'];
$_SESSION['userRole'] = $userRole; // Update session

$sql = "SELECT COUNT(*) as count FROM role_permissions rp 
        INNER JOIN permissions p ON rp.permission_id = p.permission_id 
        WHERE rp.role_id = ? AND p.permission_name = 'view_expense'";
$stmt = $connect->prepare($sql);
$stmt->bind_param('i', $userRole);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    header('Location: dashboard.php');
    exit();
}

// Check for create permission to show/hide Add Expense button
$canCreateExpense = false;
$sql = "SELECT COUNT(*) as count FROM role_permissions rp 
        INNER JOIN permissions p ON rp.permission_id = p.permission_id 
        WHERE rp.role_id = ? AND p.permission_name = 'create_expense'";
$stmt = $connect->prepare($sql);
$stmt->bind_param('i', $userRole);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$canCreateExpense = ($row['count'] > 0);
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h2><i class="fa fa-money"></i> Expense Management</h2>
            <ol class="breadcrumb">
                <li><a href="dashboard.php">Home</a></li>
                <li class="active">Expense Management</li>
            </ol>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row">
        <div class="col-md-3">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title">Total Expenses</h3>
                </div>
                <div class="panel-body">
                    <h2 class="text-center" id="totalExpenses">Loading...</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="panel panel-warning">
                <div class="panel-heading">
                    <h3 class="panel-title">Pending Expenses</h3>
                </div>
                <div class="panel-body">
                    <h2 class="text-center" id="pendingExpenses">Loading...</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <h3 class="panel-title">Budget Utilization</h3>
                </div>
                <div class="panel-body">
                    <h2 class="text-center" id="budgetUtilization">Loading...</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="panel panel-success">
                <div class="panel-heading">
                    <h3 class="panel-title">Average Daily</h3>
                </div>
                <div class="panel-body">
                    <h2 class="text-center" id="averageDaily">Loading...</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Monthly Expense Trend</h3>
                </div>
                <div class="panel-body">
                    <canvas id="expenseTrendChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Category Distribution</h3>
                </div>
                <div class="panel-body">
                    <canvas id="categoryDistributionChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Expenses Table -->
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Recent Expenses</h3>
                    <?php if ($canCreateExpense): ?>
                    <div class="pull-right">
                        <button class="btn btn-primary btn-sm" id="addExpenseBtn">
                            <i class="fa fa-plus"></i> Add Expense
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="expensesTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Expense Modal -->
<div class="modal fade" id="expenseModal" tabindex="-1" role="dialog" aria-labelledby="expenseModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="expenseModalLabel">Add/Edit Expense</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="expenseForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" id="expenseId" name="expenseId">
                    
                    <div class="form-group">
                        <label for="expenseDate">Date</label>
                        <input type="date" class="form-control" id="expenseDate" name="expenseDate" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="expenseCategory">Category</label>
                        <select class="form-control" id="expenseCategory" name="expenseCategory" required>
                            <option value="">Select Category</option>
                        </select>
                        <small class="text-muted category-budget"></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="expenseDescription">Description</label>
                        <textarea class="form-control" id="expenseDescription" name="expenseDescription" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="expenseAmount">Amount</label>
                        <input type="number" step="0.01" class="form-control" id="expenseAmount" name="expenseAmount" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="expensePaymentMethod">Payment Method</label>
                        <select class="form-control" id="expensePaymentMethod" name="expensePaymentMethod" required>
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="check">Check</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="expenseAttachment">Attachment</label>
                        <input type="file" class="form-control-file" id="expenseAttachment" name="expenseAttachment">
                        <div id="currentAttachment"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Include footer -->
<?php require_once 'includes/footer.php'; ?>

<!-- Custom JavaScript -->
<script>
$(document).ready(function() {
    // Initialize variables for charts
    var trendChart = null;
    var distributionChart = null;
    var currencySettings = null;

    // Function to format currency based on settings
    function formatCurrency(amount) {
        if (!currencySettings) return amount;
        
        const value = parseFloat(amount).toFixed(currencySettings.decimals);
        const [integerPart, decimalPart] = value.split('.');
        
        const formattedInteger = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, currencySettings.thousandSeparator);
        const formattedNumber = decimalPart 
            ? formattedInteger + currencySettings.decimalSeparator + decimalPart 
            : formattedInteger;

        if (currencySettings.position === 'left') {
            return currencySettings.symbol + formattedNumber;
        } else if (currencySettings.position === 'left_space') {
            return currencySettings.symbol + ' ' + formattedNumber;
        } else if (currencySettings.position === 'right') {
            return formattedNumber + currencySettings.symbol;
        } else {
            return formattedNumber + ' ' + currencySettings.symbol;
        }
    }

    // Load currency settings
    function loadCurrencySettings() {
        return $.ajax({
            url: 'ajax/get_currency_settings.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    currencySettings = response.settings;
                } else {
                    console.error('Error loading currency settings:', response.error);
                    toastr.error('Error loading currency settings');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading currency settings:', error);
                toastr.error('Error loading currency settings');
            }
        });
    }

    // Function to load dashboard data
    function loadDashboardData() {
        $.ajax({
            url: 'ajax/get_dashboard_data.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.error) {
                    toastr.error(response.error);
                    return;
                }
                
                // Update summary cards with currency formatting
                $('#totalExpenses').html(formatCurrency(response.totalExpenses));
                $('#pendingExpenses').html(response.pendingExpenses);
                $('#budgetUtilization').html(response.budgetUtilization);
                $('#averageDaily').html(formatCurrency(response.averageDaily));
                
                // Check if charts data exists before updating
                if (response.charts) {
                    updateCharts(response.charts);
                } else {
                    console.error('Chart data is missing from the response');
                    toastr.error('Error loading chart data');
                }
            },
            error: function(xhr, status, error) {
                console.error("Error loading dashboard data:", error);
                toastr.error("Error loading dashboard data");
            }
        });
    }

    // Function to update charts
    function updateCharts(chartData) {
        if (window.trendChart) {
            window.trendChart.destroy();
        }
        if (window.distributionChart) {
            window.distributionChart.destroy();
        }

        // Create trend chart if data exists
        if (chartData.trend && document.getElementById('expenseTrendChart')) {
            var trendCtx = document.getElementById('expenseTrendChart').getContext('2d');
            window.trendChart = new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: chartData.trend.labels || [],
                    datasets: [{
                        label: 'Monthly Expenses',
                        data: chartData.trend.datasets[0].data || [],
                        borderColor: '#3498db',
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
                                    return formatCurrency(context.parsed.y);
                                }
                            }
                        }
                    }
                }
            });
        }

        // Create distribution chart if data exists
        if (chartData.distribution && document.getElementById('categoryDistributionChart')) {
            var distributionCtx = document.getElementById('categoryDistributionChart').getContext('2d');
            window.distributionChart = new Chart(distributionCtx, {
                type: 'pie',
                data: {
                    labels: chartData.distribution.labels || [],
                    datasets: [{
                        data: chartData.distribution.datasets[0].data || [],
                        backgroundColor: chartData.distribution.datasets[0].backgroundColor || []
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    var value = context.parsed || 0;
                                    return label + ': ' + formatCurrency(value);
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    // Initialize by loading currency settings first, then dashboard data
    loadCurrencySettings().then(function() {
        loadDashboardData();
        
        // Refresh data every 5 minutes
        setInterval(loadDashboardData, 300000);
    });

    // Initialize DataTable with proper configuration
    var expensesTable = $('#expensesTable').DataTable({
        "processing": true,
        "serverSide": false, // Since we're handling all data at once
        "ajax": {
            "url": "ajax/get_expenses.php",
            "type": "GET",
            "dataSrc": function(json) {
                if (json.error) {
                    toastr.error(json.error);
                    return [];
                }
                return json.data;
            },
            "error": function(xhr, error, thrown) {
                console.error("DataTables error:", error);
                toastr.error("Error loading expense data");
            }
        },
        "columns": [
            { "data": "date" },
            { "data": "category" },
            { "data": "description" },
            { "data": "amount" },
            { "data": "status" },
            { "data": "actions" }
        ],
        "order": [[0, "desc"]],
        "language": {
            "processing": "Loading...",
            "emptyTable": "No expenses found",
            "zeroRecords": "No matching expenses found"
        },
        "drawCallback": function(settings) {
            // Reinitialize tooltips after table draw
            $('[data-toggle="tooltip"]').tooltip();
        }
    });

    // Add error handling for AJAX calls
    $(document).ajaxError(function(event, jqxhr, settings, thrownError) {
        if (jqxhr.status === 403) {
            toastr.error("Access denied - Please check your permissions");
        } else {
            toastr.error("An error occurred while processing your request");
        }
    });

    // Add expense button click handler
    $('#addExpenseBtn').click(function() {
        $('#expenseId').val('');
        $('#expenseModal').modal('show');
    });

    // Load expense categories
    function loadExpenseCategories() {
        $.ajax({
            url: 'ajax/get_expense_categories.php',
            type: 'GET',
            success: function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    var categorySelect = $('#expenseCategory');
                    categorySelect.empty();
                    categorySelect.append('<option value="">Select Category</option>');
                    
                    data.data.forEach(function(category) {
                        categorySelect.append(
                            $('<option></option>')
                                .val(category.id)
                                .text(category.name)
                                .attr('data-budget', category.budget_limit)
                                .attr('data-description', category.description)
                        );
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading categories:', error);
                showAlert('error', 'Failed to load expense categories');
            }
        });
    }

    // Load categories when modal opens
    $('#expenseModal').on('show.bs.modal', function(e) {
        loadExpenseCategories();
    });

    // Update budget info when category changes
    $('#expenseCategory').change(function() {
        var selectedOption = $(this).find('option:selected');
        var budget = selectedOption.data('budget');
        var description = selectedOption.data('description');
        
        if (budget) {
            $('.category-budget').text('Budget Limit: $' + budget + ' - ' + description);
        } else {
            $('.category-budget').text('');
        }
    });

    // Handle form submission
    $('#expenseForm').submit(function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        
        $.ajax({
            url: 'ajax/save_expense.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    $('#expenseModal').modal('hide');
                    $('#expensesTable').DataTable().ajax.reload();
                    showAlert('success', data.message);
                } else {
                    showAlert('error', data.error || 'Failed to save expense');
                }
            },
            error: function(xhr, status, error) {
                showAlert('error', 'Failed to save expense');
            }
        });
    });

    // Handle action buttons
    $(document).on('click', '.edit-expense', function() {
        var expenseId = $(this).data('id');
        // Fetch expense details
        $.ajax({
            url: 'ajax/get_expense_details.php',
            type: 'GET',
            data: { expense_id: expenseId },
            success: function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    // Populate form fields
                    $('#expenseId').val(data.expense.expense_id);
                    $('#expenseDate').val(data.expense.expense_date);
                    $('#expenseCategory').val(data.expense.category_id);
                    $('#expenseDescription').val(data.expense.description);
                    $('#expenseAmount').val(data.expense.amount);
                    $('#expensePaymentMethod').val(data.expense.payment_method);
                    
                    if (data.expense.attachment) {
                        $('#currentAttachment').html(
                            '<p>Current file: ' + data.expense.attachment + 
                            ' <a href="../uploads/expenses/' + data.expense.attachment + 
                            '" target="_blank"><i class="fas fa-download"></i></a></p>'
                        );
                    } else {
                        $('#currentAttachment').empty();
                    }
                    
                    $('#expenseModal').modal('show');
                } else {
                    showAlert('error', data.error || 'Failed to load expense details');
                }
            },
            error: function() {
                showAlert('error', 'Failed to load expense details');
            }
        });
    });

    $(document).on('click', '.delete-expense', function() {
        var expenseId = $(this).data('id');
        if (confirm('Are you sure you want to delete this expense?')) {
            $.ajax({
                url: 'ajax/delete_expense.php',
                type: 'POST',
                data: { expense_id: expenseId },
                success: function(response) {
                    var data = JSON.parse(response);
                    if (data.success) {
                        $('#expensesTable').DataTable().ajax.reload();
                        showAlert('success', 'Expense deleted successfully');
                    } else {
                        showAlert('error', data.error || 'Failed to delete expense');
                    }
                },
                error: function() {
                    showAlert('error', 'Failed to delete expense');
                }
            });
        }
    });

    $(document).on('click', '.approve-expense', function() {
        var expenseId = $(this).data('id');
        $.ajax({
            url: 'ajax/update_expense_status.php',
            type: 'POST',
            data: { 
                expense_id: expenseId,
                status: 'approved'
            },
            success: function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    $('#expensesTable').DataTable().ajax.reload();
                    showAlert('success', 'Expense approved successfully');
                } else {
                    showAlert('error', data.error || 'Failed to approve expense');
                }
            },
            error: function() {
                showAlert('error', 'Failed to approve expense');
            }
        });
    });

    $(document).on('click', '.reject-expense', function() {
        var expenseId = $(this).data('id');
        var reason = prompt('Please enter rejection reason:');
        if (reason !== null) {
            $.ajax({
                url: 'ajax/update_expense_status.php',
                type: 'POST',
                data: { 
                    expense_id: expenseId,
                    status: 'rejected',
                    reason: reason
                },
                success: function(response) {
                    var data = JSON.parse(response);
                    if (data.success) {
                        $('#expensesTable').DataTable().ajax.reload();
                        showAlert('success', 'Expense rejected successfully');
                    } else {
                        showAlert('error', data.error || 'Failed to reject expense');
                    }
                },
                error: function() {
                    showAlert('error', 'Failed to reject expense');
                }
            });
        }
    });

    // Helper function to show alerts
    function showAlert(type, message) {
        var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        var alertHtml = '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
                        message +
                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                        '<span aria-hidden="true">&times;</span></button></div>';
        
        $('#alertContainer').html(alertHtml);
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);
    }
});
</script> 