<?php
/**
 * Expense Controller
 * Handles all operations related to expense entries
 */
class ExpenseController {
    private $model;
    private $permission;

    public function __construct() {
        $this->model = new Expense();
        $this->permission = new Permission();
    }

    /**
     * List expenses with optional filters
     */
    public function index($filters = []) {
        // Check permission
        if (!$this->permission->hasPermission('expense_view')) {
            return ['error' => 'Access denied'];
        }

        try {
            $expenses = $this->model->getExpenses($filters);
            return ['success' => true, 'data' => $expenses];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Create a new expense
     */
    public function create($data) {
        // Check permission
        if (!$this->permission->hasPermission('expense_create')) {
            return ['error' => 'Access denied'];
        }

        try {
            // Validate required fields
            $required = ['category_id', 'title', 'amount', 'expense_date'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return ['error' => "$field is required"];
                }
            }

            // Create expense
            $expenseId = $this->model->createExpense($data);
            return ['success' => true, 'expense_id' => $expenseId];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Update an existing expense
     */
    public function update($id, $data) {
        // Check permission
        if (!$this->permission->hasPermission('expense_edit')) {
            return ['error' => 'Access denied'];
        }

        try {
            // Validate expense exists
            $expense = $this->model->find($id);
            if (!$expense) {
                return ['error' => 'Expense not found'];
            }

            // Update expense
            $result = $this->model->updateExpense($id, $data);
            return ['success' => true, 'updated' => $result];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Delete an expense
     */
    public function delete($id) {
        // Check permission
        if (!$this->permission->hasPermission('expense_delete')) {
            return ['error' => 'Access denied'];
        }

        try {
            // Validate expense exists
            $expense = $this->model->find($id);
            if (!$expense) {
                return ['error' => 'Expense not found'];
            }

            // Delete expense
            $result = $this->model->deleteExpense($id);
            return ['success' => true, 'deleted' => $result];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Approve an expense
     */
    public function approve($id) {
        // Check permission
        if (!$this->permission->hasPermission('expense_approve')) {
            return ['error' => 'Access denied'];
        }

        try {
            // Validate expense exists
            $expense = $this->model->find($id);
            if (!$expense) {
                return ['error' => 'Expense not found'];
            }

            // Approve expense
            $result = $this->model->approveExpense($id);
            return ['success' => true, 'approved' => $result];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Reject an expense
     */
    public function reject($id, $reason) {
        // Check permission
        if (!$this->permission->hasPermission('expense_approve')) {
            return ['error' => 'Access denied'];
        }

        try {
            // Validate expense exists
            $expense = $this->model->find($id);
            if (!$expense) {
                return ['error' => 'Expense not found'];
            }

            // Reject expense
            $result = $this->model->rejectExpense($id, $reason);
            return ['success' => true, 'rejected' => $result];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get expense summary by category
     */
    public function getSummary($startDate = null, $endDate = null) {
        // Check permission
        if (!$this->permission->hasPermission('expense_view')) {
            return ['error' => 'Access denied'];
        }

        try {
            $summary = $this->model->getExpenseSummary($startDate, $endDate);
            return ['success' => true, 'data' => $summary];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
} 