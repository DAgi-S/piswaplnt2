<?php
/**
 * Expense Category Management Page
 */
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Expense Categories</h1>
        <?php if (can('expense_category_create')): ?>
            <button class="btn btn-primary" onclick="showCategoryModal()">
                <i class="fas fa-plus"></i> New Category
            </button>
        <?php endif; ?>
    </div>

    <!-- Category List -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="categoryTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Monthly Budget</th>
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

<!-- Category Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="categoryForm">
                    <input type="hidden" name="category_id" id="categoryId">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Monthly Budget</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" name="monthly_budget" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveCategory()">Save Category</button>
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
let categoryModal;
let confirmationModal;

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize modals
    categoryModal = new bootstrap.Modal(document.getElementById('categoryModal'));
    confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
    
    // Load initial data
    loadCategories();
});

// Load categories
function loadCategories() {
    fetch('/api/expense-categories')
        .then(response => response.json())
        .then(data => {
            renderCategories(data);
        })
        .catch(error => showError('Failed to load categories'));
}

// Render categories in table
function renderCategories(categories) {
    const tbody = document.querySelector('#categoryTable tbody');
    tbody.innerHTML = categories.map(category => `
        <tr>
            <td>${category.name}</td>
            <td>${category.description || '-'}</td>
            <td>${formatCurrency(category.monthly_budget)}</td>
            <td>${renderStatusBadge(category.status)}</td>
            <td>${renderActions(category)}</td>
        </tr>
    `).join('');
}

// Show category modal
function showCategoryModal(category = null) {
    const form = document.getElementById('categoryForm');
    form.reset();
    
    if (category) {
        document.getElementById('categoryId').value = category.id;
        form.name.value = category.name;
        form.description.value = category.description || '';
        form.monthly_budget.value = category.monthly_budget || '';
        form.status.value = category.status;
    }
    
    categoryModal.show();
}

// Save category
function saveCategory() {
    const form = document.getElementById('categoryForm');
    const formData = new FormData(form);
    
    const categoryId = formData.get('category_id');
    const url = categoryId ? `/api/expense-categories/${categoryId}` : '/api/expense-categories';
    const method = categoryId ? 'PUT' : 'POST';
    
    fetch(url, {
        method: method,
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        categoryModal.hide();
        showSuccess('Category saved successfully');
        loadCategories();
    })
    .catch(error => showError('Failed to save category'));
}

// Delete category
function deleteCategory(id) {
    showConfirmation('Are you sure you want to delete this category?', () => {
        fetch(`/api/expense-categories/${id}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            showSuccess('Category deleted successfully');
            loadCategories();
        })
        .catch(error => showError('Failed to delete category'));
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
function formatCurrency(amount) {
    if (!amount) return '-';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

function renderStatusBadge(status) {
    const badges = {
        active: 'success',
        inactive: 'secondary'
    };
    return `<span class="badge bg-${badges[status]}">${status}</span>`;
}

function renderActions(category) {
    let actions = [];
    
    if (can('expense_category_edit')) {
        actions.push(`
            <button class="btn btn-sm btn-primary" onclick="showCategoryModal(${JSON.stringify(category)})">
                <i class="fas fa-edit"></i>
            </button>
        `);
    }
    
    if (can('expense_category_delete') && category.status === 'inactive') {
        actions.push(`
            <button class="btn btn-sm btn-danger" onclick="deleteCategory(${category.id})">
                <i class="fas fa-trash"></i>
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