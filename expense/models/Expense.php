<?php
/**
 * Expense Model
 * Handles all operations related to expense entries
 */
class Expense extends BaseModel {
    protected $table = 'expense_entries';
    protected $primaryKey = 'expense_id';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new expense with validation
     */
    public function createExpense($data) {
        // Validate required fields
        $required = ['category_id', 'title', 'amount', 'expense_date'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("$field is required");
            }
        }

        // Validate amount
        if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
            throw new Exception('Amount must be a positive number');
        }

        // Validate category exists
        $categoryModel = new ExpenseCategory();
        $category = $categoryModel->find($data['category_id']);
        if (!$category) {
            throw new Exception('Invalid category');
        }

        // Set default values
        $data['user_id'] = $_SESSION['user_id'];
        $data['status'] = 'pending';
        $data['deleted'] = 0;

        // Create the expense
        $expenseId = $this->create($data);

        // Log the creation
        $this->logAudit('create', $expenseId, null, $data);

        return $expenseId;
    }

    /**
     * Update an expense with validation
     */
    public function updateExpense($id, $data) {
        // Get current expense data
        $current = $this->find($id);
        if (!$current) {
            throw new Exception('Expense not found');
        }

        // Validate amount if being changed
        if (isset($data['amount']) && (!is_numeric($data['amount']) || $data['amount'] <= 0)) {
            throw new Exception('Amount must be a positive number');
        }

        // Validate category if being changed
        if (isset($data['category_id'])) {
            $categoryModel = new ExpenseCategory();
            $category = $categoryModel->find($data['category_id']);
            if (!$category) {
                throw new Exception('Invalid category');
            }
        }

        // Update the expense
        $result = $this->update($id, $data);

        // Log the update
        $this->logAudit('update', $id, $current, $data);

        return $result;
    }

    /**
     * Delete an expense (soft delete)
     */
    public function deleteExpense($id) {
        // Get current expense data
        $current = $this->find($id);
        if (!$current) {
            throw new Exception('Expense not found');
        }

        // Check if expense is already approved
        if ($current['status'] === 'approved') {
            throw new Exception('Cannot delete approved expense');
        }

        // Perform soft delete
        $result = $this->delete($id);

        // Log the deletion
        $this->logAudit('delete', $id, $current, null);

        return $result;
    }

    /**
     * Approve an expense
     */
    public function approveExpense($id) {
        // Get current expense data
        $current = $this->find($id);
        if (!$current) {
            throw new Exception('Expense not found');
        }

        // Check if already approved
        if ($current['status'] === 'approved') {
            throw new Exception('Expense is already approved');
        }

        // Update status to approved
        $result = $this->update($id, ['status' => 'approved']);

        // Log the approval
        $this->logAudit('approve', $id, $current, ['status' => 'approved']);

        return $result;
    }

    /**
     * Reject an expense
     */
    public function rejectExpense($id, $reason) {
        // Get current expense data
        $current = $this->find($id);
        if (!$current) {
            throw new Exception('Expense not found');
        }

        // Check if already rejected
        if ($current['status'] === 'rejected') {
            throw new Exception('Expense is already rejected');
        }

        // Update status to rejected
        $result = $this->update($id, [
            'status' => 'rejected',
            'rejection_reason' => $reason
        ]);

        // Log the rejection
        $this->logAudit('reject', $id, $current, [
            'status' => 'rejected',
            'rejection_reason' => $reason
        ]);

        return $result;
    }

    /**
     * Get expenses with filters
     */
    public function getExpenses($filters = [], $orderBy = 'expense_date DESC', $limit = null) {
        $conditions = [];
        
        // Apply filters
        if (!empty($filters['category_id'])) {
            $conditions['category_id'] = $filters['category_id'];
        }
        if (!empty($filters['status'])) {
            $conditions['status'] = $filters['status'];
        }
        if (!empty($filters['user_id'])) {
            $conditions['user_id'] = $filters['user_id'];
        }
        if (!empty($filters['start_date'])) {
            $conditions[] = "expense_date >= ?";
            $params[] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $conditions[] = "expense_date <= ?";
            $params[] = $filters['end_date'];
        }

        return $this->findAll($conditions, $orderBy, $limit);
    }

    /**
     * Get expense summary by category
     */
    public function getExpenseSummary($startDate = null, $endDate = null) {
        $sql = "SELECT c.category_id, c.name, 
                COUNT(e.expense_id) as total_expenses,
                SUM(e.amount) as total_amount
                FROM expense_categories c
                LEFT JOIN expense_entries e ON c.category_id = e.category_id
                WHERE c.deleted = 0 AND e.deleted = 0";

        $params = [];

        if ($startDate) {
            $sql .= " AND e.expense_date >= ?";
            $params[] = $startDate;
        }
        if ($endDate) {
            $sql .= " AND e.expense_date <= ?";
            $params[] = $endDate;
        }

        $sql .= " GROUP BY c.category_id, c.name
                 ORDER BY c.name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get total expenses for a date range
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return float Total expenses
     */
    public function getTotalExpenses($startDate, $endDate) {
        $sql = "SELECT COALESCE(SUM(amount), 0) as total 
                FROM {$this->table} 
                WHERE date BETWEEN ? AND ? 
                AND status = 'approved'";
        $result = $this->db->query($sql, [$startDate, $endDate]);
        return (float)$result[0]['total'];
    }

    /**
     * Get pending expenses for a date range
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return float Pending expenses
     */
    public function getPendingExpenses($startDate, $endDate) {
        $sql = "SELECT COALESCE(SUM(amount), 0) as total 
                FROM {$this->table} 
                WHERE date BETWEEN ? AND ? 
                AND status = 'pending'";
        $result = $this->db->query($sql, [$startDate, $endDate]);
        return (float)$result[0]['total'];
    }

    /**
     * Get average daily expense for a date range
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return float Average daily expense
     */
    public function getAverageDailyExpense($startDate, $endDate) {
        $sql = "SELECT COALESCE(AVG(daily_total), 0) as average 
                FROM (
                    SELECT date, SUM(amount) as daily_total 
                    FROM {$this->table} 
                    WHERE date BETWEEN ? AND ? 
                    AND status = 'approved'
                    GROUP BY date
                ) as daily_totals";
        $result = $this->db->query($sql, [$startDate, $endDate]);
        return (float)$result[0]['average'];
    }

    /**
     * Get daily expense trend for a date range
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Daily expense trend data
     */
    public function getDailyExpenseTrend($startDate, $endDate) {
        $sql = "SELECT date, COALESCE(SUM(amount), 0) as total 
                FROM {$this->table} 
                WHERE date BETWEEN ? AND ? 
                AND status = 'approved'
                GROUP BY date 
                ORDER BY date";
        $results = $this->db->query($sql, [$startDate, $endDate]);

        $labels = [];
        $values = [];

        foreach ($results as $row) {
            $labels[] = date('M d', strtotime($row['date']));
            $values[] = (float)$row['total'];
        }

        return [
            'labels' => $labels,
            'values' => $values
        ];
    }

    /**
     * Get category distribution for a date range
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Category distribution data
     */
    public function getCategoryDistribution($startDate, $endDate) {
        $sql = "SELECT c.name, COALESCE(SUM(e.amount), 0) as total 
                FROM expense_categories c 
                LEFT JOIN {$this->table} e ON c.id = e.category_id 
                AND e.date BETWEEN ? AND ? 
                AND e.status = 'approved'
                GROUP BY c.id, c.name 
                ORDER BY total DESC";
        $results = $this->db->query($sql, [$startDate, $endDate]);

        $labels = [];
        $values = [];

        foreach ($results as $row) {
            $labels[] = $row['name'];
            $values[] = (float)$row['total'];
        }

        return [
            'labels' => $labels,
            'values' => $values
        ];
    }

    /**
     * Get expenses by category for a date range
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Expenses by category data
     */
    public function getExpensesByCategory($startDate, $endDate) {
        $sql = "SELECT category_id, COALESCE(SUM(amount), 0) as total 
                FROM {$this->table} 
                WHERE date BETWEEN ? AND ? 
                AND status = 'approved'
                GROUP BY category_id";
        $results = $this->db->query($sql, [$startDate, $endDate]);

        $expenses = [];
        foreach ($results as $row) {
            $expenses[$row['category_id']] = (float)$row['total'];
        }

        return $expenses;
    }

    /**
     * Get payment method distribution for a date range
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Payment method distribution data
     */
    public function getPaymentMethodDistribution($startDate, $endDate) {
        $sql = "SELECT payment_method, COALESCE(SUM(amount), 0) as total 
                FROM {$this->table} 
                WHERE date BETWEEN ? AND ? 
                AND status = 'approved'
                GROUP BY payment_method 
                ORDER BY total DESC";
        $results = $this->db->query($sql, [$startDate, $endDate]);

        $labels = [];
        $values = [];

        foreach ($results as $row) {
            $labels[] = ucfirst($row['payment_method']);
            $values[] = (float)$row['total'];
        }

        return [
            'labels' => $labels,
            'values' => $values
        ];
    }

    /**
     * Get recent expenses
     * @param int $limit Number of recent expenses to retrieve
     * @return array Recent expenses data
     */
    public function getRecentExpenses($limit = 10) {
        $sql = "SELECT e.*, c.name as category_code 
                FROM {$this->table} e 
                JOIN expense_categories c ON e.category_id = c.id 
                ORDER BY e.date DESC, e.created_at DESC 
                LIMIT ?";
        return $this->db->query($sql, [$limit]);
    }

    /**
     * Get daily expenses for a date range
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Daily expenses data
     */
    public function getDailyExpenses($startDate, $endDate) {
        $sql = "SELECT DATE(expense_date) as date, COALESCE(SUM(amount), 0) as amount 
                FROM expense_entries 
                WHERE expense_date BETWEEN ? AND ? 
                AND status = 'approved'
                GROUP BY DATE(expense_date)
                ORDER BY date";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get category expenses for a date range
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Category expenses data
     */
    public function getCategoryExpenses($startDate, $endDate) {
        $sql = "SELECT c.name, COALESCE(SUM(e.amount), 0) as amount 
                FROM expense_categories c 
                LEFT JOIN expense_entries e ON c.id = e.category_id 
                AND e.expense_date BETWEEN ? AND ? 
                AND e.status = 'approved'
                GROUP BY c.id, c.name
                ORDER BY amount DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get top categories for a date range
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Top categories data
     */
    public function getTopCategories($startDate, $endDate) {
        $sql = "SELECT c.name, 
                       COALESCE(SUM(e.amount), 0) as amount,
                       ROUND(COALESCE(SUM(e.amount), 0) / 
                            (SELECT COALESCE(SUM(amount), 1) 
                             FROM expense_entries 
                             WHERE expense_date BETWEEN ? AND ? 
                             AND status = 'approved') * 100, 2) as percentage
                FROM expense_categories c 
                LEFT JOIN expense_entries e ON c.id = e.category_id 
                AND e.expense_date BETWEEN ? AND ? 
                AND e.status = 'approved'
                GROUP BY c.id, c.name
                ORDER BY amount DESC
                LIMIT 5";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDate, $endDate, $startDate, $endDate]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 