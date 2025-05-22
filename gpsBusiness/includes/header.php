<?php

require_once dirname(dirname(__DIR__)) . '/php_action/db_connect.php';

// Check if user is logged in
if(!isset($_SESSION['userId'])) {
    header('location: ../index.php');
    exit();
}
?><!DOCTYPE html>
<html>
<head>
    <title>GPS Business</title>

    <!-- jquery -->
    <script src="../assests/jquery/jquery.min.js"></script>
    
    <!-- bootstrap -->
    <link rel="stylesheet" href="../assests/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assests/bootstrap/css/bootstrap-theme.min.css">
    <script src="../assests/bootstrap/js/bootstrap.min.js"></script>
    
    <!-- DataTables -->
    <link rel="stylesheet" href="../assests/plugins/datatables/jquery.dataTables.min.css">
    <link rel="stylesheet" href="../assests/plugins/datatables/dataTables.bootstrap.css">
    <script src="../assests/plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="../assests/plugins/datatables/dataTables.bootstrap.min.js"></script>
    
    <!-- font awesome -->
    <link rel="stylesheet" href="../assests/font-awesome/css/font-awesome.min.css">
    
    <!-- custom css -->
    <link rel="stylesheet" href="../custom/css/custom.css">
    
    <!-- file input -->
    <link rel="stylesheet" href="../assests/plugins/fileinput/css/fileinput.min.css">
    
    <!-- jquery ui -->  
    <link rel="stylesheet" href="../assests/jquery-ui/jquery-ui.min.css">
    <script src="../assests/jquery-ui/jquery-ui.min.js"></script>

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
        /* Table Styles */
        .table {
            font-size: 10px !important;
        }
        
        .table > thead > tr > th {
            background-color: #337ab7;
            color: white;
            font-weight: normal;
            vertical-align: middle !important;
            border-bottom: 0 !important;
        }
        
        .table-striped > tbody > tr:nth-of-type(odd) {
            background-color: #f9f9f9;
        }
        
        .table-striped > tbody > tr:nth-of-type(even) {
            background-color: #ffffff;
        }
        
        .table > tbody > tr > td {
            vertical-align: middle;
            padding: 4px 8px;
        }

        /* DataTables Styles */
        .dataTables_wrapper .dataTables_length, 
        .dataTables_wrapper .dataTables_filter, 
        .dataTables_wrapper .dataTables_info, 
        .dataTables_wrapper .dataTables_processing, 
        .dataTables_wrapper .dataTables_paginate {
            font-size: 10px;
        }

        /* Button Styles */
        .btn {
            padding: 2px 6px;
            font-size: 10px;
        }
        
        .btn-group {
            display: flex;
            gap: 2px;
        }

        /* Action Column */
        .action-buttons {
            white-space: nowrap;
        }

        /* Status Labels */
        .label {
            font-size: 9px;
            padding: 3px 6px;
        }

        /* Search and Length Menu */
        .dataTables_length select {
            height: 25px;
            font-size: 10px;
            padding: 2px;
        }

        .dataTables_filter input {
            height: 25px;
            font-size: 10px;
            padding: 2px 6px;
        }

        /* Pagination */
        .pagination > li > a {
            padding: 4px 8px;
            font-size: 10px;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-default navbar-static-top">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="../index.php">Pi Stock</a>
        </div>

        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">      
            <ul class="nav navbar-nav navbar-right">
                <!-- Orders & Payments Dropdown -->
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-shopping-cart"></i> Orders & Payments <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a href="gps_orders.php"><i class="fa fa-list"></i> GPS Orders</a></li>
                        <li><a href="gps_payments.php"><i class="fa fa-money"></i> GPS Payments</a></li>
                        <li><a href="gps_payment_followup.php"><i class="fa fa-tasks"></i> Payment Follow-up</a></li>
                    </ul>
                </li>

                <!-- Sales & Expenses Dropdown -->
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-line-chart"></i> Sales & Expenses <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a href="gps_sales.php"><i class="fa fa-shopping-cart"></i> GPS Sales</a></li>
                        <li><a href="gps_expenses.php"><i class="fa fa-credit-card"></i> GPS Expenses</a></li>
                        <li><a href="gps_expense_categories.php"><i class="fa fa-list"></i> Expense Categories</a></li>
                    </ul>
                </li>

                <!-- GPS Business Dropdown -->
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-briefcase"></i> GPS Business <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a href="gps_business_cycles.php"><i class="fa fa-refresh"></i> Business Cycles</a></li>
                        <li><a href="gps_profit_distribution.php"><i class="fa fa-money"></i> Profit Distribution</a></li>
                        <li><a href="gps_business_reports.php"><i class="fa fa-bar-chart"></i> Business Reports</a></li>
                    </ul>
                </li>

                <!-- Business Management -->
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-cogs"></i> Business Management <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a href="gps_investors.php"><i class="fa fa-users"></i> GPS Investors</a></li>
                        <li><a href="gps_profit.php"><i class="fa fa-line-chart"></i> GPS Profit</a></li>
                    </ul>
                </li>

                <!-- Main System Link -->
                <li><a href="../index.php"><i class="fa fa-home"></i> Main System</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container"> 