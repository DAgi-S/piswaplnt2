<?php

class AnnualReportCalculator {
    private $db;
    private $accountId;
    private $currency;

    public function __construct($db, $accountId, $currency) {
        $this->db = $db;
        $this->accountId = $accountId;
        $this->currency = $currency;
    }

    public function getYearRange($fromYear, $toYear) {
        $years = [];
        $grandTotalDeposits = 0;
        $grandTotalWithdrawals = 0;

        for ($year = $fromYear; $year <= $toYear; $year++) {
            $yearData = $this->getYearData($year);
            $years[] = $yearData;

            // Update grand totals
            $grandTotalDeposits += $this->cleanAmount($yearData['totals']['deposits']);
            $grandTotalWithdrawals += $this->cleanAmount($yearData['totals']['withdrawals']);
        }

        return [
            'years' => $years,
            'grandTotals' => [
                'deposits' => $this->formatAmount($grandTotalDeposits),
                'withdrawals' => $this->formatAmount($grandTotalWithdrawals),
                'netChange' => $this->formatAmount($grandTotalDeposits - $grandTotalWithdrawals)
            ]
        ];
    }

    private function getYearData($year) {
        $monthlyData = $this->getMonthlyData($year);
        $yearTotalDeposits = 0;
        $yearTotalWithdrawals = 0;
        $formattedMonths = [];

        foreach ($monthlyData as $month) {
            $deposits = floatval($month['deposits']);
            $withdrawals = floatval($month['withdrawals']);
            
            $yearTotalDeposits += $deposits;
            $yearTotalWithdrawals += $withdrawals;

            $formattedMonths[] = [
                'name' => date('F', mktime(0, 0, 0, $month['month'], 1)),
                'deposits' => $this->formatAmount($deposits),
                'withdrawals' => $this->formatAmount($withdrawals),
                'netChange' => $this->formatAmount($deposits - $withdrawals)
            ];
        }

        return [
            'year' => $year,
            'months' => $formattedMonths,
            'totals' => [
                'deposits' => $this->formatAmount($yearTotalDeposits),
                'withdrawals' => $this->formatAmount($yearTotalWithdrawals),
                'netChange' => $this->formatAmount($yearTotalDeposits - $yearTotalWithdrawals)
            ]
        ];
    }

    private function getMonthlyData($year) {
        $monthlyData = [];
        
        // Initialize all months with zero values
        for ($month = 1; $month <= 12; $month++) {
            $monthlyData[$month] = [
                'month' => $month,
                'deposits' => 0,
                'withdrawals' => 0
            ];
        }

        // Get deposits
        $depositSql = "SELECT 
            MONTH(transaction_date) as month,
            COALESCE(SUM(amount), 0) as total
        FROM digitalswap 
        WHERE YEAR(transaction_date) = ? 
            AND account_id = ?
            AND type = 'deposit'
            AND status = 1
        GROUP BY MONTH(transaction_date)";

        $stmt = $this->db->prepare($depositSql);
        $stmt->bind_param("ii", $year, $this->accountId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            if (isset($monthlyData[$row['month']])) {
                $monthlyData[$row['month']]['deposits'] = floatval($row['total']);
            }
        }
        $stmt->close();

        // Get withdrawals
        $withdrawSql = "SELECT 
            MONTH(transaction_date) as month,
            COALESCE(SUM(amount), 0) as total
        FROM digitalswap 
        WHERE YEAR(transaction_date) = ? 
            AND account_id = ?
            AND type = 'withdraw'
            AND status = 1
        GROUP BY MONTH(transaction_date)";

        $stmt = $this->db->prepare($withdrawSql);
        $stmt->bind_param("ii", $year, $this->accountId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            if (isset($monthlyData[$row['month']])) {
                $monthlyData[$row['month']]['withdrawals'] = floatval($row['total']);
            }
        }
        $stmt->close();

        return array_values($monthlyData);
    }

    private function formatAmount($amount) {
        return number_format($amount, 2, '.', ',');
    }

    private function cleanAmount($formattedAmount) {
        return floatval(str_replace(',', '', $formattedAmount));
    }
} 