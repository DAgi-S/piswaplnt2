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

// Include database connection
require_once 'php_action/db_connect.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Products Test</title>
    
    <!-- jQuery -->
    <script src="assests/jquery/jquery.min.js"></script>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="assests/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assests/bootstrap/css/bootstrap-theme.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.7.0/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.7/css/responsive.dataTables.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="assests/font-awesome/css/font-awesome.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="custom/css/custom.css">
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <div class="page-heading"><i class="glyphicon glyphicon-edit"></i> Manage Products (Test)</div>
                    </div>
                    <div class="panel-body">
                        <div class="remove-messages"></div>
                        <div id="error-messages" class="alert alert-danger" style="display:none;"></div>

                        <div class="div-action pull pull-right" style="padding-bottom:20px;">
                            <button class="btn btn-default button1" data-toggle="modal" id="addProductModalBtn" data-target="#addProductModal">
                                <i class="glyphicon glyphicon-plus-sign"></i> Add Product
                            </button>
                        </div>

                        <table class="table" id="manageProductTable">
                            <thead>
                                <tr>
                                    <th>Photo</th>
                                    <th>Product Name</th>
                                    <th>Price</th>
                                    <th>Current Stock</th>
                                    <th>Total Purchased</th>
                                    <th>Brand</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Options</th>
                                </tr>
                            </thead>
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
    <script src="https://cdn.datatables.net/buttons/1.7.0/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.7/js/dataTables.responsive.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var manageProductTable = $('#manageProductTable').DataTable({
                'ajax': {
                    'url': 'php_action/fetchProducts.php',
                    'type': 'POST',
                    'dataSrc': function(response) {
                        console.log('Response:', response);
                        
                        if (!response.success) {
                            console.error('Server error:', response.messages);
                            $('.remove-messages').html(
                                '<div class="alert alert-danger">'+
                                '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                                '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> '+ response.messages +
                                '</div>'
                            );
                            return [];
                        }
                        return response.data || [];
                    }
                },
                'columns': [
                    {
                        'data': 'product_image',
                        'render': function(data, type, row) {
                            if(data && data != '') {
                                return '<img src="' + data + '" class="img-rounded" width="50" height="50" />';
                            }
                            return '<img src="assests/images/photo_default.png" class="img-rounded" width="50" height="50" />';
                        },
                        'orderable': false,
                        'className': 'text-center'
                    },
                    {'data': 'name'},
                    {
                        'data': 'selling_price',
                        'render': function(data) {
                            return parseFloat(data).toFixed(2);
                        }
                    },
                    {'data': 'current_stock'},
                    {'data': 'total_purchased'},
                    {
                        'data': 'brand_name',
                        'render': function(data) {
                            return data || 'Unassigned';
                        }
                    },
                    {
                        'data': 'category_name',
                        'render': function(data) {
                            return data || 'Unassigned';
                        }
                    },
                    {
                        'data': 'status',
                        'render': function(data) {
                            if (data === 'active' || data === '1') {
                                return '<span class="label label-success">Available</span>';
                            } else if (data === 'inactive' || data === '0') {
                                return '<span class="label label-danger">Not Available</span>';
                            } else {
                                return '<span class="label label-default">Unknown</span>';
                            }
                        }
                    },
                    {
                        'data': 'product_id',
                        'render': function(data, type, row) {
                            return '<div class="btn-group">' +
                                '<button class="btn btn-sm btn-primary edit-product" data-id="' + data + '">' +
                                '<i class="glyphicon glyphicon-edit"></i>' +
                                '</button> ' +
                                '<button class="btn btn-sm btn-danger remove-product" data-id="' + data + '">' +
                                '<i class="glyphicon glyphicon-trash"></i>' +
                                '</button> ' +
                                '<button class="btn btn-sm btn-info view-history" data-id="' + data + '">' +
                                '<i class="glyphicon glyphicon-time"></i>' +
                                '</button>' +
                                '</div>';
                        },
                        'orderable': false
                    }
                ],
                'order': [[1, 'asc']],
                'pageLength': 10,
                'responsive': true
            });
        });
    </script>
</body>
</html> 