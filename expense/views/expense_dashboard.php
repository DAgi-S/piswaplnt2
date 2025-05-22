<?php
/**
 * Expense Dashboard View
 */
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Expense Dashboard</h1>
        <div class="d-flex gap-2">
            <div class="input-group">
                <input type="date" class="form-control" id="startDate">
                <span class="input-group-text">to</span>
                <input type="date" class="form-control" id="endDate">
                <button class="btn btn-primary" onclick="loadDashboardData()">
                    <i class="fas fa-sync"></i> Refresh
                </button>
            </div>
            <button class="btn btn-success" onclick="exportDashboardData()">
                <i class="fas fa-download"></i> Export
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Total Expenses</h6>
                    <h3 class="mb-0" id="totalExpenses">$0.00</h3>
                    <small class="text-muted" id="expensePeriod">This Month</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Pending Expenses</h6>
                    <h3 class="mb-0" id="pendingExpenses">$0.00</h3>
                    <small class="text-muted">Awaiting Approval</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Budget Utilization</h6>
                    <h3 class="mb-0" id="budgetUtilization">0%</h3>
                    <small class="text-muted">of Monthly Budget</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Average Daily Expense</h6>
                    <h3 class="mb-0" id="avgDailyExpense">$0.00</h3>
                    <small class="text-muted">Per Day</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Monthly Expense Trend</h6>
                    <canvas id="expenseTrendChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Category Distribution</h6>
                    <canvas id="categoryDistributionChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Budget vs Actual</h6>
                    <canvas id="budgetVsActualChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Payment Method Distribution</h6>
                    <canvas id="paymentMethodChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Expenses -->
    <div class="card">
        <div class="card-body">
            <h6 class="card-title">Recent Expenses</h6>
            <div class="table-responsive">
                <table class="table table-hover" id="recentExpensesTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Status</th>
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

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Export Dashboard Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="exportForm">
                    <div class="mb-3">
                        <label class="form-label">Export Format</label>
                        <select class="form-select" name="format" required>
                            <option value="csv">CSV</option>
                            <option value="pdf">PDF</option>
                            <option value="excel">Excel</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data Range</label>
                        <select class="form-select" name="range" required>
                            <option value="current">Current View</option>
                            <option value="month">This Month</option>
                            <option value="quarter">This Quarter</option>
                            <option value="year">This Year</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Include Charts</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="include_charts" checked>
                            <label class="form-check-label">Include charts in export</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="processExport()">Export</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Global variables
let expenseTrendChart;
let categoryDistributionChart;
let budgetVsActualChart;
let paymentMethodChart;
let exportModal;

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize modals
    exportModal = new bootstrap.Modal(document.getElementById('exportModal'));
    
    // Set default date range (current month)
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    document.getElementById('startDate').value = formatDate(firstDay);
    document.getElementById('endDate').value = formatDate(today);
    
    // Load initial data
    loadDashboardData();
});

// Load dashboard data
function loadDashboardData() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    fetch(`/api/expense-dashboard/summary?start_date=${startDate}&end_date=${endDate}`)
        .then(response => response.json())
        .then(data => {
            updateSummaryCards(data.summary);
            updateCharts(data);
            updateRecentExpenses(data.recent_expenses);
        })
        .catch(error => showError('Failed to load dashboard data'));
}

// Update summary cards
function updateSummaryCards(data) {
    document.getElementById('totalExpenses').textContent = formatCurrency(data.total_expenses);
    document.getElementById('pendingExpenses').textContent = formatCurrency(data.pending_expenses);
    document.getElementById('budgetUtilization').textContent = `${data.budget_utilization}%`;
    document.getElementById('avgDailyExpense').textContent = formatCurrency(data.avg_daily_expense);
    document.getElementById('expensePeriod').textContent = `${formatDate(new Date(data.start_date))} to ${formatDate(new Date(data.end_date))}`;
}

// Update charts
function updateCharts(data) {
    // Update or create expense trend chart
    if (expenseTrendChart) {
        expenseTrendChart.destroy();
    }
    expenseTrendChart = new Chart(document.getElementById('expenseTrendChart'), {
        type: 'line',
        data: {
            labels: data.trend.labels,
            datasets: [{
                label: 'Daily Expenses',
                data: data.trend.values,
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Update or create category distribution chart
    if (categoryDistributionChart) {
        categoryDistributionChart.destroy();
    }
    categoryDistributionChart = new Chart(document.getElementById('categoryDistributionChart'), {
        type: 'pie',
        data: {
            labels: data.categories.labels,
            datasets: [{
                data: data.categories.values,
                backgroundColor: [
                    'rgb(255, 99, 132)',
                    'rgb(54, 162, 235)',
                    'rgb(255, 205, 86)',
                    'rgb(75, 192, 192)',
                    'rgb(153, 102, 255)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Update or create budget vs actual chart
    if (budgetVsActualChart) {
        budgetVsActualChart.destroy();
    }
    budgetVsActualChart = new Chart(document.getElementById('budgetVsActualChart'), {
        type: 'bar',
        data: {
            labels: data.budget_vs_actual.labels,
            datasets: [
                {
                    label: 'Budget',
                    data: data.budget_vs_actual.budget,
                    backgroundColor: 'rgb(75, 192, 192)'
                },
                {
                    label: 'Actual',
                    data: data.budget_vs_actual.actual,
                    backgroundColor: 'rgb(255, 99, 132)'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    stacked: true
                },
                y: {
                    stacked: true
                }
            }
        }
    });

    // Update or create payment method chart
    if (paymentMethodChart) {
        paymentMethodChart.destroy();
    }
    paymentMethodChart = new Chart(document.getElementById('paymentMethodChart'), {
        type: 'doughnut',
        data: {
            labels: data.payment_methods.labels,
            datasets: [{
                data: data.payment_methods.values,
                backgroundColor: [
                    'rgb(255, 99, 132)',
                    'rgb(54, 162, 235)',
                    'rgb(255, 205, 86)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

// Update recent expenses table
function updateRecentExpenses(expenses) {
    const tbody = document.querySelector('#recentExpensesTable tbody');
    tbody.innerHTML = expenses.map(expense => `
        <tr>
            <td>${formatDate(new Date(expense.date))}</td>
            <td>${expense.category_name}</td>
            <td>${expense.description}</td>
            <td>${formatCurrency(expense.amount)}</td>
            <td>${renderStatusBadge(expense.status)}</td>
        </tr>
    `).join('');
}

// Show export modal
function exportDashboardData() {
    exportModal.show();
}

// Process export
function processExport() {
    const form = document.getElementById('exportForm');
    const formData = new FormData(form);
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    formData.append('start_date', startDate);
    formData.append('end_date', endDate);
    
    fetch('/api/expense-dashboard/export', {
        method: 'POST',
        body: formData
    })
    .then(response => response.blob())
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `expense_dashboard_${formatDate(new Date())}.${formData.get('format')}`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        exportModal.hide();
    })
    .catch(error => showError('Failed to export data'));
}

// Helper functions
function formatDate(date) {
    return date.toISOString().split('T')[0];
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

function renderStatusBadge(status) {
    const badges = {
        pending: 'warning',
        approved: 'success',
        rejected: 'danger'
    };
    return `<span class="badge bg-${badges[status]}">${status}</span>`;
}

function showError(message) {
    // Implement your error notification
    alert(message);
}
</script> 