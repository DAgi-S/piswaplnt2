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
            <button class="btn btn-primary" onclick="openCategoryForm()">
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
                            <th>Budget</th>
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

<!-- Category Form Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="categoryForm">
                    <input type="hidden" name="id" id="categoryId">
                    
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
                            <input type="number" class="form-control" name="monthly_budget" step="0.01">
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
                <button type="button" class="btn btn-danger" id="confirmButton">Confirm</button>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    loadCategories();
});

// Load categories via AJAX
function loadCategories() {
    fetch('/api/expense-categories')
        .then(response => response.json())
        .then(data => {
            updateCategoryTable(data);
        })
        .catch(error => showError('Failed to load categories'));
}

// Update category table with data
function updateCategoryTable(categories) {
    const tbody = document.querySelector('#categoryTable tbody');
    tbody.innerHTML = '';

    categories.forEach(category => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${category.name}</td>
            <td>${category.description || '-'}</td>
            <td>${formatCurrency(category.monthly_budget)}</td>
            <td>${getStatusBadge(category.status)}</td>
            <td>
                ${getActionButtons(category)}
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// Helper functions
function formatCurrency(amount) {
    return amount ? new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount) : '-';
}

function getStatusBadge(status) {
    const badges = {
        active: '<span class="badge bg-success">Active</span>',
        inactive: '<span class="badge bg-secondary">Inactive</span>'
    };
    return badges[status] || status;
}

function getActionButtons(category) {
    let buttons = '';
    
    if (can('expense_category_edit')) {
        buttons += `<button class="btn btn-sm btn-primary" onclick="editCategory(${category.id})">
            <i class="fas fa-edit"></i>
        </button> `;
    }
    
    if (can('expense_category_delete')) {
        buttons += `<button class="btn btn-sm btn-danger" onclick="deleteCategory(${category.id})">
            <i class="fas fa-trash"></i>
        </button>`;
    }
    
    return buttons;
}

// Modal functions
function openCategoryForm(categoryId = null) {
    const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
    const form = document.getElementById('categoryForm');
    
    if (categoryId) {
        // Load category data
        fetch(`/api/expense-categories/${categoryId}`)
            .then(response => response.json())
            .then(data => {
                Object.keys(data).forEach(key => {
                    const input = form.querySelector(`[name="${key}"]`);
                    if (input) input.value = data[key];
                });
            })
            .catch(error => showError('Failed to load category data'));
    } else {
        form.reset();
    }
    
    modal.show();
}

// Save category
function saveCategory() {
    const form = document.getElementById('categoryForm');
    const formData = new FormData(form);
    
    const categoryId = formData.get('id');
    const url = categoryId ? `/api/expense-categories/${categoryId}` : '/api/expense-categories';
    const method = categoryId ? 'PUT' : 'POST';
    
    fetch(url, {
        method: method,
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess('Category saved successfully');
            bootstrap.Modal.getInstance(document.getElementById('categoryModal')).hide();
            loadCategories();
        } else {
            showError(data.error || 'Failed to save category');
        }
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
            if (data.success) {
                showSuccess('Category deleted successfully');
                loadCategories();
            } else {
                showError(data.error || 'Failed to delete category');
            }
        })
        .catch(error => showError('Failed to delete category'));
    });
}

// Utility functions
function showConfirmation(message, callback) {
    const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
    document.getElementById('confirmationMessage').textContent = message;
    document.getElementById('confirmButton').onclick = () => {
        modal.hide();
        callback();
    };
    modal.show();
}

function showSuccess(message) {
    // Implement your success notification
    alert(message); // Replace with your preferred notification system
}

function showError(message) {
    // Implement your error notification
    alert(message); // Replace with your preferred notification system
}
</script> 