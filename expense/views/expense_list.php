<?php
/**
 * Expense Listing Page
 */
?>
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Expense Management</h1>
        <?php if (can('expense_create')): ?>
            <button class="btn btn-primary" onclick="showExpenseModal()">
                <i class="fas fa-plus"></i> New Expense
            </button>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="filterForm" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Date Range</label>
                    <div class="input-group">
                        <input type="date" class="form-control" name="start_date" id="startDate">
                        <span class="input-group-text">to</span>
                        <input type="date" class="form-control" name="end_date" id="endDate">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="category_id" id="categoryFilter">
                        <option value="">All Categories</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" name="search" placeholder="Search expenses...">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Expense List -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="expenseTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Attachments</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted" id="paginationInfo">
                    Showing 0 to 0 of 0 entries
                </div>
                <nav>
                    <ul class="pagination" id="pagination">
                        <!-- Pagination will be loaded via AJAX -->
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Expense Modal -->
<div class="modal fade" id="expenseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Expense</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="expenseForm">
                    <input type="hidden" name="expense_id" id="expenseId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Date</label>
                            <input type="date" class="form-control" name="expense_date" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category_id" required>
                                <option value="">Select Category</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" name="amount" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payment Method</label>
                            <select class="form-select" name="payment_method" required>
                                <option value="cash">Cash</option>
                                <option value="credit_card">Credit Card</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="check">Check</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Attachments</label>
                            <input type="file" class="form-control" name="attachments[]" multiple>
                            <small class="text-muted">Max file size: 5MB. Supported formats: PDF, JPG, PNG</small>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveExpense()">Save Expense</button>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="confirmationMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmButton">Confirm</button>
            </div>
        </div>
    </div>
</div>

<script>
// Global variables
let currentPage = 1;
let totalPages = 1;
let expenseModal;
let confirmationModal;

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize modals
    expenseModal = new bootstrap.Modal(document.getElementById('expenseModal'));
    confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
    
    // Load initial data
    loadExpenses();
    loadCategories();
    
    // Set up event listeners
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        e.preventDefault();
        currentPage = 1;
        loadExpenses();
    });
});

// Load expenses with current filters
function loadExpenses() {
    const formData = new FormData(document.getElementById('filterForm'));
    formData.append('page', currentPage);
    
    fetch('/api/expenses?' + new URLSearchParams(formData))
        .then(response => response.json())
        .then(data => {
            renderExpenses(data.expenses);
            renderPagination(data.pagination);
        })
        .catch(error => showError('Failed to load expenses'));
}

// Load categories for dropdown
function loadCategories() {
    fetch('/api/expense-categories')
        .then(response => response.json())
        .then(data => {
            const categorySelects = document.querySelectorAll('select[name="category_id"]');
            categorySelects.forEach(select => {
                select.innerHTML = '<option value="">Select Category</option>' +
                    data.map(category => `<option value="${category.id}">${category.name}</option>`).join('');
            });
        })
        .catch(error => showError('Failed to load categories'));
}

// Render expenses in table
function renderExpenses(expenses) {
    const tbody = document.querySelector('#expenseTable tbody');
    tbody.innerHTML = expenses.map(expense => `
        <tr>
            <td>${formatDate(expense.expense_date)}</td>
            <td>${expense.category_name}</td>
            <td>${expense.description}</td>
            <td>${formatCurrency(expense.amount)}</td>
            <td>${renderStatusBadge(expense.status)}</td>
            <td>${renderAttachments(expense.attachments)}</td>
            <td>${renderActions(expense)}</td>
        </tr>
    `).join('');
}

// Render pagination
function renderPagination(pagination) {
    const paginationElement = document.getElementById('pagination');
    const paginationInfo = document.getElementById('paginationInfo');
    
    totalPages = pagination.total_pages;
    paginationInfo.textContent = `Showing ${pagination.from} to ${pagination.to} of ${pagination.total} entries`;
    
    let html = '';
    if (totalPages > 1) {
        html += `
            <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="changePage(${currentPage - 1})">Previous</a>
            </li>
        `;
        
        for (let i = 1; i <= totalPages; i++) {
            html += `
                <li class="page-item ${currentPage === i ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
                </li>
            `;
        }
        
        html += `
            <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="changePage(${currentPage + 1})">Next</a>
            </li>
        `;
    }
    
    paginationElement.innerHTML = html;
}

// Change page
function changePage(page) {
    if (page >= 1 && page <= totalPages) {
        currentPage = page;
        loadExpenses();
    }
}

// Show expense modal
function showExpenseModal(expense = null) {
    const form = document.getElementById('expenseForm');
    form.reset();
    
    if (expense) {
        document.getElementById('expenseId').value = expense.id;
        form.expense_date.value = expense.expense_date;
        form.category_id.value = expense.category_id;
        form.description.value = expense.description;
        form.amount.value = expense.amount;
        form.payment_method.value = expense.payment_method;
    }
    
    expenseModal.show();
}

// Save expense
function saveExpense() {
    const form = document.getElementById('expenseForm');
    const formData = new FormData(form);
    
    const expenseId = formData.get('expense_id');
    const url = expenseId ? `/api/expenses/${expenseId}` : '/api/expenses';
    const method = expenseId ? 'PUT' : 'POST';
    
    fetch(url, {
        method: method,
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        expenseModal.hide();
        showSuccess('Expense saved successfully');
        loadExpenses();
    })
    .catch(error => showError('Failed to save expense'));
}

// Delete expense
function deleteExpense(id) {
    showConfirmation('Are you sure you want to delete this expense?', () => {
        fetch(`/api/expenses/${id}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            showSuccess('Expense deleted successfully');
            loadExpenses();
        })
        .catch(error => showError('Failed to delete expense'));
    });
}

// Approve expense
function approveExpense(id) {
    showConfirmation('Are you sure you want to approve this expense?', () => {
        fetch(`/api/expenses/${id}/approve`, {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            showSuccess('Expense approved successfully');
            loadExpenses();
        })
        .catch(error => showError('Failed to approve expense'));
    });
}

// Reject expense
function rejectExpense(id) {
    showConfirmation('Are you sure you want to reject this expense?', () => {
        fetch(`/api/expenses/${id}/reject`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                reason: prompt('Please enter rejection reason:')
            })
        })
        .then(response => response.json())
        .then(data => {
            showSuccess('Expense rejected successfully');
            loadExpenses();
        })
        .catch(error => showError('Failed to reject expense'));
    });
}

// Show confirmation dialog
function showConfirmation(message, callback) {
    document.getElementById('confirmationMessage').textContent = message;
    document.getElementById('confirmButton').onclick = function() {
        confirmationModal.hide();
        callback();
    };
    confirmationModal.show();
}

// Helper functions
function formatDate(date) {
    return new Date(date).toLocaleDateString();
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

function renderAttachments(attachments) {
    if (!attachments || attachments.length === 0) return '-';
    return attachments.map(attachment => `
        <a href="/api/expense-attachments/${attachment.id}" target="_blank" class="me-2">
            <i class="fas fa-paperclip"></i>
        </a>
    `).join('');
}

function renderActions(expense) {
    let actions = [];
    
    if (can('expense_edit') && expense.status === 'pending') {
        actions.push(`
            <button class="btn btn-sm btn-primary" onclick="showExpenseModal(${JSON.stringify(expense)})">
                <i class="fas fa-edit"></i>
            </button>
        `);
    }
    
    if (can('expense_delete') && expense.status === 'pending') {
        actions.push(`
            <button class="btn btn-sm btn-danger" onclick="deleteExpense(${expense.id})">
                <i class="fas fa-trash"></i>
            </button>
        `);
    }
    
    if (can('expense_approve') && expense.status === 'pending') {
        actions.push(`
            <button class="btn btn-sm btn-success" onclick="approveExpense(${expense.id})">
                <i class="fas fa-check"></i>
            </button>
            <button class="btn btn-sm btn-danger" onclick="rejectExpense(${expense.id})">
                <i class="fas fa-times"></i>
            </button>
        `);
    }
    
    return actions.join(' ');
}

// Utility functions
function showSuccess(message) {
    // Implement your success notification
    alert(message);
}

function showError(message) {
    // Implement your error notification
    alert(message);
}
</script> 