<?php
require_once 'includes/auth.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Pi Stock - Portals</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Include the header css and js files -->
    <link rel="stylesheet" href="assests/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assests/bootstrap/css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="assests/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="custom/css/custom.css">
    <script src="assests/jquery/jquery.min.js"></script>
    <script src="assests/bootstrap/js/bootstrap.min.js"></script>
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }
        
        .page-title {
            text-align: center;
            margin: 30px 0;
            font-weight: bold;
            color: #333;
            font-size: 36px;
        }
        
        .portal-buttons {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .portal-button {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: #0e6eaf;
            color: white;
            border-radius: 50px;
            padding: 15px 30px;
            margin-bottom: 20px;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            width: 100%;
            font-size: 18px;
            font-weight: bold;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .portal-button:hover {
            background-color: #0a5a93;
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
            color: white;
            text-decoration: none;
        }
        
        .button-arrow {
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            transition: all 0.3s ease;
        }
        
        .portal-button:hover .button-arrow {
            background-color: rgba(0, 0, 0, 0.3);
            transform: translateX(5px);
        }
        
        .row {
            margin-bottom: 20px;
        }
        
        .btn-back {
            margin: 20px;
            padding: 10px 20px;
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-back:hover {
            background-color: #5a6268;
        }
        
        @media (max-width: 768px) {
            .portal-button {
                padding: 12px 20px;
                font-size: 16px;
            }
            
            .button-arrow {
                width: 30px;
                height: 30px;
                font-size: 16px;
            }
            
            .page-title {
                font-size: 30px;
                margin: 20px 0;
            }
        }
    </style>
</head>
<body>
    <!-- Include the header -->
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h1 class="page-title">PORTALS</h1>
        
        <div class="portal-buttons">
            <div class="row">
                <div class="col-md-4">
                    <a href="production/raw_materials.php" class="portal-button">
                        <span>Raw Material</span>
                        <span class="button-arrow">&raquo;</span>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="production/production_orders.php" class="portal-button">
                        <span>Production Product</span>
                        <span class="button-arrow">&raquo;</span>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="production/bill_of_materials.php" class="portal-button">
                        <span>Bill of Material</span>
                        <span class="button-arrow">&raquo;</span>
                    </a>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <a href="production/purchase.php" class="portal-button">
                        <span>Purchases</span>
                        <span class="button-arrow">&raquo;</span>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="production/sales.php" class="portal-button">
                        <span>Sales Order</span>
                        <span class="button-arrow">&raquo;</span>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="production/pos.php" class="portal-button">
                        <span>POS</span>
                        <span class="button-arrow">&raquo;</span>
                    </a>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <a href="production/purchase_payments.php" class="portal-button">
                        <span>Purchase Payment</span>
                        <span class="button-arrow">&raquo;</span>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="production/sales_payment.php" class="portal-button">
                        <span>Sales Payment</span>
                        <span class="button-arrow">&raquo;</span>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="production/reports.php" class="portal-button">
                        <span>Report</span>
                        <span class="button-arrow">&raquo;</span>
                    </a>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <a href="production/purchase_reports.php" class="portal-button">
                        <span>Purchase Report</span>
                        <span class="button-arrow">&raquo;</span>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="production/sales_reports.php" class="portal-button">
                        <span>Sales Report</span>
                        <span class="button-arrow">&raquo;</span>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="production/newreports.php" class="portal-button">
                        <span>Comprehensive Report</span>
                        <span class="button-arrow">&raquo;</span>
                    </a>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <a href="setting.php" class="portal-button">
                        <span>Setting</span>
                        <span class="button-arrow">&raquo;</span>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="user.php" class="portal-button">
                        <span>User Accounts</span>
                        <span class="button-arrow">&raquo;</span>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="check_user_permissions.php" class="portal-button">
                        <span>Permissions</span>
                        <span class="button-arrow">&raquo;</span>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="text-center">
            <a href="dashboard.php" class="btn btn-back">
                <i class="glyphicon glyphicon-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <script>
        $(document).ready(function() {
            // Add animation for buttons
            $('.portal-button').hover(
                function() {
                    $(this).find('.button-arrow').html('&raquo;&raquo;');
                },
                function() {
                    $(this).find('.button-arrow').html('&raquo;');
                }
            );
        });
    </script>
</body>
</html> 