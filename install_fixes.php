<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to copy a file with backup
function copyWithBackup($source, $destination) {
    // Create backup if destination exists
    if (file_exists($destination)) {
        $backupFile = $destination . '.bak.' . date('YmdHis');
        if (!copy($destination, $backupFile)) {
            return "Failed to create backup of $destination";
        }
        echo "Created backup: $backupFile<br>";
    }
    
    // Copy the fixed file
    if (!copy($source, $destination)) {
        return "Failed to copy $source to $destination";
    }
    
    return null; // No error
}

// Files to install
$files = [
    'fixed_core.php' => 'php_action/core.php',
    'fixed_middleware.php' => 'php_action/middleware.php',
    'fixed_fetchProducts.php' => 'php_action/fetchProducts.php',
    'fixed_header.php' => 'includes/header.php',
    'fixed_footer.php' => 'includes/footer.php',
    'fixed_products.php' => 'products.php'
];

// Install the files
echo "<h1>Installing Fixed Files</h1>";
echo "<ul>";
foreach ($files as $source => $destination) {
    echo "<li>Installing $source to $destination... ";
    $error = copyWithBackup($source, $destination);
    if ($error) {
        echo "<span style='color: red;'>ERROR: $error</span>";
    } else {
        echo "<span style='color: green;'>SUCCESS</span>";
    }
    echo "</li>";
}
echo "</ul>";

// Set session variables for admin login
session_start();
$_SESSION['userId'] = 1;
$_SESSION['userName'] = 'admin';
$_SESSION['username'] = 'admin'; // Some scripts might use this instead
$_SESSION['roleId'] = 2; // Admin role
$_SESSION['role'] = 'Admin'; // Some scripts might use this instead

// Output session information
echo "<h2>Session Fixed</h2>";
echo "<p>Session variables have been set for admin login.</p>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

// Provide links to test pages
echo "<h2>Test Pages:</h2>";
echo "<ul>";
echo "<li><a href='products.php' target='_blank'>Products Page</a></li>";
echo "<li><a href='dashboard.php' target='_blank'>Dashboard</a></li>";
echo "</ul>";
?> 