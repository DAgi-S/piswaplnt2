<?php
/**
 * Expense Dashboard Routes
 */

// Get dashboard summary data
$router->get('/api/expense-dashboard/summary', function($request) {
    $controller = new ExpenseDashboardController();
    $startDate = $request->get('start_date');
    $endDate = $request->get('end_date');
    
    $response = $controller->getSummary($startDate, $endDate);
    
    if (isset($response['error'])) {
        return json_response(['error' => $response['error']], 400);
    }
    
    return json_response($response);
});

// Export dashboard data
$router->post('/api/expense-dashboard/export', function($request) {
    $controller = new ExpenseDashboardController();
    $format = $request->post('format');
    $startDate = $request->post('start_date');
    $endDate = $request->post('end_date');
    $includeCharts = $request->post('include_charts') === 'true';
    
    $response = $controller->export($format, $startDate, $endDate, $includeCharts);
    
    if (isset($response['error'])) {
        return json_response(['error' => $response['error']], 400);
    }
    
    // The export methods will handle their own output
    return $response;
}); 