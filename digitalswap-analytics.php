<?php require_once 'includes/header.php'; ?>
<?php require_once 'php_action/db_connect.php'; ?>

<div class="row">
    <div class="col-md-12">
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Home</a></li>
            <li class="active">Digital Swap Analytics</li>
        </ol>

        <div class="row">
            <!-- ETB Overview Section -->
            <div class="col-md-6">
                <div class="currency-section etb-section">
                    <h3 class="currency-title">
                        <i class="glyphicon glyphicon-stats"></i> ETB Overview
                    </h3>
                    <div class="summary-grid">
                        <!-- Row 1 -->
                        <div class="summary-card balance">
                            <div class="card-icon">
                                <i class="glyphicon glyphicon-usd"></i>
                            </div>
                            <div class="card-content">
                                <h3 id="etbBalance">0.00</h3>
                                <span>ETB Balance</span>
                            </div>
                        </div>
                        <div class="summary-card deposits">
                            <div class="card-icon">
                                <i class="glyphicon glyphicon-plus"></i>
                            </div>
                            <div class="card-content">
                                <h3 id="etbDeposits">0.00</h3>
                                <span>Total Deposits</span>
                            </div>
                        </div>
                        <div class="summary-card withdrawals">
                            <div class="card-icon">
                                <i class="glyphicon glyphicon-minus"></i>
                            </div>
                            <div class="card-content">
                                <h3 id="etbWithdraws">0.00</h3>
                                <span>Total Withdrawals</span>
                            </div>
                        </div>
                        <div class="summary-card today-transactions">
                            <div class="card-icon">
                                <i class="glyphicon glyphicon-transfer"></i>
                            </div>
                            <div class="card-content">
                                <h3 id="etbTodayTransactions">0</h3>
                                <span>Today's Transactions</span>
                            </div>
                        </div>
                        <!-- Row 2 -->
                        <div class="summary-card today-deposits">
                            <div class="card-icon">
                                <i class="glyphicon glyphicon-time"></i>
                            </div>
                            <div class="card-content">
                                <h3 id="etbTodayDeposits">0.00</h3>
                                <span>Today's Deposits</span>
                            </div>
                        </div>
                        <div class="summary-card today-withdrawals">
                            <div class="card-icon">
                                <i class="glyphicon glyphicon-time"></i>
                            </div>
                            <div class="card-content">
                                <h3 id="etbTodayWithdraws">0.00</h3>
                                <span>Today's Withdrawals</span>
                            </div>
                        </div>
                        <div class="summary-card month-deposits">
                            <div class="card-icon">
                                <i class="glyphicon glyphicon-calendar"></i>
                            </div>
                            <div class="card-content">
                                <h3 id="etbMonthDeposits">0.00</h3>
                                <span>This Month's Deposits</span>
                            </div>
                        </div>
                        <div class="summary-card month-withdrawals">
                            <div class="card-icon">
                                <i class="glyphicon glyphicon-calendar"></i>
                            </div>
                            <div class="card-content">
                                <h3 id="etbMonthWithdraws">0.00</h3>
                                <span>This Month's Withdrawals</span>
                            </div>
                        </div>
                    </div>
                    <!-- Performance Metrics -->
                    
                </div>
            </div>

            <!-- USD Overview Section -->
            <div class="col-md-6">
                <div class="currency-section usd-section">
                    <h3 class="currency-title">
                        <i class="glyphicon glyphicon-stats"></i> USD Overview
                    </h3>
                    <div class="summary-grid">
                        <!-- Row 1 -->
                        <div class="summary-card balance">
                            <div class="card-icon">
                                <i class="glyphicon glyphicon-usd"></i>
                            </div>
                            <div class="card-content">
                                <h3 id="usdBalance">0.00</h3>
                                <span>USD Balance</span>
                            </div>
                        </div>
                        <div class="summary-card deposits">
                            <div class="card-icon">
                                <i class="glyphicon glyphicon-plus"></i>
                            </div>
                            <div class="card-content">
                                <h3 id="usdDeposits">0.00</h3>
                                <span>Total Deposits</span>
                            </div>
                        </div>
                        <div class="summary-card withdrawals">
                            <div class="card-icon">
                                <i class="glyphicon glyphicon-minus"></i>
                            </div>
                            <div class="card-content">
                                <h3 id="usdWithdraws">0.00</h3>
                                <span>Total Withdrawals</span>
                            </div>
                        </div>
                        <div class="summary-card today-transactions">
                            <div class="card-icon">
                                <i class="glyphicon glyphicon-transfer"></i>
                            </div>
                            <div class="card-content">
                                <h3 id="usdTodayTransactions">0</h3>
                                <span>Today's Transactions</span>
                            </div>
                        </div>
                        <!-- Row 2 -->
                        <div class="summary-card today-deposits">
                            <div class="card-icon">
                                <i class="glyphicon glyphicon-time"></i>
                            </div>
                            <div class="card-content">
                                <h3 id="usdTodayDeposits">0.00</h3>
                                <span>Today's Deposits</span>
                            </div>
                        </div>
                        <div class="summary-card today-withdrawals">
                            <div class="card-icon">
                                <i class="glyphicon glyphicon-time"></i>
                            </div>
                            <div class="card-content">
                                <h3 id="usdTodayWithdraws">0.00</h3>
                                <span>Today's Withdrawals</span>
                            </div>
                        </div>
                        <div class="summary-card month-deposits">
                            <div class="card-icon">
                                <i class="glyphicon glyphicon-calendar"></i>
                            </div>
                            <div class="card-content">
                                <h3 id="usdMonthDeposits">0.00</h3>
                                <span>This Month's Deposits</span>
                            </div>
                        </div>
                        <div class="summary-card month-withdrawals">
                            <div class="card-icon">
                                <i class="glyphicon glyphicon-calendar"></i>
                            </div>
                            <div class="card-content">
                                <h3 id="usdMonthWithdraws">0.00</h3>
                                <span>This Month's Withdrawals</span>
                            </div>
                        </div>
                    </div>
                    <!-- Performance Metrics -->
                   
                </div>
            </div>
        </div>

        <!-- ETB Accounts Summary Section -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="glyphicon glyphicon-list"></i> ETB Accounts Summary</h3>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="etbAccountsSummaryTable">
                        <thead>
                            <tr>
                                <th class="text-left">Account Owner</th>
                                <th class="text-left">Platform</th>
                                <th class="text-right">Total Transactions</th>
                                <th class="text-right">Total Deposits</th>
                                <th class="text-right">Total Withdrawals</th>
                                <th class="text-right">Current Balance</th>
                                <th class="text-right">Last Transaction</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql_etb = "SELECT 
                                a.id,
                                a.account_owner,
                                a.account_platform,
                                a.number_of_transactions,
                                COALESCE(SUM(CASE WHEN d.type = 'deposit' THEN d.amount ELSE 0 END), 0) as total_deposits,
                                COALESCE(SUM(CASE WHEN d.type = 'withdraw' THEN d.amount ELSE 0 END), 0) as total_withdrawals,
                                COALESCE(SUM(CASE WHEN d.type = 'deposit' THEN d.amount ELSE -d.amount END), 0) as current_balance,
                                MAX(d.transaction_date) as last_transaction
                            FROM accounts a
                            LEFT JOIN digitalswap d ON a.id = d.account_id
                            WHERE a.Currency = 'ETB'
                            GROUP BY a.id, a.account_owner, a.account_platform, a.number_of_transactions
                            ORDER BY a.account_platform, a.account_owner";

                            $result_etb = $connect->query($sql_etb);

                            while($row = $result_etb->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td class='text-left'><strong>".$row['account_owner']."</strong></td>";
                                echo "<td class='text-left'><span class='platform-badge'>".$row['account_platform']."</span></td>";
                                echo "<td class='text-right'>".number_format($row['number_of_transactions'])."</td>";
                                echo "<td class='text-right text-success'>ETB ".number_format($row['total_deposits'], 2)."</td>";
                                echo "<td class='text-right text-danger'>ETB ".number_format($row['total_withdrawals'], 2)."</td>";
                                
                                $balanceClass = $row['current_balance'] >= 0 ? 'text-success' : 'text-danger';
                                echo "<td class='text-right ".$balanceClass."'>ETB ".number_format($row['current_balance'], 2)."</td>";
                                
                                echo "<td class='text-right'>".($row['last_transaction'] ? date('d M Y', strtotime($row['last_transaction'])) : 'No transactions')."</td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- USD Accounts Summary Section -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="glyphicon glyphicon-list"></i> USD Accounts Summary</h3>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="usdAccountsSummaryTable">
                        <thead>
                            <tr>
                                <th class="text-left">Account Owner</th>
                                <th class="text-left">Platform</th>
                                <th class="text-right">Total Transactions</th>
                                <th class="text-right">Total Deposits</th>
                                <th class="text-right">Total Withdrawals</th>
                                <th class="text-right">Current Balance</th>
                                <th class="text-right">Last Transaction</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql_usd = "SELECT 
                                a.id,
                                a.account_owner,
                                a.account_platform,
                                a.number_of_transactions,
                                COALESCE(SUM(CASE WHEN d.type = 'deposit' THEN d.amount ELSE 0 END), 0) as total_deposits,
                                COALESCE(SUM(CASE WHEN d.type = 'withdraw' THEN d.amount ELSE 0 END), 0) as total_withdrawals,
                                COALESCE(SUM(CASE WHEN d.type = 'deposit' THEN d.amount ELSE -d.amount END), 0) as current_balance,
                                MAX(d.transaction_date) as last_transaction
                            FROM accounts a
                            LEFT JOIN digitalswap d ON a.id = d.account_id
                            WHERE a.Currency = 'USD'
                            GROUP BY a.id, a.account_owner, a.account_platform, a.number_of_transactions
                            ORDER BY a.account_platform, a.account_owner";

                            $result_usd = $connect->query($sql_usd);

                            while($row = $result_usd->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td class='text-left'><strong>".$row['account_owner']."</strong></td>";
                                echo "<td class='text-left'><span class='platform-badge'>".$row['account_platform']."</span></td>";
                                echo "<td class='text-right'>".number_format($row['number_of_transactions'])."</td>";
                                echo "<td class='text-right text-success'>USD ".number_format($row['total_deposits'], 2)."</td>";
                                echo "<td class='text-right text-danger'>USD ".number_format($row['total_withdrawals'], 2)."</td>";
                                
                                $balanceClass = $row['current_balance'] >= 0 ? 'text-success' : 'text-danger';
                                echo "<td class='text-right ".$balanceClass."'>USD ".number_format($row['current_balance'], 2)."</td>";
                                
                                echo "<td class='text-right'>".($row['last_transaction'] ? date('d M Y', strtotime($row['last_transaction'])) : 'No transactions')."</td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Grid Layout Styles */
.summary-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    margin-bottom: 15px;
}

.summary-card {
    background: #fff;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.summary-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.card-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 10px;
}

.card-content h3 {
    font-size: 14px;
    font-weight: 600;
    margin: 0;
    line-height: 1.2;
}

.card-content span {
    font-size: 11px;
    color: #6c757d;
    display: block;
    margin-top: 4px;
}

/* Card Colors */
.balance .card-icon {
    background: rgba(52, 152, 219, 0.15);
    color: #3498db;
}

.deposits .card-icon {
    background: rgba(46, 204, 113, 0.15);
    color: #2ecc71;
}

.withdrawals .card-icon {
    background: rgba(231, 76, 60, 0.15);
    color: #e74c3c;
}

.today-transactions .card-icon,
.today-deposits .card-icon,
.today-withdrawals .card-icon {
    background: rgba(155, 89, 182, 0.15);
    color: #9b59b6;
}

.month-deposits .card-icon,
.month-withdrawals .card-icon {
    background: rgba(241, 196, 15, 0.15);
    color: #f1c40f;
}

/* Loading State */
.loading {
    opacity: 0.7;
    position: relative;
}

.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 20px;
    height: 20px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: translate(-50%, -50%) rotate(0deg); }
    100% { transform: translate(-50%, -50%) rotate(360deg); }
}

/* Error State */
.error-state {
    color: #e74c3c;
    font-style: italic;
}

/* Responsive Adjustments */
@media (max-width: 1200px) {
    .summary-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 8px;
    }
    
    .card-content h3 {
        font-size: 13px;
    }
    
    .card-content span {
        font-size: 10px;
    }
}

@media (max-width: 992px) {
    .summary-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
    }
}

@media (max-width: 768px) {
    .summary-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
    }
    
    .summary-card {
        padding: 10px;
    }
    
    .card-icon {
        width: 28px;
        height: 28px;
        margin-bottom: 8px;
    }
    
    .card-content h3 {
        font-size: 12px;
    }
    
    .card-content span {
        font-size: 9px;
        margin-top: 2px;
    }
}

@media (max-width: 480px) {
    .summary-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 6px;
    }
    
    .summary-card {
        padding: 8px;
    }
    
    .card-icon {
        width: 24px;
        height: 24px;
        margin-bottom: 6px;
    }
    
    .card-content h3 {
        font-size: 11px;
    }
    
    .card-content span {
        font-size: 8px;
    }
}
</style>

<!-- Make sure jQuery is loaded first -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Your custom JavaScript -->
<script src="custom/js/digitalswap-analytics.js"></script>

<!-- Initialize DataTables with alignment settings -->
<script>
$(document).ready(function() {
    // Initialize ETB Accounts Table
    $('#etbAccountsSummaryTable').DataTable({
        "order": [[1, "asc"], [0, "asc"]],
        "pageLength": 10,
        "responsive": true,
        "dom": '<"top"f>rt<"bottom"lip><"clear">',
        "language": {
            "search": "Search ETB accounts: ",
            "loadingRecords": "Loading...",
            "processing": "Processing...",
            "zeroRecords": "No matching records found"
        },
        "columnDefs": [
            { "className": "text-left", "targets": [0, 1] },
            { "className": "text-right", "targets": [2, 3, 4, 5, 6] }
        ]
    });

    // Initialize USD Accounts Table
    $('#usdAccountsSummaryTable').DataTable({
        "order": [[1, "asc"], [0, "asc"]],
        "pageLength": 10,
        "responsive": true,
        "dom": '<"top"f>rt<"bottom"lip><"clear">',
        "language": {
            "search": "Search USD accounts: ",
            "loadingRecords": "Loading...",
            "processing": "Processing...",
            "zeroRecords": "No matching records found"
        },
        "columnDefs": [
            { "className": "text-left", "targets": [0, 1] },
            { "className": "text-right", "targets": [2, 3, 4, 5, 6] }
        ]
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 