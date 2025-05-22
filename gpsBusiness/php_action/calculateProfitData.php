<?php
require_once 'core.php';

$response = array(
    'success' => false,
    'messages' => '',
    'totalPurchase' => 0,
    'totalSales' => 0,
    'totalCredit' => 0,
    'totalExpenses' => 0,
    'totalInvestment' => 0,
    'grossProfit' => 0,
    'netProfit' => 0,
    'dailyData' => array(),
    'expenseCategories' => array()
);

if ($_POST) {
    $startDate = mysqli_real_escape_string($connect, $_POST['startDate']);
    $endDate = mysqli_real_escape_string($connect, $_POST['endDate']);

    try {
        // Validate dates
        if (!DateTime::createFromFormat('Y-m-d', $startDate) || !DateTime::createFromFormat('Y-m-d', $endDate)) {
            throw new Exception('Invalid date format');
        }

        // Calculate total purchases from GPS Orders
        $purchaseSql = "SELECT DATE(order_date) as date, COALESCE(SUM(total_price), 0) as total_purchase 
                        FROM gps_orders 
                        WHERE order_date BETWEEN ? AND ?
                        GROUP BY DATE(order_date)";
        $stmt = $connect->prepare($purchaseSql);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $purchaseResult = $stmt->get_result();
        $purchaseData = array();
        while ($row = $purchaseResult->fetch_assoc()) {
            $purchaseData[$row['date']] = $row['total_purchase'];
            $response['totalPurchase'] += $row['total_purchase'];
        }

        // Calculate total sales with daily breakdown
        $salesSql = "SELECT DATE(sale_date) as date, COALESCE(SUM(total_price), 0) as total_sales 
                     FROM gps_sales 
                     WHERE sale_date BETWEEN ? AND ?
                     GROUP BY DATE(sale_date)";
        $stmt = $connect->prepare($salesSql);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $salesResult = $stmt->get_result();
        $salesData = array();
        while ($row = $salesResult->fetch_assoc()) {
            $salesData[$row['date']] = $row['total_sales'];
            $response['totalSales'] += $row['total_sales'];
        }

        // Calculate total credits from orders
        $creditSql = "SELECT DATE(order_date) as date, COALESCE(SUM(credit_amount), 0) as total_credit 
                      FROM gps_orders 
                      WHERE has_credit = 1 
                      AND order_date BETWEEN ? AND ?
                      GROUP BY DATE(order_date)";
        $stmt = $connect->prepare($creditSql);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $creditResult = $stmt->get_result();
        $creditData = array();
        while ($row = $creditResult->fetch_assoc()) {
            $creditData[$row['date']] = $row['total_credit'];
            $response['totalCredit'] += $row['total_credit'];
        }

        // Calculate total expenses with categories
        $expenseSql = "SELECT DATE(e.expense_date) as date, 
                              COALESCE(SUM(e.total_amount), 0) as total_expenses,
                              ec.category_name,
                              COALESCE(SUM(e.total_amount), 0) as category_amount
                       FROM gps_expenses e
                       LEFT JOIN gps_expense_categories ec ON e.category_id = ec.id
                       WHERE e.expense_date BETWEEN ? AND ?
                       GROUP BY DATE(e.expense_date), ec.category_name";
        $stmt = $connect->prepare($expenseSql);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $expenseResult = $stmt->get_result();
        $expenseData = array();
        $categoryTotals = array();
        while ($row = $expenseResult->fetch_assoc()) {
            if (!isset($expenseData[$row['date']])) {
                $expenseData[$row['date']] = 0;
            }
            $expenseData[$row['date']] += $row['total_expenses'];
            $response['totalExpenses'] += $row['total_expenses'];
            
            if (!isset($categoryTotals[$row['category_name']])) {
                $categoryTotals[$row['category_name']] = 0;
            }
            $categoryTotals[$row['category_name']] += $row['category_amount'];
        }

        // Calculate total investments
        $investmentSql = "SELECT DATE(investment_date) as date, COALESCE(SUM(amount), 0) as total_investment 
                         FROM gps_investors 
                         WHERE investment_date BETWEEN ? AND ?
                         GROUP BY DATE(investment_date)";
        $stmt = $connect->prepare($investmentSql);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $investmentResult = $stmt->get_result();
        $investmentData = array();
        while ($row = $investmentResult->fetch_assoc()) {
            $investmentData[$row['date']] = $row['total_investment'];
            $response['totalInvestment'] += $row['total_investment'];
        }

        // Combine daily data and calculate profits
        $dates = array_unique(array_merge(
            array_keys($salesData),
            array_keys($purchaseData),
            array_keys($creditData),
            array_keys($expenseData),
            array_keys($investmentData)
        ));
        sort($dates);

        foreach ($dates as $date) {
            $dailySales = isset($salesData[$date]) ? $salesData[$date] : 0;
            $dailyPurchase = isset($purchaseData[$date]) ? $purchaseData[$date] : 0;
            $dailyCredit = isset($creditData[$date]) ? $creditData[$date] : 0;
            $dailyExpenses = isset($expenseData[$date]) ? $expenseData[$date] : 0;
            $dailyInvestment = isset($investmentData[$date]) ? $investmentData[$date] : 0;

            $dailyGrossProfit = $dailySales - $dailyPurchase;
            $dailyNetProfit = $dailyGrossProfit - ($dailyExpenses + $dailyCredit);

            $response['dailyData'][] = array(
                'date' => $date,
                'sales' => $dailySales,
                'purchase' => $dailyPurchase,
                'credit' => $dailyCredit,
                'expenses' => $dailyExpenses,
                'investment' => $dailyInvestment,
                'grossProfit' => $dailyGrossProfit,
                'netProfit' => $dailyNetProfit
            );
        }

        // Calculate total profits
        $response['grossProfit'] = $response['totalSales'] - $response['totalPurchase'];
        $response['netProfit'] = $response['grossProfit'] - ($response['totalExpenses'] + $response['totalCredit']);

        // Add expense categories breakdown
        $response['expenseCategories'] = $categoryTotals;

        $response['success'] = true;
        $response['messages'] = 'Data calculated successfully';

    } catch (Exception $e) {
        $response['messages'] = 'Error calculating profit data: ' . $e->getMessage();
        error_log('GPS Profit Calculation Error: ' . $e->getMessage());
    }
} else {
    $response['messages'] = 'Invalid request';
}

$connect->close();
echo json_encode($response); 