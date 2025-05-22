<?php
/**
 * Expense Category Model
 * Handles all operations related to expense categories
 */
class ExpenseCategory extends BaseModel {
    protected $table = 'expense_categories';
    protected $primaryKey = 'category_id';

    /**
     * Get all active categories
     */
    public function getActiveCategories() {
        return $this->findAll(['status' => 1], 'name ASC');
    }

    /**
     * Create a new category with validation
     */
    public function createCategory($data) {
        // Validate required fields
        if (empty($data['name'])) {
            throw new Exception('Category name is required');
        }

        // Check for duplicate names
        $existing = $this->findAll(['name' => $data['name']]);
        if (!empty($existing)) {
            throw new Exception('Category name already exists');
        }

        // Set default values
        $data['status'] = $data['status'] ?? 1;
        $data['deleted'] = 0;

        // Create the category
        $categoryId = $this->create($data);

        // Log the creation
        $this->logAudit('create', $categoryId, null, $data);

        return $categoryId;
    }

    /**
     * Update a category with validation
     */
    public function updateCategory($id, $data) {
        // Get current category data
        $current = $this->find($id);
        if (!$current) {
            throw new Exception('Category not found');
        }

        // Validate name if being changed
        if (isset($data['name']) && $data['name'] !== $current['name']) {
            $existing = $this->findAll(['name' => $data['name']]);
            if (!empty($existing)) {
                throw new Exception('Category name already exists');
            }
        }

        // Update the category
        $result = $this->update($id, $data);

        // Log the update
        $this->logAudit('update', $id, $current, $data);

        return $result;
    }

    /**
     * Delete a category (soft delete)
     */
    public function deleteCategory($id) {
        // Check if category has expenses
        $expenseModel = new Expense();
        $expenses = $expenseModel->findAll(['category_id' => $id]);
        
        if (!empty($expenses)) {
            throw new Exception('Cannot delete category with existing expenses');
        }

        // Perform soft delete
        $result = $this->delete($id);

        // Log the deletion
        $this->logAudit('delete', $id, null, null);

        return $result;
    }

    /**
     * Get category with budget information
     */
    public function getCategoryWithBudget($id) {
        $category = $this->find($id);
        if (!$category) {
            return null;
        }

        // Get total expenses for this category
        $expenseModel = new Expense();
        $expenses = $expenseModel->findAll([
            'category_id' => $id,
            'status' => 'approved'
        ]);

        $totalExpenses = array_sum(array_column($expenses, 'amount'));
        $category['total_expenses'] = $totalExpenses;
        $category['remaining_budget'] = $category['budget_limit'] ? 
            $category['budget_limit'] - $totalExpenses : null;

        return $category;
    }

    /**
     * Get total budget from all active categories
     * @return float Total budget
     */
    public function getTotalBudget() {
        $sql = "SELECT COALESCE(SUM(monthly_budget), 0) as total 
                FROM expense_categories 
                WHERE status = 'active'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (float)$result['total'];
    }
} 