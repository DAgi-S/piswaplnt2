<?php
require_once 'core.php';

// Check if user is logged in
requireLogin();

// Get current user data
$currentUser = getCurrentUser();
if (!$currentUser) {
    header('location: logout.php');
    exit();
}

// Get all accounts linked to this guest user
$sql = "SELECT DISTINCT
    gu.id as guest_user_id, 
    gu.guest_id, 
    gu.full_name,
    a.id as account_id,
    a.account_owner,
    a.account_platform,
    a.Currency as currency,
    a.number_of_transactions,
    a.status
FROM guest_users gu 
INNER JOIN guest_account_links gal ON gu.id = gal.guest_id
INNER JOIN accounts a ON gal.account_id = a.id 
WHERE gu.id = ? AND a.status = 1";

$stmt = $connect->prepare($sql);
$stmt->bind_param("i", $_SESSION['guest_id']);
$stmt->execute();
$result = $stmt->get_result();

$guestAccounts = array();
while($row = $result->fetch_assoc()) {
    $guestAccounts[] = $row;
}

// Debug information
error_log("Guest Accounts: " . print_r($guestAccounts, true));
error_log("Active Account: " . ($_SESSION['active_guest_account'] ?? 'Not Set'));

// Initialize guestData with current user data
$guestData = array(
    'guest_user_id' => $currentUser['id'],
    'guest_id' => $currentUser['guest_id'],
    'full_name' => $currentUser['full_name'],
    'account_id' => 0,
    'account_owner' => '',
    'account_platform' => '',
    'currency' => 'ETB',
    'number_of_transactions' => 0,
    'status' => 0
);

// If we have accounts, set up the active account
if (!empty($guestAccounts)) {
    // If no active account is selected, use the first one
    if (!isset($_SESSION['active_guest_account'])) {
        $_SESSION['active_guest_account'] = $guestAccounts[0]['account_id'];
        error_log("Setting initial active account: " . $guestAccounts[0]['account_id']);
    }

    // Get current active account data
    foreach($guestAccounts as $account) {
        if ($account['account_id'] == $_SESSION['active_guest_account']) {
            $guestData = $account;
            break;
        }
    }
}

// Get flash message if any
$flashMessage = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="custom/css/custom.css" rel="stylesheet">
    <?php if (basename($_SERVER['PHP_SELF']) === 'dashboard.php'): ?>
    <link href="custom/css/dashboard.css" rel="stylesheet">
    <?php endif; ?>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Chart.js -->
    <?php if (basename($_SERVER['PHP_SELF']) === 'dashboard.php'): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php endif; ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php"><?php echo SITE_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'transaction_history.php' ? 'active' : ''; ?>" href="transaction_history.php">
                            <i class="fas fa-history"></i> Transactions
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo in_array(basename($_SERVER['PHP_SELF']), ['annual_report.php', 'audit_report.php']) ? 'active' : ''; ?>" href="#" id="reportsDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-chart-line"></i> Reports
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) === 'annual_report.php' ? 'active' : ''; ?>" href="annual_report.php">
                                    <i class="fas fa-calendar-alt"></i> Annual Report
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) === 'audit_report.php' ? 'active' : ''; ?>" href="audit_report.php">
                                    <i class="fas fa-search-dollar"></i> Audit Report
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (!empty($guestAccounts)): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="accountDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-exchange-alt"></i> 
                            <?php 
                            $currentAccount = array_filter($guestAccounts, function($acc) {
                                return $acc['account_id'] == ($_SESSION['active_guest_account'] ?? 0);
                            });
                            $currentAccount = reset($currentAccount);
                            echo htmlspecialchars($currentAccount['account_platform'] ?? 'Switch Account');
                            ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php foreach ($guestAccounts as $account): ?>
                            <li>
                                <a class="dropdown-item <?php echo ($_SESSION['active_guest_account'] ?? '') == $account['account_id'] ? 'active' : ''; ?>" 
                                   href="php_action/switchAccount.php?account_id=<?php echo $account['account_id']; ?>">
                                    <i class="fas fa-university me-2"></i>
                                    <?php echo htmlspecialchars($account['account_platform']); ?>
                                    <small class="text-muted d-block">
                                        <?php echo htmlspecialchars($account['account_owner']); ?> - 
                                        <?php echo htmlspecialchars($account['currency']); ?>
                                    </small>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($currentUser['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="profile.php">
                                    <i class="fas fa-user-circle"></i> Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="change_password.php">
                                    <i class="fas fa-key"></i> Change Password
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php if ($flashMessage): ?>
    <div class="container">
        <div class="alert alert-<?php echo $flashMessage['type']; ?> alert-dismissible fade show">
            <?php echo $flashMessage['text']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Content Container -->
    <div class="container"><?php // This will be closed in footer.php ?></div>
</body>
</html> 