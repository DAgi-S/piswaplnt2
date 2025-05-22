<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Updating Files to Use Fixed Versions</h2>";

// Define the files to update
$filesToUpdate = [
    'php_action/core.php' => 'fixed_core.php',
    'php_action/middleware.php' => 'fixed_middleware.php',
    'php_action/fetchProducts.php' => 'fixed_fetchProducts.php',
    'includes/header.php' => 'fixed_header.php',
    'includes/footer.php' => 'fixed_footer.php',
    'products.php' => 'fixed_products.php'
];

// Function to create backup of original file
function createBackup($file) {
    if (file_exists($file)) {
        $backupFile = $file . '.bak.' . date('YmdHis');
        if (copy($file, $backupFile)) {
            return $backupFile;
        }
    }
    return false;
}

// Function to update file
function updateFile($originalFile, $fixedFile) {
    if (!file_exists($fixedFile)) {
        return "Fixed file $fixedFile does not exist";
    }
    
    $backup = createBackup($originalFile);
    if (!$backup) {
        return "Failed to create backup of $originalFile";
    }
    
    if (copy($fixedFile, $originalFile)) {
        return "Successfully updated $originalFile (backup: $backup)";
    } else {
        return "Failed to update $originalFile";
    }
}

// Update each file
echo "<ul>";
foreach ($filesToUpdate as $originalFile => $fixedFile) {
    echo "<li>";
    echo "<strong>$originalFile</strong> → $fixedFile: ";
    
    if (file_exists($originalFile)) {
        $result = updateFile($originalFile, $fixedFile);
        echo $result;
    } else {
        echo "Original file does not exist";
    }
    
    echo "</li>";
}
echo "</ul>";

echo "<h3>Next Steps:</h3>";
echo "<p>1. Test the application to ensure it's working correctly.</p>";
echo "<p>2. If there are issues, you can restore the backup files.</p>";
echo "<p>3. If everything works, you can delete the backup files.</p>";

echo "<p><a href='index.php'>Go to Login Page</a></p>";
echo "<p><a href='products.php'>Go to Products Page</a></p>";
?> 