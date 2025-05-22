<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set session variables for testing (simulate admin login)
if (!isset($_SESSION['userId'])) {
    $_SESSION['userId'] = 1;
    $_SESSION['userName'] = 'admin';
    $_SESSION['roleId'] = 2; // Admin role
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Products Simple Test</title>
    
    <!-- jQuery -->
    <script src="assests/jquery/jquery.min.js"></script>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="assests/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assests/bootstrap/css/bootstrap-theme.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/jquery.dataTables.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="assests/font-awesome/css/font-awesome.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="custom/css/custom.css">
    
    <style>
        .debug-info {
            background-color: #f8f9fa;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h1>Products Simple Test</h1>
                
                <div class="debug-info">
                    <h3>Session Information</h3>
                    <pre><?php print_r($_SESSION); ?></pre>
                </div>
                
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <div class="page-heading"><i class="glyphicon glyphicon-edit"></i> Manage Products (Simple Test)</div>
                    </div>
                    <div class="panel-body">
                        <div id="status-message"></div>
                        
                        <table class="table" id="productTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Brand</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Products will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="assests/bootstrap/js/bootstrap.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#status-message').html('<div class="alert alert-info">Loading products...</div>');
            
            // Fetch products using AJAX
            $.ajax({
                url: 'fetch_products_simple.php',
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    console.log('Response:', response);
                    
                    if (response.success) {
                        var products = response.data;
                        $('#status-message').html('<div class="alert alert-success">Successfully loaded ' + products.length + ' products</div>');
                        
                        // Initialize DataTable
                        $('#productTable').DataTable({
                            data: products,
                            columns: [
                                { data: 'product_id' },
                                { data: 'name' },
                                { 
                                    data: 'selling_price',
                                    render: function(data) {
                                        return parseFloat(data).toFixed(2);
                                    }
                                },
                                { data: 'current_stock' },
                                { 
                                    data: 'brand_name',
                                    render: function(data) {
                                        return data || 'Unassigned';
                                    }
                                },
                                { 
                                    data: 'category_name',
                                    render: function(data) {
                                        return data || 'Unassigned';
                                    }
                                },
                                { 
                                    data: 'status',
                                    render: function(data) {
                                        if (data === 'active') {
                                            return '<span class="label label-success">Active</span>';
                                        } else {
                                            return '<span class="label label-danger">Inactive</span>';
                                        }
                                    }
                                }
                            ],
                            order: [[1, 'asc']]
                        });
                    } else {
                        $('#status-message').html('<div class="alert alert-danger">Error: ' + response.messages + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    $('#status-message').html('<div class="alert alert-danger">AJAX Error: ' + error + '</div>');
                    
                    // Log more details
                    console.log('XHR:', xhr);
                    console.log('Response Text:', xhr.responseText);
                }
            });
        });
    </script>
</body>
</html> 