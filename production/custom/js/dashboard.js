document.addEventListener('DOMContentLoaded', function() {
    // Initialize variables
    const customizeBtn = document.getElementById('customize-dashboard');
    const modal = document.getElementById('customization-modal');
    const saveBtn = document.getElementById('save-preferences');
    const cancelBtn = document.getElementById('cancel-customize');
    const sectionsContainer = document.querySelector('.customization-sections');
    
    // Show modal when customize button is clicked
    customizeBtn.addEventListener('click', function() {
        loadAvailableComponents();
        modal.style.display = 'flex';
        // Add a small delay before adding the show class for the animation
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);
    });
    
    // Hide modal when cancel is clicked
    cancelBtn.addEventListener('click', hideModal);
    
    // Hide modal when clicking outside
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            hideModal();
        }
    });
    
    // Function to hide modal with animation
    function hideModal() {
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300); // Match the transition duration in CSS
    }
    
    // Save preferences when save button is clicked
    saveBtn.addEventListener('click', function() {
        savePreferences();
    });
    
    // Function to load available components
    function loadAvailableComponents() {
        fetch('php_action/getAvailableComponents.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    populateCustomizationModal(data.components);
                } else {
                    showError('Error loading components: ' + data.message);
                }
            })
            .catch(error => showError('Error loading components: ' + error.message));
    }
    
    // Function to populate the customization modal
    function populateCustomizationModal(components) {
        sectionsContainer.innerHTML = ''; // Clear existing content
        
        // Create sections for buttons, cards, and analytics
        const sections = {
            buttons: 'Quick Actions',
            cards: 'Information Cards',
            analytics: 'Analytics Widgets'
        };
        
        for (const [sectionKey, sectionTitle] of Object.entries(sections)) {
            const section = document.createElement('div');
            section.className = 'customization-section';
            
            section.innerHTML = `
                <h3 class="section-title">${sectionTitle}</h3>
                <div class="components-grid" data-section="${sectionKey}">
                    ${components[sectionKey].map(component => `
                        <div class="component-item ${component.isActive ? 'active' : ''}" 
                             data-key="${component.key}">
                            <i class="${component.icon}"></i>
                            <span>${component.name}</span>
                        </div>
                    `).join('')}
                </div>
            `;
            
            sectionsContainer.appendChild(section);
            
            // Add click handlers for component selection
            const componentItems = section.querySelectorAll('.component-item');
            componentItems.forEach(item => {
                item.addEventListener('click', function() {
                    this.classList.toggle('active');
                });
            });
        }
    }
    
    // Function to gather current preferences from modal
    function gatherPreferences() {
        const preferences = {
            buttons: [],
            cards: [],
            analytics: []
        };
        
        // Gather selected components from each section
        Object.keys(preferences).forEach(section => {
            const container = document.querySelector(`.components-grid[data-section="${section}"]`);
            const activeItems = container.querySelectorAll('.component-item.active');
            
            activeItems.forEach(item => {
                preferences[section].push(item.dataset.key);
            });
        });
        
        return preferences;
    }
    
    // Function to save preferences
    function savePreferences() {
        const preferences = gatherPreferences();
        
        fetch('php_action/saveDashboardPreferences.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ preferences: preferences })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload(); // Reload to show new preferences
            } else {
                showError('Error saving preferences: ' + data.message);
            }
        })
        .catch(error => showError('Error saving preferences: ' + error.message));
    }
    
    // Function to show error messages
    function showError(message) {
        console.error(message);
        alert(message);
    }
    
    // Function to load card data
    function loadCardData(cardElement = null) {
        const cards = cardElement ? [cardElement] : document.querySelectorAll('.info-card');
        
        cards.forEach(card => {
            const cardKey = card.dataset.key;
            const dataContainer = card.querySelector('.card-data');
            const refreshBtn = card.querySelector('.card-refresh');
            
            // Add loading state
            dataContainer.classList.add('loading');
            if (refreshBtn) {
                refreshBtn.querySelector('i').classList.add('spinning');
            }
            
            fetch(`php_action/getCardData.php?card=${cardKey}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Create card content
                        let html = `<div class="value">${data.data.value}</div>`;
                        
                        // Add trend if available
                        if (data.data.trend) {
                            html += `
                                <div class="trend ${data.data.trend.direction}">
                                    <i class="fas fa-arrow-${data.data.trend.direction}"></i>
                                    ${data.data.trend.value}
                                    <span class="trend-label">${data.data.trend.label}</span>
                                </div>
                            `;
                        }
                        
                        dataContainer.innerHTML = html;
                    } else {
                        dataContainer.innerHTML = '<div class="error">Error loading data</div>';
                        console.error('Error loading card data:', data.message);
                    }
                })
                .catch(error => {
                    dataContainer.innerHTML = '<div class="error">Error loading data</div>';
                    console.error('Error loading card data:', error);
                })
                .finally(() => {
                    // Remove loading state
                    dataContainer.classList.remove('loading');
                    if (refreshBtn) {
                        refreshBtn.querySelector('i').classList.remove('spinning');
                    }
                });
        });
    }
    
    // Add refresh button click handlers
    document.querySelectorAll('.card-refresh').forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent card click event
            const card = this.closest('.info-card');
            loadCardData(card);
        });
    });
    
    // Function to load analytics data
    function loadAnalyticsData() {
        document.querySelectorAll('.analytics-widget').forEach(widget => {
            const widgetKey = widget.dataset.key;
            const contentContainer = widget.querySelector('.widget-content');
            
            fetch(`php_action/getAnalyticsData.php?widget=${widgetKey}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Handle different types of analytics data
                        renderAnalyticsData(contentContainer, data.data);
                    } else {
                        contentContainer.innerHTML = 'Error loading data';
                        console.error('Error loading analytics data:', data.message);
                    }
                })
                .catch(error => {
                    contentContainer.innerHTML = 'Error loading data';
                    console.error('Error loading analytics data:', error);
                });
        });
    }
    
    // Function to render analytics data
    function renderAnalyticsData(container, data) {
        if (data.type === 'chart') {
            // Create chart container
            const chartContainer = document.createElement('div');
            chartContainer.className = 'chart-container';
            const canvas = document.createElement('canvas');
            chartContainer.appendChild(canvas);
            container.innerHTML = '';
            container.appendChild(chartContainer);
            
            // Create chart
            new Chart(canvas, data.config);
        } else if (data.type === 'table') {
            container.innerHTML = data.content;
        } else {
            container.innerHTML = 'Unsupported data type';
        }
    }
    
    // Initial data load
    loadCardData();
    loadAnalyticsData();
    
    // Refresh data periodically
    setInterval(loadCardData, 60000); // Every minute
    setInterval(loadAnalyticsData, 300000); // Every 5 minutes
}); 