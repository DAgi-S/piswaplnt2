$(document).ready(function() {
    var manageGeneratedLettersTable = $('#manageGeneratedLettersTable').DataTable({
        'ajax': 'php_action/fetchGeneratedLetters.php',
        'order': [[0, 'desc']],
        'columns': [
            { 'data': 'generated_date' },
            { 'data': 'reference_no' },
            { 'data': 'client_name' },
            { 'data': 'template_name' },
            { 'data': 'vehicle_count' },
            { 
                'data': 'id',
                'render': function(data, type, row) {
                    return `<div class="btn-group">
                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                            Action <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a href="php_action/viewLetter.php?id=${data}" target="_blank">
                                <i class="glyphicon glyphicon-eye-open"></i> View</a></li>
                            <li><a href="#" onclick="removeLetter(${data})">
                                <i class="glyphicon glyphicon-trash"></i> Remove</a></li>
                        </ul>
                    </div>`;
                }
            }
        ]
    });

    // Remove letter function
    window.removeLetter = function(letterId) {
        if(confirm('Are you sure you want to remove this letter?')) {
            $.ajax({
                url: 'php_action/removeLetter.php',
                type: 'post',
                data: {letterId: letterId},
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        $('.remove-messages').html('<div class="alert alert-success">'+
                            '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                            '<strong><i class="glyphicon glyphicon-ok-sign"></i></strong> '+ response.messages +
                        '</div>');
                        manageGeneratedLettersTable.ajax.reload(null, false);
                    }
                }
            });
        }
    };
}); 