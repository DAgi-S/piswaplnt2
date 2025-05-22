<?php
require_once 'includes/core.php';

// Debug logging
error_log("Dashboard Access - Starting");
error_log("Session data: " . print_r($_SESSION, true));

// Check if user is logged in
requireLogin();
error_log("Dashboard Access - User is logged in");

// Get current user data
$user = getCurrentUser();
error_log("Dashboard Access - getCurrentUser result: " . ($user ? "User found" : "No user found"));

if (!$user) {
    error_log("Dashboard Access - No user data found in getCurrentUser");
    setFlashMessage('User data not found', 'danger');
    header('Location: ' . SITE_URL . '/logout.php');
    exit();
}

// Set active account if not set
if (!isset($_SESSION['active_guest_account'])) {
    error_log("Dashboard Access - No active account set, attempting to find one");
    // Get first linked account
    $sql = "SELECT gal.account_id 
            FROM guest_account_links gal 
            JOIN guest_users gu ON gal.guest_id = gu.id
            WHERE gu.id = ? 
            LIMIT 1";
    $stmt = $connect->prepare($sql);
    error_log("Dashboard Access - Looking for account with guest_id: " . $_SESSION['guest_id']);
    $stmt->bind_param("i", $_SESSION['guest_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $_SESSION['active_guest_account'] = $row['account_id'];
        error_log("Dashboard Access - Found and set active account: " . $row['account_id']);
    } else {
        error_log("Dashboard Access - No linked accounts found");
    }
}

// Get guest data with account info
error_log("Dashboard Access - Attempting to get guest data with account info");
$sql = "SELECT gu.*, a.account_owner, a.account_platform, a.Currency 
        FROM guest_users gu 
        LEFT JOIN guest_account_links gal ON gu.id = gal.guest_id
        LEFT JOIN accounts a ON gal.account_id = a.id 
        WHERE gu.id = ?";
$stmt = $connect->prepare($sql);

if (!$stmt) {
    error_log("Dashboard Access - SQL prepare error: " . $connect->error);
    setFlashMessage('Database error occurred', 'danger');
    header('Location: ' . SITE_URL . '/logout.php');
    exit();
}

$stmt->bind_param("i", $_SESSION['guest_id']);
$stmt->execute();
$result = $stmt->get_result();
$guestData = $result->fetch_assoc();

error_log("Dashboard Access - Guest data query result: " . ($guestData ? "Data found" : "No data found"));

if (!$guestData) {
    error_log("Dashboard Access - No guest data found for guest_id: " . $_SESSION['guest_id']);
    setFlashMessage('Account data not found', 'danger');
    header('Location: ' . SITE_URL . '/logout.php');
    exit();
}

// Get all linked accounts
error_log("Dashboard Access - Fetching all linked accounts");
$sql = "SELECT a.* 
        FROM accounts a 
        JOIN guest_account_links gal ON a.id = gal.account_id 
        JOIN guest_users gu ON gal.guest_id = gu.id
        WHERE gu.id = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("i", $_SESSION['guest_id']);
$stmt->execute();
$result = $stmt->get_result();
$guestAccounts = $result->fetch_all(MYSQLI_ASSOC);

error_log("Dashboard Access - Found " . count($guestAccounts) . " linked accounts");

require_once 'includes/header.php';
?>

<div class="container">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h4>
                                <i class="fas fa-user-circle"></i> 
                                Welcome, <?php echo htmlspecialchars($guestData['full_name'] ?? ''); ?>
                            </h4>
                            <p>
                                <i class="fas fa-id-card"></i> Guest ID: <?php echo htmlspecialchars($guestData['guest_id'] ?? ''); ?>
                                <?php if (!empty($guestData['account_platform'])): ?>
                                <span class="mx-3">|</span>
                                <i class="fas fa-university"></i> <?php echo htmlspecialchars($guestData['account_platform']); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-6 text-end">
                            <h4><i class="fas fa-money-bill"></i> <?php echo htmlspecialchars($guestData['Currency'] ?? 'ETB'); ?></h4>
                            <p>
                                <i class="fas fa-calendar"></i> Last Login: <?php echo date('M d, Y H:i', strtotime($guestData['last_login'] ?? 'now')); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="noAccountsMessage" class="alert alert-info">
        <i class="fas fa-info-circle"></i> No accounts linked to your profile. Please contact support for assistance.
    </div>

    <div id="statsSection">
        <!-- Stats Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="card mb-4">
                    <div class="card-body">
                        <i class="fas fa-balance-scale text-primary fs-3 mb-3"></i>
                        <h6 class="card-subtitle mb-2 text-muted">Current Balance</h6>
                        <h4 class="card-title text-primary" id="current-balance">0.00</h4>
                        <small class="text-muted" id="current-account">Active Account</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card mb-4">
                    <div class="card-body">
                        <i class="fas fa-arrow-circle-up text-success fs-3 mb-3"></i>
                        <h6 class="card-subtitle mb-2 text-muted">Recent Deposits</h6>
                        <h4 class="card-title text-success" id="recent-deposits">0.00</h4>
                        <small class="text-muted">Last 30 days</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card mb-4">
                    <div class="card-body">
                        <i class="fas fa-arrow-circle-down text-warning fs-3 mb-3"></i>
                        <h6 class="card-subtitle mb-2 text-muted">Recent Withdrawals</h6>
                        <h4 class="card-title text-warning" id="recent-withdrawals">0.00</h4>
                        <small class="text-muted">Last 30 days</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card mb-4">
                    <div class="card-body">
                        <i class="fas fa-exchange-alt text-info fs-3 mb-3"></i>
                        <h6 class="card-subtitle mb-2 text-muted">Total Transactions</h6>
                        <h4 class="card-title" id="total-transactions">0</h4>
                        <small class="text-muted">All time</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Balances Section -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-wallet"></i> Account Balances
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Account Owner</th>
                                        <th>Platform</th>
                                        <th>Currency</th>
                                        <th class="text-end">Balance</th>
                                    </tr>
                                </thead>
                                <tbody id="account-balances">
                                    <!-- Account balances will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction History Section -->
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history"></i> Transaction History
                        </h5>
                        <div class="d-flex align-items-center">
                            <select id="timeFrameSelect" class="form-select form-select-sm me-2">
                                <option value="6">Last 6 Months</option>
                                <option value="12">Last 12 Months</option>
                                <option value="24">Last 24 Months</option>
                                <option value="thisYear">This Year</option>
                                <option value="lastYear">Last Year</option>
                                <option value="custom">Custom Range</option>
                            </select>
                            <div id="customDateRange" style="display: none;">
                                <input type="date" id="startDate" class="form-control form-control-sm me-2">
                                <input type="date" id="endDate" class="form-control form-control-sm">
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 300px;">
                            <canvas id="transactionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list"></i> Recent Activity
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="recent-transactions-table">
                                    <!-- Recent transactions will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <a href="transaction_history.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="custom/js/dashboard.js"></script>

<?php require_once 'includes/footer.php'; ?>