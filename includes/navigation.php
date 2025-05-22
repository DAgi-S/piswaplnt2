<nav class="navbar navbar-default navbar-static-top">
    <div class="container-fluid">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="dashboard.php">Pi Stock</a>
        </div>

        <!-- Collect the nav links, forms, and other content for toggling -->
        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav">
                <li id="navDashboard"><a href="dashboard.php"> <i class="glyphicon glyphicon-home"></i> Dashboard</a></li>
                
                <?php if(isset($_SESSION['roleId']) && ($_SESSION['roleId'] == 2 || hasPermission('view_product'))): ?>
                <li id="navProduct"><a href="product.php"> <i class="glyphicon glyphicon-list-alt"></i> Product </a></li>
                <?php endif; ?>
                
                <?php if(isset($_SESSION['roleId']) && ($_SESSION['roleId'] == 2 || hasPermission('view_invoice'))): ?>
                <li id="navInvoice"><a href="invoice.php"> <i class="glyphicon glyphicon-credit-card"></i> Invoice </a></li>
                <?php endif; ?>
                
                <?php if(isset($_SESSION['roleId']) && ($_SESSION['roleId'] == 2 || hasPermission('view_gps_letter'))): ?>
                <li id="navGPS"><a href="gps.php"> <i class="glyphicon glyphicon-map-marker"></i> GPS Letter </a></li>
                <?php endif; ?>
                
                <?php if(isset($_SESSION['roleId']) && ($_SESSION['roleId'] == 2 || hasPermission('view_digitalswap'))): ?>
                <li id="navDigital"><a href="digital.php"> <i class="glyphicon glyphicon-transfer"></i> Digital Swap </a></li>
                <?php endif; ?>
                
                <?php if(isset($_SESSION['roleId']) && $_SESSION['roleId'] >= 1) { ?>
                <li id="navUsers"><a href="user.php"> <i class="glyphicon glyphicon-user"></i> Users </a></li>
                <?php } ?>
                
                <?php if(isset($_SESSION['roleId']) && $_SESSION['roleId'] == 2) { ?>
                <li id="navRoles"><a href="role_management.php"> <i class="glyphicon glyphicon-wrench"></i> Roles </a></li>
                <?php } ?>
            </ul>
            <ul class="nav navbar-nav navbar-right">
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                        <i class="glyphicon glyphicon-user"></i> 
                        <?php echo isset($_SESSION['username']) ? $_SESSION['username'] : ''; ?> 
                        <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a href="setting.php"><i class="glyphicon glyphicon-cog"></i> Setting</a></li>
                        <li><a href="logout.php"><i class="glyphicon glyphicon-log-out"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div><!-- /.navbar-collapse -->
    </div><!-- /.container-fluid -->
</nav> 