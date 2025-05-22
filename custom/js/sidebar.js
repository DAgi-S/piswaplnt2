$(document).ready(function() {
    // Toggle sidebar
    $('#sidebarToggle').on('click', function() {
        $('.sidebar').toggleClass('collapsed');
        $('.main-content').toggleClass('expanded');
        
        // Store state in localStorage
        localStorage.setItem('sidebarCollapsed', $('.sidebar').hasClass('collapsed'));
    });

    // Restore sidebar state from localStorage
    if(localStorage.getItem('sidebarCollapsed') === 'true') {
        $('.sidebar').addClass('collapsed');
        $('.main-content').addClass('expanded');
    }

    // Toggle submenu
    $('.nav-link[data-toggle="collapse"]').on('click', function(e) {
        e.preventDefault();
        const target = $(this).data('target');
        
        if($('.sidebar').hasClass('collapsed')) {
            return; // Don't toggle when sidebar is collapsed
        }
        
        $(target).toggleClass('show');
        $(this).find('.submenu-arrow').toggleClass('fa-angle-down fa-angle-right');
    });

    // Handle submenu in collapsed mode
    $('.nav-item').on('mouseenter mouseleave', function() {
        if($('.sidebar').hasClass('collapsed')) {
            $(this).find('.sidebar-submenu').toggleClass('show');
        }
    });

    // Search functionality
    $('.search-input').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        $('.nav-item').each(function() {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.indexOf(searchTerm) > -1);
        });
    });

    // Active link handling
    const currentPath = window.location.pathname;
    $('.nav-link').each(function() {
        const linkPath = $(this).attr('href');
        if(linkPath && currentPath.includes(linkPath)) {
            $(this).addClass('active');
            $(this).parents('.sidebar-submenu').addClass('show');
        }
    });

    // Quick access buttons
    $('.quick-access-btn').on('click', function(e) {
        e.preventDefault();
        const action = $(this).data('action');
        
        switch(action) {
            case 'pos':
                window.location.href = 'pos.php';
                break;
            case 'new-order':
                window.location.href = 'orders.php?o=add';
                break;
            case 'new-product':
                window.location.href = 'products.php';
                $('#addProductModalBtn').click();
                break;
            // Add more quick actions as needed
        }
    });

    // Notification handling
    function updateNotifications() {
        $.ajax({
            url: 'get_notifications.php',
            method: 'GET',
            success: function(response) {
                if(response.count > 0) {
                    $('.notification-badge').text(response.count).show();
                } else {
                    $('.notification-badge').hide();
                }
            }
        });
    }

    // Update notifications every minute
    setInterval(updateNotifications, 60000);
    updateNotifications(); // Initial check

    // Mobile responsiveness
    function handleResponsive() {
        if($(window).width() <= 768) {
            $('.sidebar').addClass('collapsed');
            $('.main-content').addClass('expanded');
        }
    }

    $(window).on('resize', handleResponsive);
    handleResponsive(); // Initial check
}); 