document.addEventListener('DOMContentLoaded', function() {
    // Handle quick action button clicks
    document.querySelectorAll('.dashboard-button').forEach(button => {
        button.addEventListener('click', function() {
            const key = this.dataset.key;
            handleAction(key);
        });
    });
    
    // Handle info card clicks
    document.querySelectorAll('.info-card').forEach(card => {
        card.addEventListener('click', function() {
            const key = this.dataset.key;
            handleAction(getActionFromCardKey(key));
        });
    });
    
    // Function to handle actions with permission check
    function handleAction(action) {
        fetch(`php_action/handleDashboardAction.php?action=${action}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                } else {
                    // Show error message
                    alert(data.message || 'An error occurred');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing your request');
            });
    }
    
    // Function to map card keys to actions
    function getActionFromCardKey(key) {
        const actionMap = {
            'total_products': 'view_products',
            'low_stock': 'view_products',
            'total_orders': 'view_orders',
            'total_sales': 'reports',
            'recent_orders': 'view_orders',
            'pending_orders': 'view_orders',
            'total_categories': 'view_categories',
            'total_brands': 'view_brands'
        };
        
        return actionMap[key] || key;
    }
    
    // Add hover effect to cards
    document.querySelectorAll('.info-card').forEach(card => {
        card.style.cursor = 'pointer';
        
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 2px 4px rgba(0,0,0,0.05)';
        });
    });
    
    // Add loading animation to analytics widgets
    document.querySelectorAll('.widget-content').forEach(widget => {
        if (widget.textContent.trim() === 'Loading...') {
            widget.innerHTML = `
                <div class="loading-spinner">
                    <div class="spinner"></div>
                    <span>Loading data...</span>
                </div>
            `;
        }
    });
}); 