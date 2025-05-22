<?php
/**
 * Expense Module API Routes
 */

// Middleware for authentication
$router->group(['middleware' => 'auth'], function($router) {
    
    // Expense Categories
    $router->group(['prefix' => 'expense-categories'], function($router) {
        $router->get('/', ['middleware' => 'can:expense_category_view', 'uses' => 'ExpenseCategoryController@index']);
        $router->post('/', ['middleware' => 'can:expense_category_create', 'uses' => 'ExpenseCategoryController@create']);
        $router->put('/{id}', ['middleware' => 'can:expense_category_edit', 'uses' => 'ExpenseCategoryController@update']);
        $router->delete('/{id}', ['middleware' => 'can:expense_category_delete', 'uses' => 'ExpenseCategoryController@delete']);
        $router->get('/{id}/budget', ['middleware' => 'can:expense_category_view', 'uses' => 'ExpenseCategoryController@getCategoryWithBudget']);
    });

    // Expenses
    $router->group(['prefix' => 'expenses'], function($router) {
        $router->get('/', ['middleware' => 'can:expense_view', 'uses' => 'ExpenseController@index']);
        $router->post('/', ['middleware' => 'can:expense_create', 'uses' => 'ExpenseController@create']);
        $router->get('/{id}', ['middleware' => 'can:expense_view', 'uses' => 'ExpenseController@show']);
        $router->put('/{id}', ['middleware' => 'can:expense_edit', 'uses' => 'ExpenseController@update']);
        $router->delete('/{id}', ['middleware' => 'can:expense_delete', 'uses' => 'ExpenseController@delete']);
        
        // Expense Approval
        $router->post('/{id}/approve', ['middleware' => 'can:expense_approve', 'uses' => 'ExpenseController@approve']);
        $router->post('/{id}/reject', ['middleware' => 'can:expense_approve', 'uses' => 'ExpenseController@reject']);
        
        // Expense Summary
        $router->get('/summary', ['middleware' => 'can:expense_view', 'uses' => 'ExpenseController@getSummary']);
    });

    // Expense Attachments
    $router->group(['prefix' => 'expense-attachments'], function($router) {
        $router->post('/', ['middleware' => 'can:expense_edit', 'uses' => 'ExpenseAttachmentController@upload']);
        $router->get('/{id}', ['middleware' => 'can:expense_view', 'uses' => 'ExpenseAttachmentController@download']);
        $router->delete('/{id}', ['middleware' => 'can:expense_edit', 'uses' => 'ExpenseAttachmentController@delete']);
        $router->get('/expense/{expenseId}', ['middleware' => 'can:expense_view', 'uses' => 'ExpenseAttachmentController@getExpenseAttachments']);
    });

    // Dashboard
    $router->group(['prefix' => 'dashboard'], function($router) {
        $router->get('/summary', ['middleware' => 'can:expense_view', 'uses' => 'ExpenseDashboardController@getSummary']);
        $router->get('/trend', ['middleware' => 'can:expense_view', 'uses' => 'ExpenseDashboardController@getTrendData']);
        $router->get('/categories', ['middleware' => 'can:expense_view', 'uses' => 'ExpenseDashboardController@getCategoryDistribution']);
        $router->get('/recent', ['middleware' => 'can:expense_view', 'uses' => 'ExpenseDashboardController@getRecentExpenses']);
        $router->get('/top-categories', ['middleware' => 'can:expense_view', 'uses' => 'ExpenseDashboardController@getTopCategories']);
        $router->get('/export', ['middleware' => 'can:expense_export', 'uses' => 'ExpenseDashboardController@exportReport']);
    });
});

// Dashboard routes
$router->get('/expense-dashboard', function($request) {
    try {
        $controller = new ExpenseDashboardController();
        $filters = $request->getQueryParams();
        
        $data = [
            'summary' => $controller->getSummary($filters),
            'charts' => [
                'trend' => $controller->getTrendData($filters),
                'categories' => $controller->getCategoryDistribution($filters)
            ],
            'tables' => [
                'recent_expenses' => $controller->getRecentExpenses($filters),
                'top_categories' => $controller->getTopCategories($filters)
            ]
        ];

        return json_response($data);
    } catch (Exception $e) {
        return json_response(['error' => $e->getMessage()], 400);
    }
});

$router->get('/expense-dashboard/export', function($request) {
    try {
        $controller = new ExpenseDashboardController();
        $filters = $request->getQueryParams();
        $controller->exportReport($filters);
    } catch (Exception $e) {
        return json_response(['error' => $e->getMessage()], 400);
    }
}); 