<?php
/**
 * Expense Dashboard Controller
 * Handles dashboard data and export functionality
 */
class ExpenseDashboardController {
    private $expenseModel;
    private $categoryModel;
    private $permission;

    public function __construct() {
        $this->expenseModel = new Expense();
        $this->categoryModel = new ExpenseCategory();
        $this->permission = new Permission();
    }

    /**
     * Get dashboard summary data
     */
    public function getSummary($startDate = null, $endDate = null) {
        // Check permission
        if (!$this->permission->hasPermission('expense_view')) {
            return ['error' => 'Access denied'];
        }

        // Set default date range to current month if not provided
        if (!$startDate) {
            $startDate = date('Y-m-01');
        }
        if (!$endDate) {
            $endDate = date('Y-m-t');
        }

        try {
            // Get summary data
            $summary = [
                'total_expenses' => $this->expenseModel->getTotalExpenses($startDate, $endDate),
                'pending_expenses' => $this->expenseModel->getPendingExpenses($startDate, $endDate),
                'budget_utilization' => $this->calculateBudgetUtilization($startDate, $endDate),
                'avg_daily_expense' => $this->expenseModel->getAverageDailyExpense($startDate, $endDate),
                'start_date' => $startDate,
                'end_date' => $endDate
            ];

            // Get trend data
            $trend = $this->expenseModel->getDailyExpenseTrend($startDate, $endDate);

            // Get category distribution
            $categories = $this->expenseModel->getCategoryDistribution($startDate, $endDate);

            // Get budget vs actual comparison
            $budgetVsActual = $this->getBudgetVsActual($startDate, $endDate);

            // Get payment method distribution
            $paymentMethods = $this->expenseModel->getPaymentMethodDistribution($startDate, $endDate);

            // Get recent expenses
            $recentExpenses = $this->expenseModel->getRecentExpenses(10);

            return [
                'summary' => $summary,
                'trend' => $trend,
                'categories' => $categories,
                'budget_vs_actual' => $budgetVsActual,
                'payment_methods' => $paymentMethods,
                'recent_expenses' => $recentExpenses
            ];
        } catch (Exception $e) {
            return ['error' => 'Failed to load dashboard data: ' . $e->getMessage()];
        }
    }

    /**
     * Export dashboard data
     */
    public function export($format, $startDate = null, $endDate = null, $includeCharts = true) {
        // Check permission
        if (!$this->permission->hasPermission('expense_export')) {
            return ['error' => 'Access denied'];
        }

        try {
            // Get dashboard data
            $data = $this->getSummary($startDate, $endDate);
            if (isset($data['error'])) {
                return $data;
            }

            // Generate export file based on format
            switch ($format) {
                case 'csv':
                    return $this->exportToCSV($data);
                case 'pdf':
                    return $this->exportToPDF($data, $includeCharts);
                case 'excel':
                    return $this->exportToExcel($data, $includeCharts);
                default:
                    return ['error' => 'Invalid export format'];
            }
        } catch (Exception $e) {
            return ['error' => 'Failed to export data: ' . $e->getMessage()];
        }
    }

    /**
     * Calculate budget utilization percentage
     */
    private function calculateBudgetUtilization($startDate, $endDate) {
        $totalBudget = $this->categoryModel->getTotalBudget();
        $totalExpenses = $this->expenseModel->getTotalExpenses($startDate, $endDate);
        
        if ($totalBudget <= 0) {
            return 0;
        }

        return round(($totalExpenses / $totalBudget) * 100, 2);
    }

    /**
     * Get budget vs actual comparison data
     */
    private function getBudgetVsActual($startDate, $endDate) {
        $categories = $this->categoryModel->getAll();
        $expensesByCategory = $this->expenseModel->getExpensesByCategory($startDate, $endDate);

        $labels = [];
        $budget = [];
        $actual = [];

        foreach ($categories as $category) {
            $labels[] = $category['name'];
            $budget[] = $category['monthly_budget'];
            $actual[] = $expensesByCategory[$category['id']] ?? 0;
        }

        return [
            'labels' => $labels,
            'budget' => $budget,
            'actual' => $actual
        ];
    }

    /**
     * Export data to CSV format
     */
    private function exportToCSV($data) {
        $filename = 'expense_dashboard_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // Write summary
        fputcsv($output, ['Expense Dashboard Summary']);
        fputcsv($output, ['Period', $data['summary']['start_date'] . ' to ' . $data['summary']['end_date']]);
        fputcsv($output, ['Total Expenses', $data['summary']['total_expenses']]);
        fputcsv($output, ['Pending Expenses', $data['summary']['pending_expenses']]);
        fputcsv($output, ['Budget Utilization', $data['summary']['budget_utilization'] . '%']);
        fputcsv($output, ['Average Daily Expense', $data['summary']['avg_daily_expense']]);
        fputcsv($output, []);

        // Write trend data
        fputcsv($output, ['Daily Expense Trend']);
        fputcsv($output, ['Date', 'Amount']);
        foreach ($data['trend']['values'] as $index => $value) {
            fputcsv($output, [$data['trend']['labels'][$index], $value]);
        }
        fputcsv($output, []);

        // Write category distribution
        fputcsv($output, ['Category Distribution']);
        fputcsv($output, ['Category', 'Amount']);
        foreach ($data['categories']['values'] as $index => $value) {
            fputcsv($output, [$data['categories']['labels'][$index], $value]);
        }
        fputcsv($output, []);

        // Write recent expenses
        fputcsv($output, ['Recent Expenses']);
        fputcsv($output, ['Date', 'Category', 'Description', 'Amount', 'Status']);
        foreach ($data['recent_expenses'] as $expense) {
            fputcsv($output, [
                $expense['date'],
                $expense['category_name'],
                $expense['description'],
                $expense['amount'],
                $expense['status']
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Export data to PDF format
     */
    private function exportToPDF($data, $includeCharts) {
        // TODO: Implement PDF export using a PDF library
        return ['error' => 'PDF export not implemented yet'];
    }

    /**
     * Export data to Excel format
     */
    private function exportToExcel($data, $includeCharts) {
        // TODO: Implement Excel export using a spreadsheet library
        return ['error' => 'Excel export not implemented yet'];
    }
} 