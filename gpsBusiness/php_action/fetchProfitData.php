<?php
require_once '../../php_action/core.php';

$response = array(
    'success' => false,
    'messages' => '',
    'summary' => array(),
    'chart' => array()
);

if ($_POST) {
    try {
        $startDate = $_POST['startDate'];
        $endDate = $_POST['endDate'];

        // Validate dates
        if (!DateTime::createFromFormat('Y-m-d', $startDate) || !DateTime::createFromFormat('Y-m-d', $endDate)) {
            throw new Exception('Invalid date format');
        }

        // 1. GPS Sales (Income)
        $salesSql = "SELECT COALESCE(SUM(total_price), 0) as total_sales,
                            COALESCE(SUM(CASE WHEN currency = 'USD' THEN total_price * rate ELSE total_price END), 0) as total_sales_etb
                     FROM gps_sales 
                     WHERE sale_date BETWEEN ? AND ?";
        $stmt = $connect->prepare($salesSql);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $salesResult = $stmt->get_result()->fetch_assoc();
        $totalSales = floatval($salesResult['total_sales_etb']);

        // 2. GPS Orders/Purchases (Cost)
        $purchaseSql = "SELECT COALESCE(SUM(total_price), 0) as total_purchase,
                               COALESCE(SUM(CASE WHEN currency = 'USD' THEN total_price * rate ELSE total_price END), 0) as total_purchase_etb
                        FROM gps_orders 
                        WHERE order_date BETWEEN ? AND ?";
        $stmt = $connect->prepare($purchaseSql);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $purchaseResult = $stmt->get_result()->fetch_assoc();
        $totalPurchase = floatval($purchaseResult['total_purchase_etb']);

        // 3. GPS Expenses
        $expenseSql = "SELECT COALESCE(SUM(total_amount), 0) as total_expenses,
                              COALESCE(SUM(CASE WHEN currency = 'USD' THEN total_amount * rate ELSE total_amount END), 0) as total_expenses_etb
                       FROM gps_expenses 
                       WHERE expense_date BETWEEN ? AND ?";
        $stmt = $connect->prepare($expenseSql);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $expenseResult = $stmt->get_result()->fetch_assoc();
        $totalExpenses = floatval($expenseResult['total_expenses_etb']);

        // 4. GPS Credits
        $creditSql = "SELECT COALESCE(SUM(credit_amount), 0) as total_credit,
                             COALESCE(SUM(CASE WHEN currency = 'USD' THEN credit_amount * rate ELSE credit_amount END), 0) as total_credit_etb
                      FROM gps_orders 
                      WHERE has_credit = 1 
                      AND order_date BETWEEN ? AND ?";
        $stmt = $connect->prepare($creditSql);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $creditResult = $stmt->get_result()->fetch_assoc();
        $totalCredit = floatval($creditResult['total_credit_etb']);

        // 5. GPS Investments (if exists)
        $investmentSql = "SELECT COALESCE(SUM(amount), 0) as total_investment,
                                COALESCE(SUM(CASE WHEN currency = 'USD' THEN amount * rate ELSE amount END), 0) as total_investment_etb
                         FROM gps_investments 
                         WHERE investment_date BETWEEN ? AND ?";
        $stmt = $connect->prepare($investmentSql);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $investmentResult = $stmt->get_result()->fetch_assoc();
        $totalInvestment = floatval($investmentResult['total_investment_etb']);

        // Calculate daily transactions for chart
        $dailySql = "SELECT 
            dates.date,
            COALESCE(s.daily_sales, 0) as daily_sales,
            COALESCE(o.daily_purchase, 0) as daily_purchase,
            COALESCE(e.daily_expenses, 0) as daily_expenses,
            COALESCE(c.daily_credit, 0) as daily_credit,
            COALESCE(i.daily_investment, 0) as daily_investment
        FROM (
            SELECT date FROM (
                SELECT DATE(sale_date) as date FROM gps_sales WHERE sale_date BETWEEN ? AND ?
                UNION
                SELECT DATE(order_date) FROM gps_orders WHERE order_date BETWEEN ? AND ?
                UNION
                SELECT DATE(expense_date) FROM gps_expenses WHERE expense_date BETWEEN ? AND ?
                UNION
                SELECT DATE(investment_date) FROM gps_investments WHERE investment_date BETWEEN ? AND ?
            ) all_dates
        ) dates
        LEFT JOIN (
            SELECT DATE(sale_date) as date, 
                   SUM(CASE WHEN currency = 'USD' THEN total_price * rate ELSE total_price END) as daily_sales 
            FROM gps_sales 
            WHERE sale_date BETWEEN ? AND ?
            GROUP BY DATE(sale_date)
        ) s ON dates.date = s.date
        LEFT JOIN (
            SELECT DATE(order_date) as date, 
                   SUM(CASE WHEN currency = 'USD' THEN total_price * rate ELSE total_price END) as daily_purchase 
            FROM gps_orders 
            WHERE order_date BETWEEN ? AND ?
            GROUP BY DATE(order_date)
        ) o ON dates.date = o.date
        LEFT JOIN (
            SELECT DATE(expense_date) as date, 
                   SUM(CASE WHEN currency = 'USD' THEN total_amount * rate ELSE total_amount END) as daily_expenses 
            FROM gps_expenses 
            WHERE expense_date BETWEEN ? AND ?
            GROUP BY DATE(expense_date)
        ) e ON dates.date = e.date
        LEFT JOIN (
            SELECT DATE(order_date) as date, 
                   SUM(CASE WHEN currency = 'USD' THEN credit_amount * rate ELSE credit_amount END) as daily_credit 
            FROM gps_orders 
            WHERE has_credit = 1 AND order_date BETWEEN ? AND ?
            GROUP BY DATE(order_date)
        ) c ON dates.date = c.date
        LEFT JOIN (
            SELECT DATE(investment_date) as date, 
                   SUM(CASE WHEN currency = 'USD' THEN amount * rate ELSE amount END) as daily_investment 
            FROM gps_investments 
            WHERE investment_date BETWEEN ? AND ?
            GROUP BY DATE(investment_date)
        ) i ON dates.date = i.date
        ORDER BY dates.date ASC";

        $stmt = $connect->prepare($dailySql);
        $stmt->bind_param("ssssssssssssssssssss", 
            $startDate, $endDate, 
            $startDate, $endDate, 
            $startDate, $endDate,
            $startDate, $endDate,
            $startDate, $endDate,
            $startDate, $endDate,
            $startDate, $endDate,
            $startDate, $endDate,
            $startDate, $endDate
        );
        $stmt->execute();
        $dailyResult = $stmt->get_result();

        $labels = array();
        $grossProfit = array();
        $netProfit = array();

        while ($row = $dailyResult->fetch_assoc()) {
            $labels[] = date('d M', strtotime($row['date']));
            
            // Gross Profit = Sales - Purchase
            $dailyGrossProfit = $row['daily_sales'] - $row['daily_purchase'];
            
            // Net Profit = Gross Profit - (Expenses + Credit) + Investment
            $dailyNetProfit = $dailyGrossProfit - $row['daily_expenses'] - $row['daily_credit'] + $row['daily_investment'];
            
            $grossProfit[] = round($dailyGrossProfit, 2);
            $netProfit[] = round($dailyNetProfit, 2);
        }

        // Calculate total profits
        $totalGrossProfit = $totalSales - $totalPurchase;
        $totalNetProfit = $totalGrossProfit - $totalExpenses - $totalCredit + $totalInvestment;

        // Prepare response
        $response['success'] = true;
        $response['summary'] = array(
            'totalSales' => $totalSales,
            'totalPurchase' => $totalPurchase,
            'totalExpenses' => $totalExpenses,
            'totalCredit' => $totalCredit,
            'totalInvestment' => $totalInvestment,
            'grossProfit' => $totalGrossProfit,
            'netProfit' => $totalNetProfit
        );
        $response['chart'] = array(
            'labels' => $labels,
            'grossProfit' => $grossProfit,
            'netProfit' => $netProfit
        );

    } catch (Exception $e) {
        $response['success'] = false;
        $response['messages'] = 'Error: ' . $e->getMessage();
    }
} else {
    $response['messages'] = 'Invalid request';
}

$connect->close();
header('Content-Type: application/json');
echo json_encode($response); 