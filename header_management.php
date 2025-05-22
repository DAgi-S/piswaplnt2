<?php
require_once 'includes/header.php';
require_once 'php_action/db_connect.php';
require_once 'php_action/core.php';

// Validation Exception Class
class ValidationException extends Exception {}

// Check if user has permission to manage headers
if (!hasPermission('manage_headers')) {
    $_SESSION['error_message'] = "Access Denied: Insufficient permissions";
    header('Location: index.php');
    exit();
}

// Function to check specific header permissions
function checkHeaderPermission($headerType, $action) {
    global $connect;
    
    $roleId = $_SESSION['roleId'];
    $sql = "SELECT can_view, can_edit, can_delete 
            FROM header_permissions 
            WHERE role_id = ? AND header_type = ?";
            
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("is", $roleId, $headerType);
    $stmt->execute();
    $result = $stmt->get_result();
    $permissions = $result->fetch_assoc();
    
    switch($action) {
        case 'view':
            return $permissions['can_view'] == 1;
        case 'edit':
            return $permissions['can_edit'] == 1;
        case 'delete':
            return $permissions['can_delete'] == 1;
        default:
            return false;
    }
}

// Validate header data
function validateHeaderData($headerName, $headerType, $headerContent) {
    // Header name validation
    if (empty($headerName) || strlen($headerName) > 255) {
        throw new ValidationException('Header name must be between 1 and 255 characters');
    }
    
    // Header type validation
    $validTypes = ['main', 'production', 'guest', 'gps'];
    if (!in_array($headerType, $validTypes)) {
        throw new ValidationException('Invalid header type');
    }
    
    // Content validation
    if (empty($headerContent)) {
        throw new ValidationException('Header content cannot be empty');
    }
    
    return true;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            // CSRF Protection
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                throw new ValidationException('Invalid security token');
            }
            
            switch ($_POST['action']) {
                case 'create':
                    if (!hasPermission('create_headers')) {
                        throw new ValidationException('Permission denied: Cannot create headers');
                    }
                    createHeader();
                    break;
                    
                case 'update':
                    if (!hasPermission('edit_headers')) {
                        throw new ValidationException('Permission denied: Cannot edit headers');
                    }
                    updateHeader();
                    break;
                    
                case 'delete':
                    if (!hasPermission('delete_headers')) {
                        throw new ValidationException('Permission denied: Cannot delete headers');
                    }
                    deleteHeader();
                    break;
            }
        }
    } catch (ValidationException $e) {
        $_SESSION['error_message'] = $e->getMessage();
        header('Location: header_management.php');
        exit();
    }
}

// Function to create a new header
function createHeader() {
    global $connect;
    
    $headerName = trim($_POST['header_name']);
    $headerType = trim($_POST['header_type']);
    $headerContent = trim($_POST['header_content']);
    $status = (int)$_POST['status'];
    $createdBy = $_SESSION['userId'];
    
    try {
        // Validate input
        validateHeaderData($headerName, $headerType, $headerContent);
        
        // Check specific permissions
        if (!checkHeaderPermission($headerType, 'edit')) {
            throw new ValidationException("You don't have permission to create this type of header");
        }
        
        // Sanitize input
        $headerName = htmlspecialchars($headerName, ENT_QUOTES, 'UTF-8');
        $headerContent = htmlspecialchars($headerContent, ENT_QUOTES, 'UTF-8');
        
        $sql = "INSERT INTO headers (header_name, header_type, header_content, status, created_by) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("sssii", $headerName, $headerType, $headerContent, $status, $createdBy);
        
        if ($stmt->execute()) {
            // Log the action
            logAction('create_header', "Created header: $headerName");
            $_SESSION['success_message'] = "Header created successfully";
        } else {
            throw new ValidationException("Error creating header: " . $connect->error);
        }
    } catch (ValidationException $e) {
        throw $e;
    } catch (Exception $e) {
        throw new ValidationException("Unexpected error: " . $e->getMessage());
    }
}

// Function to update a header
function updateHeader() {
    global $connect;
    
    $headerId = (int)$_POST['header_id'];
    $headerName = trim($_POST['header_name']);
    $headerType = trim($_POST['header_type']);
    $headerContent = trim($_POST['header_content']);
    $status = (int)$_POST['status'];
    
    try {
        // Validate input
        validateHeaderData($headerName, $headerType, $headerContent);
        
        // Check specific permissions
        if (!checkHeaderPermission($headerType, 'edit')) {
            throw new ValidationException("You don't have permission to edit this type of header");
        }
        
        // Sanitize input
        $headerName = htmlspecialchars($headerName, ENT_QUOTES, 'UTF-8');
        $headerContent = htmlspecialchars($headerContent, ENT_QUOTES, 'UTF-8');
        
        // Get original header data for logging
        $originalSql = "SELECT * FROM headers WHERE header_id = ?";
        $stmt = $connect->prepare($originalSql);
        $stmt->bind_param("i", $headerId);
        $stmt->execute();
        $originalHeader = $stmt->get_result()->fetch_assoc();
        
        $sql = "UPDATE headers 
                SET header_name = ?, header_type = ?, header_content = ?, status = ? 
                WHERE header_id = ?";
        
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("sssii", $headerName, $headerType, $headerContent, $status, $headerId);
        
        if ($stmt->execute()) {
            // Log the action with changes
            $changes = [];
            if ($originalHeader['header_name'] !== $headerName) $changes[] = "name";
            if ($originalHeader['header_type'] !== $headerType) $changes[] = "type";
            if ($originalHeader['header_content'] !== $headerContent) $changes[] = "content";
            if ($originalHeader['status'] !== $status) $changes[] = "status";
            
            logAction('update_header', "Updated header: $headerName (Changed: " . implode(', ', $changes) . ")");
            $_SESSION['success_message'] = "Header updated successfully";
        } else {
            throw new ValidationException("Error updating header: " . $connect->error);
        }
    } catch (ValidationException $e) {
        throw $e;
    } catch (Exception $e) {
        throw new ValidationException("Unexpected error: " . $e->getMessage());
    }
}

// Function to delete a header
function deleteHeader() {
    global $connect;
    
    $headerId = (int)$_POST['header_id'];
    
    try {
        // Get header info before deletion
        $sql = "SELECT header_type, header_name FROM headers WHERE header_id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("i", $headerId);
        $stmt->execute();
        $header = $stmt->get_result()->fetch_assoc();
        
        // Check specific permissions
        if (!checkHeaderPermission($header['header_type'], 'delete')) {
            throw new ValidationException("You don't have permission to delete this type of header");
        }
        
        $sql = "DELETE FROM headers WHERE header_id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("i", $headerId);
        
        if ($stmt->execute()) {
            // Log the action
            logAction('delete_header', "Deleted header: {$header['header_name']}");
            $_SESSION['success_message'] = "Header deleted successfully";
        } else {
            throw new ValidationException("Error deleting header: " . $connect->error);
        }
    } catch (ValidationException $e) {
        throw $e;
    } catch (Exception $e) {
        throw new ValidationException("Unexpected error: " . $e->getMessage());
    }
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch all headers with permission check
$headers = [];
$sql = "SELECT h.*, u.username as created_by_name 
        FROM headers h 
        LEFT JOIN users u ON h.created_by = u.user_id 
        ORDER BY h.created_at DESC";
$result = $connect->query($sql);

while ($header = $result->fetch_assoc()) {
    if (checkHeaderPermission($header['header_type'], 'view')) {
        $headers[] = $header;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Header Management</title>
    <style>
        .header-management {
            margin-top: 20px;
        }
        
        .header-card {
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header-actions {
            margin-top: 10px;
        }
        
        .header-preview {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .validation-error {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        
        .permission-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            margin-right: 5px;
            background-color: #e9ecef;
        }
        
        .permission-badge.active {
            background-color: #28a745;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container header-management">
        <div class="row mb-4">
            <div class="col-md-6">
                <h2>Header Management</h2>
            </div>
            <div class="col-md-6 text-right">
                <?php if (hasPermission('create_headers')): ?>
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addHeaderModal">
                    <i class="glyphicon glyphicon-plus"></i> Add New Header
                </button>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?php 
            echo $_SESSION['success_message'];
            unset($_SESSION['success_message']);
            ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?php 
            echo $_SESSION['error_message'];
            unset($_SESSION['error_message']);
            ?>
        </div>
        <?php endif; ?>
        
        <!-- Headers List -->
        <div class="row">
            <?php foreach ($headers as $header): ?>
            <div class="col-md-6">
                <div class="header-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4><?php echo htmlspecialchars($header['header_name']); ?></h4>
                        <div>
                            <?php if (checkHeaderPermission($header['header_type'], 'view')): ?>
                            <span class="permission-badge active">View</span>
                            <?php endif; ?>
                            <?php if (checkHeaderPermission($header['header_type'], 'edit')): ?>
                            <span class="permission-badge active">Edit</span>
                            <?php endif; ?>
                            <?php if (checkHeaderPermission($header['header_type'], 'delete')): ?>
                            <span class="permission-badge active">Delete</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <p><strong>Type:</strong> <?php echo htmlspecialchars($header['header_type']); ?></p>
                    <p><strong>Status:</strong> 
                        <span class="label label-<?php echo $header['status'] ? 'success' : 'danger'; ?>">
                            <?php echo $header['status'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </p>
                    <p><strong>Created by:</strong> <?php echo htmlspecialchars($header['created_by_name']); ?></p>
                    <p><strong>Created at:</strong> <?php echo date('Y-m-d H:i', strtotime($header['created_at'])); ?></p>
                    
                    <div class="header-preview">
                        <strong>Preview:</strong>
                        <div><?php echo htmlspecialchars($header['header_content']); ?></div>
                    </div>
                    
                    <div class="header-actions">
                        <?php if (checkHeaderPermission($header['header_type'], 'edit')): ?>
                        <button type="button" class="btn btn-warning btn-sm edit-header" 
                                data-toggle="modal" data-target="#editHeaderModal" 
                                data-header='<?php echo json_encode($header); ?>'>
                            <i class="glyphicon glyphicon-edit"></i> Edit
                        </button>
                        <?php endif; ?>
                        
                        <?php if (checkHeaderPermission($header['header_type'], 'delete')): ?>
                        <button type="button" class="btn btn-danger btn-sm delete-header" 
                                data-header-id="<?php echo $header['header_id']; ?>"
                                data-header-name="<?php echo htmlspecialchars($header['header_name']); ?>">
                            <i class="glyphicon glyphicon-trash"></i> Delete
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Add Header Modal -->
        <div class="modal fade" id="addHeaderModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form action="header_management.php" method="POST" class="header-form" novalidate>
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                            <h4 class="modal-title">Add New Header</h4>
                        </div>
                        
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="header_name">Header Name</label>
                                <input type="text" class="form-control" id="header_name" name="header_name" required>
                                <div class="validation-error"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="header_type">Header Type</label>
                                <select class="form-control" id="header_type" name="header_type" required>
                                    <option value="">Select Type</option>
                                    <?php if (checkHeaderPermission('main', 'edit')): ?>
                                    <option value="main">Main System Header</option>
                                    <?php endif; ?>
                                    <?php if (checkHeaderPermission('production', 'edit')): ?>
                                    <option value="production">Production Module Header</option>
                                    <?php endif; ?>
                                    <?php if (checkHeaderPermission('guest', 'edit')): ?>
                                    <option value="guest">Guest Portal Header</option>
                                    <?php endif; ?>
                                    <?php if (checkHeaderPermission('gps', 'edit')): ?>
                                    <option value="gps">GPS Business Header</option>
                                    <?php endif; ?>
                                </select>
                                <div class="validation-error"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="header_content">Header Content</label>
                                <textarea class="form-control" id="header_content" name="header_content" rows="10" required></textarea>
                                <div class="validation-error"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save Header</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Edit Header Modal -->
        <div class="modal fade" id="editHeaderModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form action="header_management.php" method="POST" class="header-form" novalidate>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="header_id" id="edit_header_id">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                            <h4 class="modal-title">Edit Header</h4>
                        </div>
                        
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="edit_header_name">Header Name</label>
                                <input type="text" class="form-control" id="edit_header_name" name="header_name" required>
                                <div class="validation-error"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_header_type">Header Type</label>
                                <select class="form-control" id="edit_header_type" name="header_type" required>
                                    <option value="">Select Type</option>
                                    <?php if (checkHeaderPermission('main', 'edit')): ?>
                                    <option value="main">Main System Header</option>
                                    <?php endif; ?>
                                    <?php if (checkHeaderPermission('production', 'edit')): ?>
                                    <option value="production">Production Module Header</option>
                                    <?php endif; ?>
                                    <?php if (checkHeaderPermission('guest', 'edit')): ?>
                                    <option value="guest">Guest Portal Header</option>
                                    <?php endif; ?>
                                    <?php if (checkHeaderPermission('gps', 'edit')): ?>
                                    <option value="gps">GPS Business Header</option>
                                    <?php endif; ?>
                                </select>
                                <div class="validation-error"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_header_content">Header Content</label>
                                <textarea class="form-control" id="edit_header_content" name="header_content" rows="10" required></textarea>
                                <div class="validation-error"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_status">Status</label>
                                <select class="form-control" id="edit_status" name="status" required>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Update Header</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Form validation
        $('.header-form').on('submit', function(e) {
            var form = $(this);
            var isValid = true;
            
            // Clear previous errors
            form.find('.validation-error').empty();
            
            // Validate header name
            var headerName = form.find('[name="header_name"]').val().trim();
            if (!headerName || headerName.length > 255) {
                form.find('[name="header_name"]').siblings('.validation-error')
                    .text('Header name must be between 1 and 255 characters');
                isValid = false;
            }
            
            // Validate header type
            var headerType = form.find('[name="header_type"]').val();
            if (!headerType) {
                form.find('[name="header_type"]').siblings('.validation-error')
                    .text('Please select a header type');
                isValid = false;
            }
            
            // Validate content
            var content = form.find('[name="header_content"]').val().trim();
            if (!content) {
                form.find('[name="header_content"]').siblings('.validation-error')
                    .text('Header content cannot be empty');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
        
        // Edit Header
        $('.edit-header').on('click', function() {
            var header = $(this).data('header');
            $('#edit_header_id').val(header.header_id);
            $('#edit_header_name').val(header.header_name);
            $('#edit_header_type').val(header.header_type);
            $('#edit_header_content').val(header.header_content);
            $('#edit_status').val(header.status);
        });
        
        // Delete Header
        $('.delete-header').on('click', function() {
            var headerId = $(this).data('header-id');
            var headerName = $(this).data('header-name');
            
            Swal.fire({
                title: 'Delete Header?',
                text: `Are you sure you want to delete "${headerName}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'header_management.php',
                        method: 'POST',
                        data: {
                            action: 'delete',
                            header_id: headerId,
                            csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
                        },
                        success: function(response) {
                            location.reload();
                        },
                        error: function(xhr, status, error) {
                            Swal.fire(
                                'Error!',
                                'Failed to delete header: ' + error,
                                'error'
                            );
                        }
                    });
                }
            });
        });
        
        // Preview content changes
        $('[name="header_content"]').on('input', function() {
            var preview = $(this).closest('.modal-content').find('.header-preview');
            if (preview.length === 0) {
                preview = $('<div class="header-preview mt-3"><strong>Preview:</strong><div></div></div>');
                $(this).after(preview);
            }
            preview.find('div').text($(this).val());
        });
    });
    </script>
</body>
</html> 