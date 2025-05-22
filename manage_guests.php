<?php
require_once 'php_action/core.php';
require_once 'includes/header.php';

// Initialize permission flags for guest management
$permissions = array(
    'core' => array(
        'view' => hasPermission('guest.view') || hasPermission('view_guests'),
        'create' => hasPermission('guest.create') || hasPermission('guest_create'),
        'edit' => hasPermission('guest.edit') || hasPermission('guest_edit'),
        'delete' => hasPermission('guest.delete') || hasPermission('guest_delete'),
        'manage' => hasPermission('guest.manage') || hasPermission('manage_guests')
    ),
    'access' => array(
        'view' => hasPermission('guest.access.view'),
        'manage' => hasPermission('guest.access.manage'),
        'assign' => hasPermission('guest.access.assign')
    )
);

// Check if user has permission to access this page
if (!$permissions['core']['view'] && !$permissions['core']['manage'] && !hasPermission('admin.access')) {
    header('location: access_denied.php');
    exit();
}
?>

<!-- Add required CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap.min.css"/>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-theme@0.1.0-beta.10/dist/select2-bootstrap.min.css" rel="stylesheet" />

<!-- Add required JavaScript -->
<script type="text/javascript" src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>

<!-- Custom Styles -->
<style>
.select2-container {
    width: 100% !important;
}
.help-block {
    font-size: 12px;
    margin-top: 5px;
    color: #737373;
}
.label {
    display: inline-block;
    margin: 2px;
    padding: 5px 8px;
}
.datatable-access-level {
    white-space: normal !important;
    min-width: 150px;
}
.container {
    margin-top: 20px;
    margin-bottom: 20px;
}
.panel {
    margin-bottom: 20px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-shadow: 0 1px 1px rgba(0,0,0,.05);
}
.table-responsive {
    border: none;
    margin-bottom: 0;
}
#guestTable {
    margin-bottom: 0;
}
#guestTable thead th {
    background-color: #f5f5f5;
    border-bottom: 2px solid #ddd;
}
.page-heading {
    padding: 10px 0;
}
.page-heading button {
    margin-top: -5px;
}
</style>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <ol class="breadcrumb">
                <li><a href="dashboard.php">Home</a></li>
                <li class="active">Manage Guests</li>
            </ol>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <div class="page-heading">
                        <i class="fa fa-users"></i> Manage Guest Users
                        <?php if($permissions['core']['create'] || $permissions['core']['manage']): ?>
                        <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#addGuestModal">
                            <i class="fa fa-plus"></i> Add New Guest
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="remove-messages"></div>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" id="guestTable">
                            <thead>
                                <tr>
                                    <th>Guest ID</th>
                                    <th>Username</th>
                                    <th>Full Name</th>
                                    <th>Account</th>
                                    <th>Currency</th>
                                    <?php if($permissions['access']['view'] || $permissions['access']['manage']): ?>
                                    <th>Access Level</th>
                                    <?php endif; ?>
                                    <th>Status</th>
                                    <th>Expiry Date</th>
                                    <th>Last Login</th>
                                    <?php if($permissions['core']['edit'] || $permissions['core']['delete'] || $permissions['core']['manage']): ?>
                                    <th>Action</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Guest Modal -->
<div class="modal fade" id="addGuestModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="submitGuestForm" action="php_action/createGuest.php" method="POST">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-plus"></i> Add New Guest</h4>
            </div>
                <div class="modal-body">
                    <div id="add-guest-messages"></div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Username</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="username" name="username" placeholder="Guest Username" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Password</label>
                        <div class="col-sm-9">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Guest Password" required>
                            <span class="help-block">Password must be at least 8 characters long</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Full Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="fullName" name="fullName" placeholder="Guest Full Name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Account Name</label>
                        <div class="col-sm-9">
                            <select class="form-control select2" id="linkedAccount" name="linkedAccount[]" multiple required>
                                <?php
                                $sql = "SELECT * FROM accounts WHERE status = 1 ORDER BY account_owner ASC";
                                $result = $connect->query($sql);
                                if($result && $result->num_rows > 0) {
                                    while($row = $result->fetch_assoc()) {
                                        echo "<option value='".$row['id']."' data-currency='".$row['Currency']."'>".$row['account_owner']." - ".$row['account_platform']." (".$row['Currency'].")</option>";
                                    }
                                } else {
                                    echo "<option value=''>No accounts available</option>";
                                }
                                ?>
                            </select>
                            <span class="help-block">Hold Ctrl/Cmd to select multiple accounts. Each selected account will be linked to this guest user.</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Currency</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="currency" name="currency" readonly required>
                            <small class="help-block">Currency will be set based on selected account</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Access Level</label>
                        <div class="col-sm-9">
                            <?php if($permissions['access']['manage'] || $permissions['access']['assign']): ?>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="access[]" value="view_transactions"> View Transactions
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="access[]" value="export_transactions"> Export Transactions
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="access[]" value="view_reports"> View Reports
                                </label>
                            </div>
                            <?php else: ?>
                            <p class="text-muted">You do not have permission to manage access levels</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Expiry Date</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" id="expiryDate" name="expiryDate" required>
                            <span class="help-block">Account will be automatically deactivated after this date</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="createGuestBtn">
                        <i class="fa fa-save"></i> Create Guest
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Guest Modal -->
<div class="modal fade" id="editGuestModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="editGuestForm" action="php_action/editGuest.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><i class="fa fa-edit"></i> Edit Guest</h4>
                </div>
                <div class="modal-body">
                    <div class="edit-messages"></div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Username</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="editUsername" name="editUsername" placeholder="Guest Username" readonly>
                            <span class="help-block">Username cannot be changed</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Password</label>
                        <div class="col-sm-9">
                            <input type="password" class="form-control" id="editPassword" name="editPassword" placeholder="Leave blank to keep current password">
                            <small class="help-block">Leave blank to keep current password. New password must be at least 8 characters.</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Full Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="editFullName" name="editFullName" placeholder="Guest Full Name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Account Name</label>
                        <div class="col-sm-9">
                            <select class="form-control select2" id="editLinkedAccount" name="editLinkedAccount[]" multiple required>
                                <?php
                                $sql = "SELECT * FROM accounts WHERE status = 1 ORDER BY account_owner ASC";
                                $result = $connect->query($sql);
                                if($result && $result->num_rows > 0) {
                                    while($row = $result->fetch_assoc()) {
                                        echo "<option value='".$row['id']."' data-currency='".$row['Currency']."'>".$row['account_owner']." - ".$row['account_platform']." (".$row['Currency'].")</option>";
                                    }
                                } else {
                                    echo "<option value=''>No accounts available</option>";
                                }
                                ?>
                            </select>
                            <span class="help-block">Hold Ctrl/Cmd to select multiple accounts. Each selected account will be linked to this guest user.</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Currency</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="editCurrency" name="editCurrency" readonly required>
                            <small class="help-block">Currency will be set based on selected account</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Access Level</label>
                        <div class="col-sm-9">
                            <?php if($permissions['access']['manage'] || $permissions['access']['assign']): ?>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="editAccess[]" value="view_transactions"> View Transactions
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="editAccess[]" value="export_transactions"> Export Transactions
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="editAccess[]" value="view_reports"> View Reports
                                </label>
                            </div>
                            <?php else: ?>
                            <p class="text-muted">You do not have permission to manage access levels</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Expiry Date</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" id="editExpiryDate" name="editExpiryDate" required>
                            <span class="help-block">Account will be automatically deactivated after this date</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Status</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="editStatus" name="editStatus" required>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="guestId" id="guestId">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="editGuestBtn">
                        <i class="fa fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Custom JS -->
<script type="text/javascript">
$(document).ready(function() {
    // Initialize Select2 with tags
    $('.select2').select2({
        placeholder: "Select accounts",
        allowClear: true,
        width: '100%',
        theme: 'bootstrap'
    });

    // Handle currency update on account selection
    $('#linkedAccount, #editLinkedAccount').on('change', function() {
        var selectedOptions = $(this).find('option:selected');
        var currencies = [];
        var accountNames = [];
        
        selectedOptions.each(function() {
            var currency = $(this).data('currency');
            if (currency && !currencies.includes(currency)) {
                currencies.push(currency);
            }
            accountNames.push($(this).text());
        });

        // Update currency field
        var currencyField = $(this).attr('id') === 'linkedAccount' ? '#currency' : '#editCurrency';
        $(currencyField).val(currencies.join(', ') || 'USD');
    });

    // Initialize DataTable
    var guestTable = $('#guestTable').DataTable({
        'ajax': {
            'url': 'php_action/fetchGuests.php',
            'type': 'GET'
        },
        'order': [],
        'pageLength': 10,
        'responsive': true,
        'autoWidth': false,
        'dom': '<"row"<"col-sm-6"l><"col-sm-6"f>>' +
               '<"row"<"col-sm-12"tr>>' +
               '<"row"<"col-sm-5"i><"col-sm-7"p>>',
        'language': {
            'search': 'Search:',
            'lengthMenu': '_MENU_ records per page',
            'info': 'Showing _START_ to _END_ of _TOTAL_ entries',
            'infoEmpty': 'Showing 0 to 0 of 0 entries',
            'infoFiltered': '(filtered from _MAX_ total entries)',
            'emptyTable': 'No guest users available',
            'zeroRecords': 'No matching guest users found'
        },
        'columns': [
            { data: 'guest_id' },
            { data: 'username' },
            { data: 'full_name' },
            { data: 'account_name' },
            { data: 'currency' },
            <?php if($permissions['access']['view'] || $permissions['access']['manage']): ?>
            { 
                data: 'access_level',
                className: 'datatable-access-level',
                render: function(data) {
                    try {
                        var access = JSON.parse(data || '[]');
                        if (access.length === 0) return '<span class="label label-default">No Access</span>';
                        
                        return access.map(function(level) {
                            var label = level.split('_').map(function(word) {
                                return word.charAt(0).toUpperCase() + word.slice(1);
                            }).join(' ');
                            
                            var labelClass = '';
                            switch(level) {
                                case 'view_transactions':
                                    labelClass = 'label-primary';
                                    break;
                                case 'export_transactions':
                                    labelClass = 'label-success';
                                    break;
                                case 'view_reports':
                                    labelClass = 'label-info';
                                    break;
                                default:
                                    labelClass = 'label-default';
                            }
                            
                            return '<span class="label ' + labelClass + '">' + label + '</span>';
                        }).join(' ');
                    } catch(e) {
                        console.error('Error parsing access levels:', e);
                        return '<span class="label label-danger">Error</span>';
                    }
                }
            },
            <?php endif; ?>
            { 
                data: 'status',
                render: function(data) {
                    return data == 1 ? 
                        '<span class="label label-success">Active</span>' : 
                        '<span class="label label-danger">Inactive</span>';
                }
            },
            { 
                data: 'expiry_date',
                render: function(data) {
                    return data ? new Date(data).toLocaleDateString() : '-';
                }
            },
            { 
                data: 'last_login',
                render: function(data) {
                    return data ? new Date(data).toLocaleString() : 'Never';
                }
            },
            <?php if($permissions['core']['edit'] || $permissions['core']['delete'] || $permissions['core']['manage']): ?>
            {
                data: null,
                orderable: false,
                className: 'text-center',
                render: function(data) {
                    var buttons = '';
                    <?php if($permissions['core']['edit'] || $permissions['core']['manage']): ?>
                    buttons += `
                        <button class="btn btn-warning btn-sm edit-guest" data-id="${data.guest_id}">
                            <i class="fa fa-edit"></i>
                        </button>`;
                    <?php endif; ?>
                    <?php if($permissions['core']['delete'] || $permissions['core']['manage']): ?>
                    buttons += `
                        <button class="btn btn-danger btn-sm delete-guest" data-id="${data.guest_id}">
                            <i class="fa fa-trash"></i>
                        </button>`;
                    <?php endif; ?>
                    return buttons;
                }
            }
            <?php endif; ?>
        ]
    });

    // Edit Guest
    $('#guestTable').on('click', '.edit-guest', function() {
        <?php if(!$permissions['core']['edit'] && !$permissions['core']['manage']): ?>
        $('.remove-messages').html('<div class="alert alert-danger">You do not have permission to edit guests</div>');
        return false;
        <?php endif; ?>

        var guestId = $(this).data('id');
        
        // Clear previous selections and messages
        $('#editLinkedAccount').val(null).trigger('change');
        $('.edit-messages').empty();
        
        $.ajax({
            url: 'php_action/getGuestInfo.php',
            type: 'POST',
            data: {guestId: guestId},
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#editUsername').val(response.data.username);
                    $('#editFullName').val(response.data.full_name);
                    
                    // Handle linked accounts
                    if(response.data.linked_account_id) {
                        try {
                            var accountIds = response.data.linked_account_id.split(',').map(function(id) {
                                return id.trim();
                            }).filter(Boolean);
                            
                            if(accountIds.length > 0) {
                                $('#editLinkedAccount').val(accountIds);
                                $('#editLinkedAccount').trigger('change');
                            }
                        } catch(e) {
                            console.error('Error setting linked accounts:', e);
                        }
                    }
                    
                    $('#editExpiryDate').val(response.data.expiry_date);
                    $('#editStatus').val(response.data.status);
                    $('#guestId').val(response.data.guest_id);
                    
                    // Set access levels from JSON array
                    try {
                        var access = JSON.parse(response.data.access_level || '[]');
                        $('input[name="editAccess[]"]').each(function() {
                            $(this).prop('checked', access.includes($(this).val()));
                        });
                    } catch(e) {
                        console.error('Error parsing access levels:', e);
                        // Fallback for old comma-separated format
                        var access = response.data.access_level ? response.data.access_level.split(',') : [];
                        $('input[name="editAccess[]"]').each(function() {
                            $(this).prop('checked', access.includes($(this).val()));
                        });
                    }
                    
                    $('#editGuestModal').modal('show');
                } else {
                    $('.remove-messages').html('<div class="alert alert-danger">'+response.messages+'</div>');
                }
            },
            error: function() {
                $('.remove-messages').html('<div class="alert alert-danger">Error occurred while fetching guest information</div>');
            }
        });
    });

    // Form Validation and Submission for Add Guest
    $('#submitGuestForm').on('submit', function(e) {
        e.preventDefault();
        
        <?php if(!$permissions['core']['create'] && !$permissions['core']['manage']): ?>
        $("#add-guest-messages").html('<div class="alert alert-danger">You do not have permission to create guests</div>');
        return false;
        <?php endif; ?>

        if($("#password").val().length < 8) {
            $("#add-guest-messages").html('<div class="alert alert-danger">Password must be at least 8 characters long</div>');
            return false;
        }

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#addGuestModal').modal('hide');
                    $('#submitGuestForm')[0].reset();
                    $('#linkedAccount').val(null).trigger('change');
                    guestTable.ajax.reload();
                    $('.remove-messages').html('<div class="alert alert-success">'+response.messages+'</div>');
                } else {
                    $("#add-guest-messages").html('<div class="alert alert-danger">'+response.messages+'</div>');
                }
            },
            error: function() {
                $("#add-guest-messages").html('<div class="alert alert-danger">Error occurred while processing the request</div>');
            }
        });
    });

    // Edit Guest Form Submission
    $('#editGuestForm').on('submit', function(e) {
        e.preventDefault();
        
        <?php if(!$permissions['core']['edit'] && !$permissions['core']['manage']): ?>
        $(".edit-messages").html('<div class="alert alert-danger">You do not have permission to edit guests</div>');
        return false;
        <?php endif; ?>

        // Password validation if provided
        if($("#editPassword").val() !== "" && $("#editPassword").val().length < 8) {
            $(".edit-messages").html('<div class="alert alert-danger">Password must be at least 8 characters long</div>');
            return false;
        }

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#editGuestModal').modal('hide');
                    guestTable.ajax.reload();
                    $('.remove-messages').html('<div class="alert alert-success">'+response.messages+'</div>');
                } else {
                    $(".edit-messages").html('<div class="alert alert-danger">'+response.messages+'</div>');
                }
            },
            error: function() {
                $(".edit-messages").html('<div class="alert alert-danger">Error occurred while processing the request</div>');
            }
        });
    });

    // Delete Guest
    $('#guestTable').on('click', '.delete-guest', function() {
        <?php if(!$permissions['core']['delete'] && !$permissions['core']['manage']): ?>
        $('.remove-messages').html('<div class="alert alert-danger">You do not have permission to delete guests</div>');
        return false;
        <?php endif; ?>

        var guestId = $(this).data('id');
        if(confirm('Are you sure you want to delete this guest?')) {
            $.ajax({
                url: 'php_action/deleteGuest.php',
                type: 'POST',
                data: {id: guestId},
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        guestTable.ajax.reload();
                        $('.remove-messages').html('<div class="alert alert-success">'+response.messages+'</div>');
                    } else {
                        $('.remove-messages').html('<div class="alert alert-danger">'+response.messages+'</div>');
                    }
                },
                error: function() {
                    $('.remove-messages').html('<div class="alert alert-danger">Error occurred while deleting guest</div>');
                }
            });
        }
    });

    // Clear messages and reset forms when modals are closed
    $('.modal').on('hidden.bs.modal', function () {
        $('.alert').remove();
        $(this).find('form')[0].reset();
        $('#linkedAccount, #editLinkedAccount').val(null).trigger('change');
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 