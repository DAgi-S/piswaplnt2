var manageDigitalSwapTable;

$(document).ready(function() {
    // Initialize DataTable
    manageDigitalSwapTable = $('#manageDigitalSwapTable').DataTable({
        'ajax': {
            'url': getBaseUrl() + '/php_action/fetchDigitalSwap.php',
            'type': 'GET',
            'error': function(xhr, error, thrown) {
                console.error('DataTable AJAX Error:', error);
                console.error('Response:', xhr.responseText);
                $('.remove-messages').html(
                    '<div class="alert alert-danger">' +
                    '<strong>Error!</strong> Failed to load digital swaps.<br>' +
                    'Error: ' + error + '<br>' +
                    'Response: ' + xhr.responseText +
                    '</div>'
                );
            }
        },
        'order': [],
        'processing': true,
        'serverSide': false,
        'bDestroy': true,
        'pageLength': 10,
        'language': {
            processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span>'
        },
        'columns': [
            { 'data': 'transaction_date' },
            { 'data': 'type' },
            { 'data': 'name' },
            { 'data': 'platform' },
            { 
                'data': 'amount',
                'render': function(data, type, row) {
                    if (type === 'display') {
                        return parseFloat(data).toFixed(2);
                    }
                    return data;
                }
            },
            { 
                'data': 'image',
                'render': function(data, type, row) {
                    if (type === 'display') {
                        if (row.image_url) {
                            return '<a href="javascript:void(0)" class="view-image" data-image="' + row.image_url + '">View Image</a>';
                        }
                        return 'No Image';
                    }
                    return data;
                }
            },
            { 'data': 'comment' },
            {
                'data': 'id',
                'orderable': false,
                'className': 'text-center',
                'render': function(data, type, row) {
                    return '<button class="btn btn-primary btn-sm" onclick="viewDigitalSwap(' + data + ')">' +
                           '<i class="glyphicon glyphicon-eye-open"></i>' +
                           '</button> ' +
                           '<button class="btn btn-warning btn-sm" onclick="editDigitalSwap(' + data + ')">' +
                           '<i class="glyphicon glyphicon-edit"></i>' +
                           '</button> ' +
                           '<button class="btn btn-danger btn-sm" onclick="removeDigitalSwap(' + data + ')">' +
                           '<i class="glyphicon glyphicon-trash"></i>' +
                           '</button>';
                }
            }
        ],
        'drawCallback': function(settings) {
            // Initialize dropdowns after table draw
            $('.dropdown-toggle').dropdown();
            
            // Handle dropdown positioning for table-specific dropdowns only
            $('.action-dropdown').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                var $dropdown = $(this);
                var $menu = $dropdown.next('.dropdown-menu');
                var $btnGroup = $dropdown.closest('.btn-group');
                
                // Only handle table-specific dropdowns
                if (!$btnGroup.length) {
                    return;
                }
                
                // Reset any previously set styles
                $menu.css({
                    'position': 'absolute',
                    'transform': 'none',
                    'top': '100%',
                    'left': 'auto',
                    'right': '0'
                });
                
                // Get positions and dimensions
                var windowHeight = $(window).height();
                var windowWidth = $(window).width();
                var menuHeight = $menu.outerHeight();
                var menuWidth = $menu.outerWidth();
                var btnGroupOffset = $btnGroup.offset();
                var btnGroupHeight = $btnGroup.outerHeight();
                var btnGroupWidth = $btnGroup.outerWidth();
                
                // Check if dropdown would go off screen vertically
                var spaceBelow = windowHeight - (btnGroupOffset.top + btnGroupHeight);
                if (spaceBelow < menuHeight && btnGroupOffset.top > menuHeight) {
                    $menu.css({
                        'top': 'auto',
                        'bottom': '100%'
                    });
                }
                
                // Check if dropdown would go off screen horizontally
                var spaceRight = windowWidth - btnGroupOffset.left;
                if (spaceRight < menuWidth) {
                    $menu.css({
                        'right': '0',
                        'left': 'auto'
                    });
                }
                
                // Toggle the dropdown
                $menu.toggle();
            });
        },
        'createdRow': function(row, data, dataIndex) {
            // Add hover effect class
            $(row).addClass('table-row-hover');
        }
    });

    // Close dropdowns when clicking outside - only for table-specific dropdowns
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.btn-group').length) {
            $('.action-dropdown').next('.dropdown-menu').hide();
        }
    });

    // Handle window resize - only for table-specific dropdowns
    $(window).on('resize', function() {
        $('.action-dropdown').next('.dropdown-menu:visible').each(function() {
            var $menu = $(this);
            var $btnGroup = $menu.closest('.btn-group');
            
            // Hide menu if window is resized
            $menu.hide();
        });
    });

    // Function to edit digital swap
    window.editDigitalSwap = function(id) {
        $('#editDigitalSwapForm')[0].reset();
        $('.form-group').removeClass('has-error');
        $('.text-danger').remove();
        $('#edit-digitalswap-messages').html('');
        
        $.ajax({
            url: getBaseUrl() + '/php_action/fetchSelectedDigitalSwap.php',
            type: 'post',
            data: {id: id},
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#editDigitalSwapModal').modal('show');
                    $('#editDigitalSwapId').val(response.data.id);
                    $('#editTransactionDate').val(response.data.transaction_date);
                    $('#editType').val(response.data.type);
                    $('#editName').val(response.data.name);
                    $('#editAmount').val(response.data.amount);
                    $('#editComment').val(response.data.comment);
                    $('#editAccountId').val(response.data.account_id).trigger('change');
                } else {
                    $('#edit-digitalswap-messages').html(
                        '<div class="alert alert-danger">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        (response.messages || 'Failed to load digital swap details.') +
                        '</div>'
                    );
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                $('#edit-digitalswap-messages').html(
                    '<div class="alert alert-danger">' +
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                    'Failed to load digital swap details.<br>' +
                    'Status: ' + status + '<br>' +
                    'Error: ' + error + '<br>' +
                    'Response: ' + xhr.responseText +
                    '</div>'
                );
            }
        });
    };

    // Form submission handler
    $("#submitDigitalSwapForm").off('submit').on('submit', function(e) {
        e.preventDefault();
        
        // Disable the submit button to prevent double submission
        var $submitBtn = $("#createDigitalSwapBtn");
        if ($submitBtn.prop('disabled')) {
            return false;
        }
        
        // Show loading state
        $submitBtn
            .prop('disabled', true)
            .html('<i class="fa fa-spinner fa-spin"></i> Processing...');

        var formData = new FormData(this);

        $.ajax({
            url: getBaseUrl() + '/php_action/createDigitalSwap.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            cache: false,
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.success) {
                    // Reset form
                    $("#submitDigitalSwapForm")[0].reset();
                    $("#addDigitalSwapModal").modal('hide');
                    
                    // Clear Select2
                    $('#accountId').val(null).trigger('change');
                    
                    // Reload table with a fresh AJAX call
                    manageDigitalSwapTable.ajax.reload(null, false);
                    
                    // Show success message
                    $("#success-message").html(response.messages);
                    $("#success-alert").show().delay(5000).fadeOut();
                } else {
                    // Show error message
                    $("#add-digitalswap-messages").html(
                        '<div class="alert alert-danger">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> ' + 
                        (response.messages || 'An error occurred') +
                        (response.error_details ? '<br>Details: ' + response.error_details : '') +
                        '</div>'
                    );
                    
                    // If it's a duplicate entry, keep the modal open
                    if (response.messages && response.messages.includes('Duplicate entry')) {
                        setTimeout(function() {
                            $submitBtn
                                .prop('disabled', false)
                                .html('<i class="glyphicon glyphicon-ok-sign"></i> Save changes');
                        }, 1000);
                        return;
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                $("#add-digitalswap-messages").html(
                    '<div class="alert alert-danger">' +
                    '<strong>Error!</strong> Server error occurred.<br>' +
                    'Status: ' + status + '<br>' +
                    'Error: ' + error + '<br>' +
                    'Response: ' + xhr.responseText +
                    '</div>'
                );
            },
            complete: function() {
                // Reset button state after a short delay
                setTimeout(function() {
                    $submitBtn
                        .prop('disabled', false)
                        .html('<i class="glyphicon glyphicon-ok-sign"></i> Save changes');
                }, 1000);
            }
        });
    });

    // Initialize Select2 for Account dropdown
    if($.fn.select2) {
        $('#accountId').select2({
            width: '100%',
            placeholder: 'Select an Account'
        });
    }

    // Add this to show platform when account is selected
    $('#accountId').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var platform = selectedOption.parent('optgroup').attr('label');
        console.log('Selected account platform:', platform);
    });

    // View Digital Swap
    $(document).on('click', '.view-btn', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        console.log('View button clicked, ID:', id); // Debug log
        viewDigitalSwap(id);
    });

    // Edit Digital Swap
    $(document).on('click', '.editDigitalswapModalBtn', function(e) {
        e.preventDefault();
        $('#editDigitalSwapForm')[0].reset();
        $('.form-group').removeClass('has-error');
        $('.text-danger').remove();
        $('#edit-digitalswap-messages').html('');
        
        var digitalswap_id = $(this).data('id');
        var btn = $(this);
        
        // Disable the edit button while loading
        btn.prop('disabled', true);
        
        $.ajax({
            url: getBaseUrl() + '/php_action/fetchSelectedDigitalSwap.php',
            type: 'post',
            data: {id: digitalswap_id},
            dataType: 'json',
            success: function(response) {
                btn.prop('disabled', false);
                
                if (response.success) {
                    $('#editDigitalSwapId').val(response.data.id);
                    $('#editAccountId').val(response.data.account_id);
                    $('#editTransactionDate').val(response.data.transaction_date);
                    $('#editType').val(response.data.type);
                    $('#editName').val(response.data.name);
                    $('#editAmount').val(response.data.amount);
                    $('#editComment').val(response.data.comment || '');
                    
                    // Initialize Select2 for the edit form
                    if($.fn.select2) {
                        $('#editAccountId').select2({
                            width: '100%',
                            placeholder: 'Select an Account'
                        });
                    }
                    
                    // Handle image preview
                    if (response.data.image) {
                        $('#editImagePreview').attr('src', response.data.image).show();
                        $('#currentImage').val(response.data.image);
                        $('.remove-image-option').show();
                    } else {
                        $('#editImagePreview').hide();
                        $('#currentImage').val('');
                        $('.remove-image-option').hide();
                    }
                    
                    $('#editDigitalSwapModal').modal('show');
                } else {
                    $('#edit-digitalswap-messages').html('<div class="alert alert-danger">' + 
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' + 
                        response.messages + '</div>');
                }
            },
            error: function(xhr, status, error) {
                btn.prop('disabled', false);
                $('#edit-digitalswap-messages').html('<div class="alert alert-danger">' + 
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>' + 
                    'Failed to fetch digital swap details. Please try again.</div>');
                console.error('Error:', error);
                console.error('Status:', status);
                console.error('XHR:', xhr);
            }
        });
    });

    // Handle image preview for edit form
    $('#editImage').change(function() {
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#editImagePreview').attr('src', e.target.result).show();
                $('.remove-image-option').show();
            };
            reader.readAsDataURL(this.files[0]);
            $('#removeImage').prop('checked', false);
        }
    });

    // Handle remove image checkbox
    $('#removeImage').change(function() {
        if ($(this).is(':checked')) {
            $('#editImagePreview').hide();
            $('#editImage').val('');
        } else {
            var currentImage = $('#currentImage').val();
            if (currentImage) {
                $('#editImagePreview').attr('src', currentImage).show();
            }
        }
    });

    // Submit edit form
    $('#editDigitalSwapForm').off('submit').on('submit', function(e) {
        e.preventDefault();
        
        $('.form-group').removeClass('has-error');
        $('.text-danger').remove();
        $('#edit-digitalswap-messages').html('');
        
        var form = $(this);
        var submitBtn = form.find('button[type="submit"]');
        
        // Prevent double submission
        if (submitBtn.prop('disabled')) {
            return false;
        }
        
        // Show loading state
        submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Updating...');
        
        $.ajax({
            url: getBaseUrl() + '/php_action/updateDigitalSwap.php',
            type: 'POST',
            data: new FormData(this),
            contentType: false,
            cache: false,
            processData: false,
            success: function(response) {
                if (response.success) {
                    $('#edit-digitalswap-messages').html('<div class="alert alert-success">' + 
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' + 
                        response.messages + '</div>');
                    
                    // Reset form and close modal after short delay
                    setTimeout(function() {
                        $('#editDigitalSwapModal').modal('hide');
                        // Refresh the table
                        manageDigitalSwapTable.ajax.reload(null, false);
                    }, 1500);
                } else {
                    $('#edit-digitalswap-messages').html('<div class="alert alert-danger">' + 
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' + 
                        response.messages + '</div>');
                }
            },
            error: function(xhr, status, error) {
                $('#edit-digitalswap-messages').html('<div class="alert alert-danger">' + 
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>' + 
                    'Failed to update digital swap. Please try again.</div>');
                console.error('Error:', error);
                console.error('Status:', status);
                console.error('XHR:', xhr);
            },
            complete: function() {
                // Reset button state after short delay
                setTimeout(function() {
                    submitBtn.prop('disabled', false).html('Update Digital Swap');
                }, 1000);
            }
        });
    });

    // View image click handler
    $(document).on('click', '.view-image', function(e) {
        e.preventDefault();
        var imageUrl = $(this).data('image');
        $('#imagePreviewModal .modal-body').html('<img src="' + imageUrl + '" class="img-responsive">');
        $('#imagePreviewModal').modal('show');
    });

    // Add image error handling
    $('#previewImage').on('error', function() {
        $(this).attr('src', 'assets/images/no-image.png'); // Replace with your default image path
        $('#imagePreviewModal .modal-title').text('Image Not Found');
    });

    // Remove Digital Swap
    window.removeDigitalSwap = function(id) {
        if (!id) {
            console.error('Invalid digital swap ID');
            return;
        }

        $('#removeDigitalSwapModal').modal('show');
        
        $('#removeDigitalSwapBtn').off('click').on('click', function() {
            var $btn = $(this);
            $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Removing...');
            
            $.ajax({
                url: getBaseUrl() + '/php_action/removeDigitalSwap.php',
                type: 'POST',
                data: {id: id},
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        $('#removeDigitalSwapModal').modal('hide');
                        manageDigitalSwapTable.ajax.reload(null, false);
                        
                        $("#success-message").html(response.messages);
                        $("#success-alert").show().delay(5000).fadeOut();
                    } else {
                        $('.removeProductMessages').html(
                            '<div class="alert alert-danger">' +
                            '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                            (response.messages || 'Failed to remove digital swap.') +
                            '</div>'
                        );
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', xhr.responseText);
                    $('.removeProductMessages').html(
                        '<div class="alert alert-danger">' +
                        '<strong>Error!</strong> Failed to remove digital swap.<br>' +
                        'Status: ' + status + '<br>' +
                        'Error: ' + error + '<br>' +
                        'Response: ' + xhr.responseText +
                        '</div>'
                    );
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<i class="glyphicon glyphicon-trash"></i> Remove');
                }
            });
        });
    };
});

// Debug function to check table state
function debugTable() {
    console.log('Table data:', manageDigitalSwapTable.data().toArray());
    console.log('Table info:', manageDigitalSwapTable.page.info());
}

// Function to get base URL
function getBaseUrl() {
    var host = window.location.host;
    var protocol = window.location.protocol;
    
    // Check if we're on localhost
    if (host.includes('localhost')) {
        return protocol + '//' + host + '/pistocklnt1march';
    }
    
    // Production environment
    return protocol + '//' + host;
}

// View Digital Swap function
function viewDigitalSwap(id) {
    if (!id) {
        console.error('Invalid digital swap ID');
        return;
    }

    // Show loading state in the modal
    $('#viewDigitalSwapModal .modal-body').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-3x"></i></div>');
    $('#viewDigitalSwapModal').modal('show');

    // Log the URL being called for debugging
    var url = getBaseUrl() + '/php_action/fetchSelectedDigitalSwap.php';
    console.log('Calling URL:', url);
    console.log('Data being sent:', { id: id });

    $.ajax({
        url: url,
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        beforeSend: function() {
            console.log('Making AJAX request to:', url);
        },
        success: function(response) {
            console.log('View response:', response);
            
            if (response && response.success) {
                var data = response.data;
                
                // Format the data
                var formattedAmount = parseFloat(data.amount).toFixed(2);
                var formattedDate = data.transaction_date;
                
                // Build the table content
                var modalContent = '<div class="table-responsive"><table class="table table-bordered table-striped">';
                modalContent += '<tr><th style="width:30%">Transaction Date</th><td>' + formattedDate + '</td></tr>';
                modalContent += '<tr><th>Type</th><td>' + (data.type || 'N/A') + '</td></tr>';
                modalContent += '<tr><th>Name</th><td>' + (data.name || 'N/A') + '</td></tr>';
                modalContent += '<tr><th>Platform</th><td>' + (data.platform || 'N/A') + '</td></tr>';
                modalContent += '<tr><th>Amount</th><td>' + formattedAmount + '</td></tr>';
                
                if (data.image_url) {
                    modalContent += '<tr><th>Image</th><td>';
                    modalContent += '<a href="javascript:void(0)" class="view-image" data-image="' + data.image_url + '">';
                    modalContent += '<img src="' + data.image_url + '" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">';
                    modalContent += '</a></td></tr>';
                }
                
                modalContent += '<tr><th>Comment</th><td>' + (data.comment || 'No comment') + '</td></tr>';
                modalContent += '</table></div>';

                $('#viewDigitalSwapModal .modal-body').html(modalContent);

                // Initialize image preview click handler
                $('.view-image').off('click').on('click', function(e) {
                    e.preventDefault();
                    var imageUrl = $(this).data('image');
                    $('#imagePreviewModal .modal-body').html('<img src="' + imageUrl + '" class="img-responsive">');
                    $('#imagePreviewModal').modal('show');
                });
            } else {
                $('#viewDigitalSwapModal .modal-body').html(
                    '<div class="alert alert-danger">' +
                    '<strong>Error!</strong> ' + ((response && response.messages) || 'Failed to load digital swap details.') +
                    '</div>'
                );
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error Details:');
            console.error('Status:', status);
            console.error('Error:', error);
            console.error('Response Text:', xhr.responseText);
            console.error('Status Code:', xhr.status);
            console.error('Ready State:', xhr.readyState);
            
            var errorMessage = 'Failed to load digital swap details.<br>';
            if (xhr.status === 404) {
                errorMessage += 'The server endpoint could not be found. Please check the URL path.<br>';
            } else if (xhr.status === 500) {
                errorMessage += 'Internal server error occurred. Please check server logs.<br>';
            }
            
            $('#viewDigitalSwapModal .modal-body').html(
                '<div class="alert alert-danger">' +
                '<strong>Error!</strong><br>' +
                errorMessage +
                'Status: ' + status + '<br>' +
                'Error: ' + error + '<br>' +
                'Response: ' + (xhr.responseText || 'No response text') +
                '</div>'
            );
        }
    });
}

// Function to show full-size image in modal
function showFullImage(imageUrl) {
    $('#previewImage').attr('src', imageUrl);
    $('#imagePreviewModal').modal('show');
}

// Handle image error in preview modal
$('#previewImage').on('error', function() {
    $(this).attr('src', 'assets/images/no-image.png');
    $('#imagePreviewModal .modal-title').text('Image Not Found');
});

// Helper function to capitalize first letter
function ucfirst(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

// Add this to your page header
function loadSelect2() {
    if(typeof $.fn.select2 === 'undefined') {
        $('head').append('<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />');
        $.getScript('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', function() {
            initializeSelect2();
        });
    } else {
        initializeSelect2();
    }
}

function initializeSelect2() {
    $('#accountId').select2({
        width: '100%',
        placeholder: 'Select an Account'
    });
}

// Call this when document is ready
$(document).ready(function() {
    loadSelect2();
});

// Add this function at the top of your file
function showSuccessMessage(message) {
    $('#success-message').html(message);
    $('#success-alert').fadeIn('slow');
    setTimeout(function() {
        $('#success-alert').fadeOut('slow');
    }, 5000); // Hide after 5 seconds
}

// Update edit form submit handler
$('#editDigitalSwapForm').off('submit').on('submit', function(e) {
    e.preventDefault();
    
    // Disable submit button to prevent double submission
    var $submitBtn = $('#editDigitalSwapBtn');
    if ($submitBtn.prop('disabled')) {
        return false;
    }
    
    // Show loading state
    $submitBtn
        .prop('disabled', true)
        .html('<i class="glyphicon glyphicon-refresh glyphicon-refresh-animate"></i> Saving...');
    
    var formData = new FormData(this);
    
    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: formData,
        cache: false,
        contentType: false,
        processData: false,
        success: function(response) {
            if(response.success) {
                // Close the modal
                $('#editDigitalSwapModal').modal('hide');
                
                // Show success message popup
                $("#success-message").html(response.messages);
                $("#success-alert").show().delay(5000).fadeOut();
                
                // Refresh the table
                manageDigitalSwapTable.ajax.reload(null, false);
            } else {
                // Show error message
                $('#edit-digitalswap-messages').html(
                    '<div class="alert alert-danger">' +
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                    '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> ' + 
                    (response.messages || 'An error occurred') +
                    '</div>'
                );
                
                // If it's a validation error, keep the modal open
                if (response.messages) {
                    setTimeout(function() {
                        $submitBtn
                            .prop('disabled', false)
                            .html('<i class="glyphicon glyphicon-ok-sign"></i> Save Changes');
                    }, 1000);
                    return;
                }
            }
        },
        error: function(xhr, status, error) {
            // Log error details
            console.error('XHR:', xhr);
            console.error('Status:', status);
            console.error('Error:', error);
            
            // Show error message
            $('#edit-digitalswap-messages').html(
                '<div class="alert alert-danger">' +
                '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> ' +
                'Server error occurred. Please check console for details.<br>' +
                'Status: ' + status + '<br>' +
                'Error: ' + error +
                '</div>'
            );
        },
        complete: function() {
            // Reset button state after a short delay
            setTimeout(function() {
                $submitBtn
                    .prop('disabled', false)
                    .html('<i class="glyphicon glyphicon-ok-sign"></i> Save Changes');
            }, 1000);
        }
    });
});