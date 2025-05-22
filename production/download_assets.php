<?php

// Configuration
$assets = [
    // DataTables Core
    'datatables' => [
        'js' => [
            'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js',
            'https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap.min.js',
            'https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js',
            'https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap.min.js',
            'https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js',
            'https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js',
            'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js',
            'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/pdfmake.min.js',
            'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/vfs_fonts.js'
        ],
        'css' => [
            'https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap.min.css',
            'https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap.min.css'
        ]
    ],
    // Select2
    'select2' => [
        'js' => [
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js'
        ],
        'css' => [
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css'
        ]
    ],
    // SweetAlert2
    'sweetalert2' => [
        'js' => [
            'https://cdn.jsdelivr.net/npm/sweetalert2@11.10.1/dist/sweetalert2.all.min.js' => 'sweetalert2.all.min.js'
        ],
        'css' => [
            'https://cdn.jsdelivr.net/npm/sweetalert2@11.10.1/dist/sweetalert2.min.css' => 'sweetalert2.min.css'
        ]
    ],
    // Moment.js
    'moment' => [
        'js' => [
            'https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js'
        ]
    ]
];

// Function to create directory if it doesn't exist
function createDir($path) {
    if (!file_exists($path)) {
        mkdir($path, 0777, true);
    }
}

// Function to download file
function downloadFile($url, $path) {
    // Create directory if it doesn't exist
    $dir = dirname($path);
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
    
    $content = file_get_contents($url);
    if ($content === false) {
        throw new Exception("Failed to download: " . $url);
    }
    if (file_put_contents($path, $content) === false) {
        throw new Exception("Failed to save file: " . $path);
    }
    return true;
}

// Create base directories in the correct location
createDir('../assests/plugins/datatables/js');
createDir('../assests/plugins/datatables/css');
createDir('../assests/plugins/select2/js');
createDir('../assests/plugins/select2/css');
createDir('../assests/plugins/sweetalert2/js');
createDir('../assests/plugins/sweetalert2/css');
createDir('../assests/plugins/moment');

// Download all assets
foreach ($assets as $plugin => $types) {
    foreach ($types as $type => $files) {
        foreach ($files as $url => $filename) {
            if (is_numeric($url)) {
                $url = $filename;
                $filename = basename($url);
            }
            $path = "../assests/plugins/{$plugin}/{$type}/{$filename}";
            
            try {
                if (downloadFile($url, $path)) {
                    echo "Successfully downloaded: {$filename}\n";
                }
            } catch (Exception $e) {
                echo "Error: " . $e->getMessage() . "\n";
            }
        }
    }
}

echo "Asset download completed!\n"; 