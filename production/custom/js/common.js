// Common utility functions
$(document).ready(function() {
    // Initialize Select2 for all select elements with 'select2' class
    $('.select2').select2();

    // Initialize DataTables defaults
    $.extend(true, $.fn.dataTable.defaults, {
        "responsive": true,
        "language": {
            "processing": "Processing...",
            "lengthMenu": "Show _MENU_ entries",
            "zeroRecords": "No matching records found",
            "info": "Showing _START_ to _END_ of _TOTAL_ entries",
            "infoEmpty": "Showing 0 to 0 of 0 entries",
            "infoFiltered": "(filtered from _MAX_ total entries)",
            "search": "Search:",
            "paginate": {
                "first": "First",
                "last": "Last",
                "next": "Next",
                "previous": "Previous"
            }
        }
    });

    // Global AJAX error handler
    $(document).ajaxError(function(event, jqXHR, settings, error) {
        console.error('Ajax error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred while processing your request. Please try again.'
        });
    });

    // Form validation helper
    window.validateForm = function(formId) {
        const $form = $(formId);
        let isValid = true;
        
        $form.find('[required]').each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        return isValid;
    };

    // Success message helper
    window.showSuccessMessage = function(message) {
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: message
        });
    };

    // Error message helper
    window.showErrorMessage = function(message) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: message
        });
    };

    // Check if we need DataTables on this page
    if ($.fn.DataTable) {
        // Initialize any tables with the 'datatable' class
        $('.datatable').each(function() {
            $(this).DataTable({
                responsive: true,
                pageLength: 10,
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                }
            });
        });
    }

    // Initialize tooltips if Bootstrap is available
    if (typeof $.fn.tooltip === 'function') {
        $('[data-toggle="tooltip"]').tooltip();
    }

    // Initialize popovers if Bootstrap is available
    if (typeof $.fn.popover === 'function') {
        $('[data-toggle="popover"]').popover();
    }

    // Handle any flash messages
    const flashMessage = $('#flashMessage');
    if (flashMessage.length) {
        setTimeout(function() {
            flashMessage.fadeOut('slow');
        }, 3000);
    }

    // Handle sidebar toggle
    $('.sidebar-toggle').on('click', function(e) {
        e.preventDefault();
        $('body').toggleClass('sidebar-collapsed');
    });
}); 