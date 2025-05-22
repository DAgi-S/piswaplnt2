<?php
require_once 'php_action/core.php';
require_once 'php_action/classes/ConfigurationManager.php';

// Check permissions
if (!isset($_SESSION['userId']) || !isset($_SESSION['role_id'])) {
    header('Location: login.php');
    exit();
}

$config = ConfigurationManager::getInstance();
$backupPath = $config->get('backup_path') . '/encryption_keys';
$backups = [];

if (file_exists($backupPath)) {
    $files = glob($backupPath . '/keys_backup_*.enc');
    foreach ($files as $file) {
        $timestamp = substr(basename($file), 12, 19);
        $recoveryFile = $backupPath . '/recovery_info_' . $timestamp . '.txt';
        $backups[] = [
            'file' => basename($file),
            'timestamp' => str_replace('_', ' ', $timestamp),
            'has_recovery' => file_exists($recoveryFile)
        ];
    }
    // Sort by timestamp descending
    usort($backups, function($a, $b) {
        return strtotime(str_replace('_', ' ', $b['timestamp'])) - strtotime(str_replace('_', ' ', $a['timestamp']));
    });
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Encryption Key Backup Management</title>
    <?php include('includes/header.php'); ?>
    <style>
        .backup-card {
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .backup-actions {
            display: flex;
            gap: 10px;
        }
        .status-badge {
            font-size: 0.8em;
            padding: 3px 8px;
            border-radius: 12px;
            background: #e9ecef;
        }
        .status-badge.success {
            background: #d4edda;
            color: #155724;
        }
        .status-badge.warning {
            background: #fff3cd;
            color: #856404;
        }
    </style>
</head>
<body>
    <?php include('includes/navbar.php'); ?>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2>Encryption Key Backup Management</h2>
                <hr>
                
                <!-- Create Backup Button -->
                <div class="mb-4">
                    <button id="createBackup" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create New Backup
                    </button>
                </div>

                <!-- Backups List -->
                <div class="row" id="backupsList">
                    <?php if (empty($backups)): ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                No backups found. Click "Create New Backup" to create your first backup.
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($backups as $backup): ?>
                            <div class="col-md-6">
                                <div class="card backup-card">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            Backup <?php echo $backup['timestamp']; ?>
                                            <?php if ($backup['has_recovery']): ?>
                                                <span class="status-badge success">Recovery Info Available</span>
                                            <?php else: ?>
                                                <span class="status-badge warning">No Recovery Info</span>
                                            <?php endif; ?>
                                        </h5>
                                        <p class="card-text">
                                            Filename: <?php echo htmlspecialchars($backup['file']); ?>
                                        </p>
                                        <div class="backup-actions">
                                            <?php if ($backup['has_recovery']): ?>
                                                <button class="btn btn-sm btn-info download-recovery" 
                                                        data-timestamp="<?php echo $backup['timestamp']; ?>">
                                                    <i class="fas fa-download"></i> Download Recovery Info
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-warning restore-backup" 
                                                    data-file="<?php echo htmlspecialchars($backup['file']); ?>">
                                                <i class="fas fa-undo"></i> Restore
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Restore Modal -->
    <div class="modal fade" id="restoreModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Restore Backup</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="restoreForm">
                        <input type="hidden" id="backupFile" name="backup_file">
                        <div class="form-group">
                            <label for="backupPassword">Backup Password</label>
                            <input type="password" class="form-control" id="backupPassword" 
                                   name="backup_password" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" id="confirmRestore">Restore</button>
                </div>
            </div>
        </div>
    </div>

    <?php include('includes/footer.php'); ?>
    
    <script>
        $(document).ready(function() {
            // Create backup
            $('#createBackup').click(function() {
                $.ajax({
                    url: 'php_action/backup_encryption_keys.php',
                    method: 'POST',
                    data: { action: 'backup' },
                    success: function(response) {
                        if (response.success) {
                            alert('Backup created successfully. Please download the recovery information.');
                            location.reload();
                        } else {
                            alert('Backup failed: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Failed to create backup. Please try again.');
                    }
                });
            });

            // Download recovery info
            $('.download-recovery').click(function() {
                var timestamp = $(this).data('timestamp');
                window.location.href = 'php_action/download_recovery.php?timestamp=' + encodeURIComponent(timestamp);
            });

            // Restore backup
            $('.restore-backup').click(function() {
                var backupFile = $(this).data('file');
                $('#backupFile').val(backupFile);
                $('#restoreModal').modal('show');
            });

            $('#confirmRestore').click(function() {
                var formData = $('#restoreForm').serialize();
                formData += '&action=restore';

                $.ajax({
                    url: 'php_action/backup_encryption_keys.php',
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        $('#restoreModal').modal('hide');
                        if (response.success) {
                            alert('Backup restored successfully.');
                            location.reload();
                        } else {
                            alert('Restore failed: ' + response.message);
                        }
                    },
                    error: function() {
                        $('#restoreModal').modal('hide');
                        alert('Failed to restore backup. Please try again.');
                    }
                });
            });
        });
    </script>
</body>
</html> 