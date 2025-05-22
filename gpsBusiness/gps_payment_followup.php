<?php
require_once '../php_action/core.php';

// Initialize the database connection if not already done
if (!isset($connect)) {
    require_once '../php_action/db_connect.php';
}

require_once 'includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    header('location: ../index.php');
    exit();
}
?>

<style>
    /* Form Styles */
    .form-group {
        margin-bottom: 8px;
    }
    
    .form-control {
        font-size: 11px;
        height: 30px;
        padding: 5px 10px;
    }
    
    .control-label {
        font-size: 11px;
        padding-top: 5px;
    }
    
    .modal-body {
        padding: 15px;
    }
    
    .row {
        margin-bottom: 5px;
    }
    
    /* Image Preview */
    .payment-image-preview {
        max-width: 100%;
        max-height: 200px;
        margin-top: 10px;
    }
    
    /* Modal Size */
    .modal-dialog {
        width: 600px;
    }
    
    /* Button Styles */
    .btn {
        font-size: 11px;
        padding: 4px 8px;
    }
</style>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="fas fa-clock"></i> GPS Payment Follow-up
                    <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#addGpsPaymentFollowupModal">
                        <i class="fas fa-plus"></i> Add Payment Follow-up
                    </button>
                </div>
            </div>
            <div class="panel-body">
                <div class="remove-messages"></div>

                <table class="table table-hover table-striped table-bordered" id="gpsPaymentFollowupTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Paid By</th>
                            <th>Amount</th>
                            <th>Rate</th>
                            <th>Transfer To</th>
                            <th>Bank/Platform</th>
                            <th>Comment</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add GPS Payment Follow-up Modal -->
<div class="modal fade" id="addGpsPaymentFollowupModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="submitGpsPaymentFollowupForm" action="php_action/createGpsPaymentFollowup.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Add Payment Follow-up</h4>
                </div>
                <div class="modal-body">
                    <div id="add-payment-followup-messages"></div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Payment Date</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" id="payment_date" name="payment_date" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Paid By</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="paid_by" name="paid_by" placeholder="Enter payer name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Currency</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="currency" name="currency" required>
                                <option value="ETB">ETB</option>
                                <option value="USD">USD</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Amount</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="amount" name="amount" placeholder="Enter amount" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Rate</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="rate" name="rate" placeholder="Enter rate (optional)">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Transfer To</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="transfer_to" name="transfer_to" placeholder="Enter transfer recipient">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Bank/Platform</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="bank_platform_name" name="bank_platform_name" placeholder="Enter bank or platform name">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Comment</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="comment" name="comment" rows="3" placeholder="Enter comment"></textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Payment Image</label>
                        <div class="col-sm-9">
                            <input type="file" class="form-control" id="payment_image" name="payment_image" accept=".jpg,.jpeg,.png,.pdf">
                            <div id="payment_image_preview"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit GPS Payment Follow-up Modal -->
<div class="modal fade" id="editGpsPaymentFollowupModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="editGpsPaymentFollowupForm" action="php_action/editGpsPaymentFollowup.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-edit"></i> Edit Payment Follow-up</h4>
                </div>
                <div class="modal-body">
                    <div id="edit-payment-followup-messages"></div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Payment Date</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" id="edit_payment_date" name="payment_date" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Paid By</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="edit_paid_by" name="paid_by" placeholder="Enter payer name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Currency</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="edit_currency" name="currency" required>
                                <option value="ETB">ETB</option>
                                <option value="USD">USD</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Amount</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="edit_amount" name="amount" placeholder="Enter amount" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Rate</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="edit_rate" name="rate" placeholder="Enter rate (optional)">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Transfer To</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="edit_transfer_to" name="transfer_to" placeholder="Enter transfer recipient">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Bank/Platform</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="edit_bank_platform_name" name="bank_platform_name" placeholder="Enter bank or platform name">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Comment</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="edit_comment" name="comment" rows="3" placeholder="Enter comment"></textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Payment Image</label>
                        <div class="col-sm-9">
                            <input type="file" class="form-control" id="edit_payment_image" name="payment_image" accept=".jpg,.jpeg,.png,.pdf">
                            <div id="edit_payment_image_preview"></div>
                            <input type="hidden" name="old_image" id="old_image">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="id" id="edit_id">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#gpsPaymentFollowupTable')) {
        $('#gpsPaymentFollowupTable').DataTable().destroy();
    }

    // Initialize DataTable
    var paymentFollowupTable = $('#gpsPaymentFollowupTable').DataTable({
        'ajax': {
            'url': 'php_action/fetchGpsPaymentFollowup.php',
            'type': 'GET',
            'error': function(xhr, error, thrown) {
                console.error('DataTables error:', error);
                $('.remove-messages').html('<div class="alert alert-danger">'+
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                    '<strong><i class="fas fa-times"></i></strong> Could not load payment follow-up data. Please try refreshing the page.'+
                    '</div>');
            }
        },
        'order': [[0, 'desc']],
        'pageLength': 25,
        'responsive': true,
        'dom': 'Bfrtip',
        'buttons': ['copy', 'csv', 'excel', 'pdf', 'print'],
        'columnDefs': [
            {
                'targets': -1,
                'orderable': false,
                'searchable': false
            }
        ]
    });

    // Handle file input change for preview
    function handleImagePreview(input, previewDiv) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                if (input.files[0].type.startsWith('image/')) {
                    $(previewDiv).html('<img src="' + e.target.result + '" class="payment-image-preview">');
                } else {
                    $(previewDiv).html('<p>File selected: ' + input.files[0].name + '</p>');
                }
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    $('#payment_image').change(function() {
        handleImagePreview(this, '#payment_image_preview');
    });

    $('#edit_payment_image').change(function() {
        handleImagePreview(this, '#edit_payment_image_preview');
    });

    // Handle form submission for adding payment follow-up
    $('#submitGpsPaymentFollowupForm').submit(function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            success: function(response) {
                var data = JSON.parse(response);
                $('#add-payment-followup-messages').html('');
                
                if (data.success) {
                    $('#submitGpsPaymentFollowupForm')[0].reset();
                    $('#payment_image_preview').html('');
                    $('#addGpsPaymentFollowupModal').modal('hide');
                    
                    // Show success message and refresh table
                    $('.remove-messages').html('<div class="alert alert-success">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="fas fa-check"></i></strong> '+ data.messages.join(' ') +
                        '</div>');

                    paymentFollowupTable.ajax.reload();
                } else {
                    $('#add-payment-followup-messages').html('<div class="alert alert-danger">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="fas fa-times"></i></strong> '+ data.messages.join(' ') +
                        '</div>');
                }
            },
            cache: false,
            contentType: false,
            processData: false
        });
    });

    // Handle edit button click
    $(document).on('click', 'a[onclick^="editPaymentFollowup"]', function(e) {
        e.preventDefault();
        var id = $(this).attr('onclick').match(/\d+/)[0];
        
        $.ajax({
            url: 'php_action/fetchSelectedPaymentFollowup.php',
            type: 'GET',
            data: { id: id },
            success: function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    $('#edit_id').val(data.data.id);
                    $('#edit_payment_date').val(data.data.payment_date);
                    $('#edit_paid_by').val(data.data.paid_by);
                    $('#edit_currency').val(data.data.currency);
                    $('#edit_amount').val(data.data.amount);
                    $('#edit_rate').val(data.data.rate);
                    $('#edit_transfer_to').val(data.data.transfer_to);
                    $('#edit_bank_platform_name').val(data.data.bank_platform_name);
                    $('#edit_comment').val(data.data.comment);
                    $('#old_image').val(data.data.payment_image);
                    
                    if (data.data.payment_image) {
                        if (data.data.payment_image.match(/\.(jpg|jpeg|png)$/i)) {
                            $('#edit_payment_image_preview').html('<img src="../' + data.data.payment_image + '" class="payment-image-preview">');
                        } else {
                            $('#edit_payment_image_preview').html('<p>Current file: ' + data.data.payment_image + '</p>');
                        }
                    } else {
                        $('#edit_payment_image_preview').html('');
                    }
                    
                    $('#editGpsPaymentFollowupModal').modal('show');
                }
            }
        });
    });

    // Handle form submission for editing payment follow-up
    $('#editGpsPaymentFollowupForm').submit(function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            success: function(response) {
                var data = JSON.parse(response);
                $('#edit-payment-followup-messages').html('');
                
                if (data.success) {
                    $('#editGpsPaymentFollowupModal').modal('hide');
                    
                    // Show success message and refresh table
                    $('.remove-messages').html('<div class="alert alert-success">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="fas fa-check"></i></strong> '+ data.messages.join(' ') +
                        '</div>');

                    paymentFollowupTable.ajax.reload();
                } else {
                    $('#edit-payment-followup-messages').html('<div class="alert alert-danger">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="fas fa-times"></i></strong> '+ data.messages.join(' ') +
                        '</div>');
                }
            },
            cache: false,
            contentType: false,
            processData: false
        });
    });

    // Handle delete button click
    $(document).on('click', 'a[onclick^="deletePaymentFollowup"]', function(e) {
        e.preventDefault();
        var id = $(this).attr('onclick').match(/\d+/)[0];
        
        if (confirm('Are you sure you want to delete this payment follow-up?')) {
            $.ajax({
                url: 'php_action/deleteGpsPaymentFollowup.php',
                type: 'POST',
                data: { id: id },
                success: function(response) {
                    var data = JSON.parse(response);
                    
                    if (data.success) {
                        // Show success message and refresh table
                        $('.remove-messages').html('<div class="alert alert-success">'+
                            '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                            '<strong><i class="fas fa-check"></i></strong> '+ data.messages.join(' ') +
                            '</div>');

                        paymentFollowupTable.ajax.reload();
                    } else {
                        $('.remove-messages').html('<div class="alert alert-danger">'+
                            '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                            '<strong><i class="fas fa-times"></i></strong> '+ data.messages.join(' ') +
                            '</div>');
                    }
                }
            });
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 