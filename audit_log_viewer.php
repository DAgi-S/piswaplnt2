<?php
require_once 'php_action/core.php';
require_once 'php_action/classes/ConfigurationManager.php';

// Check permissions
if (!isset($_SESSION['userId']) || !isset($_SESSION['role_id'])) {
    header('Location: login.php');
    exit();
}

// Check if user has permission to view audit logs
$permissions = new EnhancedPermissions();
if (!$permissions->checkPermission($_SESSION['userId'], 'view_audit_logs', 'system')) {
    header('Location: dashboard.php');
    exit();
}

// Get filter parameters
$filters = [
    'user_id' => $_GET['user_id'] ?? null,
    'module' => $_GET['module'] ?? null,
    'start_date' => $_GET['start_date'] ?? null,
    'end_date' => $_GET['end_date'] ?? null,
    'limit' => $_GET['limit'] ?? 50,
    'page' => $_GET['page'] ?? 1
];

// Calculate offset for pagination
$offset = ($filters['page'] - 1) * $filters['limit'];

// Get audit logs
$logger = new AuditLogger();
$result = $logger->getLogs(array_merge($filters, ['offset' => $offset]));
$logs = $result['success'] ? $result['logs'] : [];

// Get total count for pagination
$totalResult = $logger->getLogs(array_merge($filters, ['count' => true]));
$totalLogs = $totalResult['success'] ? $totalResult['count'] : 0;
$totalPages = ceil($totalLogs / $filters['limit']);

// Get available modules for filter
$modules = array_unique(array_column($logs, 'module'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log Viewer</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <h2>Audit Log Viewer</h2>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form id="filterForm" class="row g-3">
                    <div class="col-md-3">
                        <label for="module" class="form-label">Module</label>
                        <select class="form-select" id="module" name="module">
                            <option value="">All Modules</option>
                            <?php foreach ($modules as $module): ?>
                                <option value="<?php echo htmlspecialchars($module); ?>" <?php echo $filters['module'] === $module ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($module); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($filters['start_date']); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($filters['end_date']); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="limit" class="form-label">Entries per page</label>
                        <select class="form-select" id="limit" name="limit">
                            <option value="25" <?php echo $filters['limit'] == 25 ? 'selected' : ''; ?>>25</option>
                            <option value="50" <?php echo $filters['limit'] == 50 ? 'selected' : ''; ?>>50</option>
                            <option value="100" <?php echo $filters['limit'] == 100 ? 'selected' : ''; ?>>100</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <button type="button" class="btn btn-secondary" onclick="resetFilters()">Reset</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Log Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>Module</th>
                                <th>Action</th>
                                <th>Details</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($log['created_at']); ?></td>
                                    <td><?php echo htmlspecialchars($log['username']); ?></td>
                                    <td><?php echo htmlspecialchars($log['module']); ?></td>
                                    <td><?php echo htmlspecialchars($log['action']); ?></td>
                                    <td>
                                        <?php
                                        $details = json_decode($log['details'], true);
                                        if (json_last_error() === JSON_ERROR_NONE) {
                                            echo '<pre>' . htmlspecialchars(json_encode($details, JSON_PRETTY_PRINT)) . '</pre>';
                                        } else {
                                            echo htmlspecialchars($log['details']);
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i == $filters['page'] ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_diff_key($filters, ['page' => ''])); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/audit-logger.js"></script>
    <script>
        function resetFilters() {
            document.getElementById('filterForm').reset();
            window.location.href = 'audit_log_viewer.php';
        }

        // Add event listener for form submission
        document.getElementById('filterForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const params = new URLSearchParams(formData);
            window.location.href = 'audit_log_viewer.php?' + params.toString();
        });
    </script>
</body>
</html> 