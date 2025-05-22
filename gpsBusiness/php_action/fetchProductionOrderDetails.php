<?php
require_once 'db_connect.php';
require_once 'core.php';

if(isset($_POST['orderId'])) {
    $orderId = filter_var($_POST['orderId'], FILTER_VALIDATE_INT);
    
    // Get order details
    $sql = "SELECT po.*, CONCAT(u.username) as created_by 
            FROM production_orders po 
            LEFT JOIN users u ON po.created_by = u.user_id 
            WHERE po.production_order_id = ?";
    
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $order = $result->fetch_assoc();
        
        // Get material details
        $materialSql = "SELECT pom.*, p.name as product_name, p.quantity as current_stock 
                       FROM production_order_materials pom
                       LEFT JOIN products p ON pom.product_id = p.product_id
                       WHERE pom.production_order_id = ?";
        
        $materialStmt = $connect->prepare($materialSql);
        $materialStmt->bind_param('i', $orderId);
        $materialStmt->execute();
        $materials = $materialStmt->get_result();
        
        // Start building the HTML response
        ?>
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">
                Production Order Details: <?php echo htmlspecialchars($order['order_number']); ?>
            </h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <th>Status</th>
                            <td>
                                <span class="label label-<?php 
                                    echo $order['status'] == 'completed' ? 'success' : 
                                         ($order['status'] == 'cancelled' ? 'danger' : 
                                         ($order['status'] == 'in_progress' ? 'info' : 
                                         ($order['status'] == 'confirmed' ? 'primary' : 'default')));
                                ?>">
                                    <?php echo strtoupper(str_replace('_', ' ', $order['status'])); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Target Quantity</th>
                            <td><?php echo htmlspecialchars($order['target_quantity']); ?></td>
                        </tr>
                        <tr>
                            <th>Completed Quantity</th>
                            <td><?php echo htmlspecialchars($order['completed_quantity']); ?></td>
                        </tr>
                        <tr>
                            <th>Start Date</th>
                            <td><?php echo htmlspecialchars($order['start_date']); ?></td>
                        </tr>
                        <tr>
                            <th>Expected Completion</th>
                            <td><?php echo htmlspecialchars($order['completion_date']); ?></td>
                        </tr>
                        <tr>
                            <th>Created By</th>
                            <td><?php echo htmlspecialchars($order['created_by']); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h4>Required Materials</h4>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Material</th>
                                <th>Required</th>
                                <th>Consumed</th>
                                <th>Current Stock</th>
                                <th>Status</th>
                                <?php if($order['status'] == 'in_progress'): ?>
                                <th>Action</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($material = $materials->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($material['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($material['required_quantity']); ?></td>
                                <td><?php echo htmlspecialchars($material['consumed_quantity']); ?></td>
                                <td><?php echo htmlspecialchars($material['current_stock']); ?></td>
                                <td>
                                    <span class="label label-<?php 
                                        echo $material['status'] == 'fully_consumed' ? 'success' : 
                                             ($material['status'] == 'partially_consumed' ? 'warning' : 'default');
                                    ?>">
                                        <?php echo strtoupper(str_replace('_', ' ', $material['status'])); ?>
                                    </span>
                                </td>
                                <?php if($order['status'] == 'in_progress'): ?>
                                <td>
                                    <?php if($material['status'] != 'fully_consumed'): ?>
                                    <button type="button" class="btn btn-xs btn-primary consume-material" 
                                            data-order-id="<?php echo $orderId; ?>"
                                            data-material-id="<?php echo $material['id']; ?>"
                                            data-product-id="<?php echo $material['product_id']; ?>"
                                            data-required="<?php echo $material['required_quantity']; ?>"
                                            data-consumed="<?php echo $material['consumed_quantity']; ?>">
                                        Consume
                                    </button>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php if(!empty($order['notes'])): ?>
            <div class="row">
                <div class="col-md-12">
                    <h4>Notes</h4>
                    <div class="well">
                        <?php echo nl2br(htmlspecialchars($order['notes'])); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            <?php if($order['status'] == 'in_progress'): ?>
            <button type="button" class="btn btn-success complete-production" data-id="<?php echo $orderId; ?>">
                Complete Production
            </button>
            <?php endif; ?>
        </div>

        <?php
    } else {
        echo '<div class="modal-header">';
        echo '<button type="button" class="close" data-dismiss="modal">&times;</button>';
        echo '<h4 class="modal-title">Error</h4>';
        echo '</div>';
        echo '<div class="modal-body">';
        echo '<p>Production order not found.</p>';
        echo '</div>';
        echo '<div class="modal-footer">';
        echo '<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>';
        echo '</div>';
    }
}

$connect->close(); 