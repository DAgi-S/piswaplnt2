<?php
require_once 'php_action/core.php';
require_once 'includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    header('location: ../index.php');
    exit();
}

$userId = $_SESSION['userId'];

// Initialize preferences array
$preferences = array(
    'buttons' => array(),
    'cards' => array(),
    'analytics' => array()
);

try {
    // Query to get user's active preferences with component details
    $query = "SELECT udp.section_type, udp.component_key, udp.position, dac.component_name, dac.component_icon
             FROM user_dashboard_preferences udp
             JOIN dashboard_available_components dac 
                ON udp.section_type = dac.section_type 
                AND udp.component_key = dac.component_key
             WHERE udp.user_id = ? AND udp.is_active = 1
             ORDER BY udp.position";
    
    $stmt = $connect->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Populate preferences array
    while ($row = $result->fetch_assoc()) {
        $section = $row['section_type'];
        $preferences[$section][] = array(
            'key' => $row['component_key'],
            'name' => $row['component_name'],
            'icon' => $row['component_icon']
        );
    }
    
    // If no preferences found, get and insert default preferences
    if (empty($preferences['buttons']) && empty($preferences['cards']) && empty($preferences['analytics'])) {
        // Get default components
        $defaultQuery = "SELECT section_type, component_key, component_name, component_icon 
                        FROM dashboard_available_components 
                        WHERE is_default = 1";
        $defaultResult = $connect->query($defaultQuery);
        
        if ($defaultResult) {
            $position = 0;
            $insertQuery = "INSERT INTO user_dashboard_preferences 
                          (user_id, section_type, component_key, is_active, position) 
                          VALUES (?, ?, ?, 1, ?)";
            $insertStmt = $connect->prepare($insertQuery);
            
            while ($row = $defaultResult->fetch_assoc()) {
                $section = $row['section_type'];
                $preferences[$section][] = array(
                    'key' => $row['component_key'],
                    'name' => $row['component_name'],
                    'icon' => $row['component_icon']
                );
                
                // Insert default preference
                $insertStmt->bind_param("issi", $userId, $row['section_type'], $row['component_key'], $position);
                $insertStmt->execute();
                $position++;
            }
        }
    }
} catch (Exception $e) {
    // Log error for debugging
    error_log("Error fetching dashboard preferences: " . $e->getMessage());
}
?>

<div class="row">
    <div class="col-md-12">
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Home</a></li>
            <li class="active">Custom Dashboard</li>
        </ol>

        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="fas fa-tachometer-alt"></i> Custom Dashboard
                </div>
            </div>
            <div class="panel-body">
                <div class="dashboard-container">
                    <!-- Quick Action Buttons Section -->
                    <section id="buttons-section" class="dashboard-section">
                        <h2>Quick Actions</h2>
                        <div class="buttons-container">
                            <?php foreach ($preferences['buttons'] as $button): ?>
                                <button class="dashboard-button" data-key="<?php echo htmlspecialchars($button['key']); ?>">
                                    <i class="<?php echo htmlspecialchars($button['icon']); ?>"></i>
                                    <?php echo htmlspecialchars($button['name']); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <!-- Info Cards Section -->
                    <section id="cards-section" class="dashboard-section">
                        <h2>Overview</h2>
                        <div class="cards-container">
                            <?php foreach ($preferences['cards'] as $card): ?>
                                <div class="info-card" data-key="<?php echo htmlspecialchars($card['key']); ?>">
                                    <button class="card-refresh" title="Refresh data">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                    <div class="card-icon">
                                        <i class="<?php echo htmlspecialchars($card['icon']); ?>"></i>
                                    </div>
                                    <div class="card-content">
                                        <h3><?php echo htmlspecialchars($card['name']); ?></h3>
                                        <div class="card-data loading" id="<?php echo htmlspecialchars($card['key']); ?>-data">
                                            <div class="loading-spinner">
                                                <div class="spinner"></div>
                                                <span>Loading data...</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <!-- Analytics Section -->
                    <section id="analytics-section" class="dashboard-section">
                        <h2>Analytics</h2>
                        <div class="analytics-container">
                            <?php foreach ($preferences['analytics'] as $widget): ?>
                                <div class="analytics-widget" data-key="<?php echo htmlspecialchars($widget['key']); ?>">
                                    <h3><?php echo htmlspecialchars($widget['name']); ?></h3>
                                    <div class="widget-content loading" id="<?php echo htmlspecialchars($widget['key']); ?>-content">
                                        <div class="loading-spinner">
                                            <div class="spinner"></div>
                                            <span>Loading data...</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </div>

                <!-- Customize Dashboard Button -->
                <button id="customize-dashboard" class="customize-btn">
                    <i class="fas fa-cog"></i> Customize Dashboard
                </button>

                <!-- Customization Modal -->
                <div id="customization-modal" class="modal">
                    <div class="modal-content">
                        <h2>Customize Your Dashboard</h2>
                        <div class="customization-sections">
                            <!-- Sections will be populated dynamically via JavaScript -->
                        </div>
                        <div class="modal-footer">
                            <button id="save-preferences" class="save-btn">Save Changes</button>
                            <button id="cancel-customize" class="cancel-btn">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Chart.js for analytics -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>

<!-- Include custom CSS and JavaScript -->
<link rel="stylesheet" href="custom/css/dashboard.css">
<script src="custom/js/dashboard.js"></script>
<script src="custom/js/dashboard-actions.js"></script>

<?php require_once 'includes/footer.php'; ?> 