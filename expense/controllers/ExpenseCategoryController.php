<?php
/**
 * Expense Category Controller
 * Handles all operations related to expense categories
 */
class ExpenseCategoryController {
    private $model;
    private $permission;

    public function __construct() {
        $this->model = new ExpenseCategory();
        $this->permission = new Permission();
    }

    /**
     * List all active categories
     */
    public function index() {
        // Check permission
        if (!$this->permission->hasPermission('expense_category_view')) {
            return ['error' => 'Access denied'];
        }

        try {
            $categories = $this->model->getActiveCategories();
            return ['success' => true, 'data' => $categories];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Create a new category
     */
    public function create($data) {
        // Check permission
        if (!$this->permission->hasPermission('expense_category_create')) {
            return ['error' => 'Access denied'];
        }

        try {
            // Validate required fields
            if (empty($data['name'])) {
                return ['error' => 'Category name is required'];
            }

            // Create category
            $categoryId = $this->model->createCategory($data);
            return ['success' => true, 'category_id' => $categoryId];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Update an existing category
     */
    public function update($id, $data) {
        // Check permission
        if (!$this->permission->hasPermission('expense_category_edit')) {
            return ['error' => 'Access denied'];
        }

        try {
            // Validate category exists
            $category = $this->model->find($id);
            if (!$category) {
                return ['error' => 'Category not found'];
            }

            // Update category
            $result = $this->model->updateCategory($id, $data);
            return ['success' => true, 'updated' => $result];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Delete a category
     */
    public function delete($id) {
        // Check permission
        if (!$this->permission->hasPermission('expense_category_delete')) {
            return ['error' => 'Access denied'];
        }

        try {
            // Validate category exists
            $category = $this->model->find($id);
            if (!$category) {
                return ['error' => 'Category not found'];
            }

            // Delete category
            $result = $this->model->deleteCategory($id);
            return ['success' => true, 'deleted' => $result];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get category with budget information
     */
    public function getCategoryWithBudget($id) {
        // Check permission
        if (!$this->permission->hasPermission('expense_category_view')) {
            return ['error' => 'Access denied'];
        }

        try {
            $category = $this->model->getCategoryWithBudget($id);
            if (!$category) {
                return ['error' => 'Category not found'];
            }
            return ['success' => true, 'data' => $category];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
} 