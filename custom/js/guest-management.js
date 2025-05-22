var manageGuestTable;

$(document).ready(function() {
    manageGuestTable = $('#manageGuestTable').DataTable({
        'ajax': 'php_action/fetchGuests.php',
        'order': []
    });

    // Submit guest form
    $("#submitGuestForm").on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var formData = new FormData(this);

        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: formData,
            dataType: 'json',
            cache: false,
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.success == true) {
                    $("#add-guest-messages").html('<div class="alert alert-success">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="fa fa-check"></i></strong> '+ response.messages +
                        '</div>');

                    $("#submitGuestForm")[0].reset();
                    manageGuestTable.ajax.reload(null, false);
                    $("#addGuestModal").modal('hide');
                } else {
                    $("#add-guest-messages").html('<div class="alert alert-warning">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="fa fa-warning"></i></strong> '+ response.messages +
                        '</div>');
                }
            }
        });
    });
});

function editGuest(guestId = null) {
    if(guestId) {
        $.ajax({
            url: 'php_action/getGuestData.php',
            type: 'post',
            data: {guestId: guestId},
            dataType: 'json',
            success: function(response) {
                $("#editGuestModal").modal('show');
                $("#editUsername").val(response.username);
                $("#editFullName").val(response.full_name);
                $("#editLinkedAccount").val(response.linked_account_id);
                $("#editExpiryDate").val(response.expiry_date);
                
                // Set access checkboxes
                var access = JSON.parse(response.access_level);
                $("input[name='editAccess[]']").each(function() {
                    if(access.includes($(this).val())) {
                        $(this).prop('checked', true);
                    }
                });
                
                // Add guest ID to form
                $("#editGuestForm").append('<input type="hidden" name="guestId" value="' + guestId + '" />');
            }
        });
    }
}

function removeGuest(guestId = null) {
    if(guestId) {
        $("#removeGuestBtn").unbind('click').bind('click', function() {
            $.ajax({
                url: 'php_action/removeGuest.php',
                type: 'post',
                data: {guestId: guestId},
                dataType: 'json',
                success: function(response) {
                    if(response.success == true) {
                        $(".remove-messages").html('<div class="alert alert-success">'+
                            '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                            '<strong><i class="fa fa-check"></i></strong> '+ response.messages +
                            '</div>');

                        // refresh the table
                        manageGuestTable.ajax.reload(null, false);
                        // close the modal
                        $("#removeGuestModal").modal('hide');
                    } else {
                        $(".remove-messages").html('<div class="alert alert-warning">'+
                            '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                            '<strong><i class="fa fa-warning"></i></strong> '+ response.messages +
                            '</div>');
                    }
                }
            });
        });
    }
}

function changeGuestStatus(guestId, status) {
    $.ajax({
        url: 'php_action/changeGuestStatus.php',
        type: 'post',
        data: {
            guestId: guestId,
            status: status
        },
        dataType: 'json',
        success: function(response) {
            if(response.success == true) {
                manageGuestTable.ajax.reload(null, false);
            }
        }
    });
} 