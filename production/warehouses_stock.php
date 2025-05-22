<?php 
require_once 'php_action/core.php';
require_once 'includes/header.php';

// Get warehouse ID from URL
$warehouseId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch warehouse details
$warehouseQuery = "SELECT name, code FROM warehouses WHERE id = ?";
$stmt = $connect->prepare($warehouseQuery);
$stmt->bind_param("i", $warehouseId);
$stmt->execute();
$result = $stmt->get_result();
$warehouse = $result->fetch_assoc();

if (!$warehouse) {
    echo "<script>window.location.href = 'warehouses.php';</script>";
    exit();
}
?>

<div class="row">
    <div class="col-md-12">
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Home</a></li>
            <li><a href="warehouses.php">Warehouses</a></li>
            <li class="active">Stock Items</li>
        </ol>

        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="fa fa-list"></i> Stock Items - <?php echo htmlspecialchars($warehouse['name']); ?> 
                    (<?php echo htmlspecialchars($warehouse['code']); ?>)
                    <a href="warehouses.php" class="btn btn-default pull-right">
                        <i class="fa fa-arrow-left"></i> Back to Warehouses
                    </a>
                </div>
            </div>

            <div class="panel-body">
                <div class="remove-messages"></div>

                <table class="table table-striped" id="warehouseStockTable">
                    <thead>
                        <tr>
                            <th>Item Code</th>
                            <th>Item Name</th>
                            <th>Type</th>
                            <th>Unit</th>
                            <th>Quantity</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit Stock Modal -->
<div class="modal fade" id="editStockModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editStockForm">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">Edit Stock Quantity</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="editQuantity">Quantity</label>
                        <input type="number" class="form-control" id="editQuantity" name="quantity" step="0.01" required>
                        <input type="hidden" id="editStockId" name="stockId">
                    </div>
                    <div class="form-group">
                        <label for="editNotes">Notes</label>
                        <textarea class="form-control" id="editNotes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Custom JS -->
<script type="text/javascript">
$(document).ready(function() {
    var warehouseId = <?php echo $warehouseId; ?>;
    
    // Initialize DataTable
    var stockTable = $('#warehouseStockTable').DataTable({
        'ajax': {
            'url': 'php_action/fetchWarehouseStock.php',
            'type': 'POST',
            'data': { warehouseId: warehouseId }
        },
        'columns': [
            { 'data': 'item_code' },
            { 'data': 'item_name' },
            { 'data': 'type' },
            { 'data': 'unit' },
            { 'data': 'quantity' },
            {
                'data': null,
                'render': function(data, type, row) {
                    var buttons = '<div class="btn-group">';
                    if (row.item_type === 'raw_material') {
                        buttons += '<a href="stock_itemsRawMaterial.php?id=' + row.item_id + 
                                 '" class="btn btn-info btn-sm"><i class="fa fa-info-circle"></i> Details</a>';
                    } else if (row.item_type === 'finished_good') {
                        buttons += '<a href="stock_itemFinishedGoods.php?id=' + row.item_id + 
                                 '" class="btn btn-info btn-sm"><i class="fa fa-info-circle"></i> Details</a>';
                    }
                    buttons += '<button class="btn btn-default btn-sm editStockBtn" onclick="editStock(' + row.id + 
                             ')"><i class="fa fa-edit"></i> Edit</button>';
                    buttons += '</div>';
                    return buttons;
                },
                'orderable': false
            }
        ],
        'order': [[1, 'asc']],
        'pageLength': 25,
        'responsive': true,
        'dom': '<"row"<"col-sm-6"B><"col-sm-6"f>>rt<"row"<"col-sm-6"i><"col-sm-6"p>>',
        'buttons': [
            { extend: 'copy', className: 'btn-sm' },
            { extend: 'csv', className: 'btn-sm' },
            { extend: 'excel', className: 'btn-sm' },
            { extend: 'pdf', className: 'btn-sm' },
            { extend: 'print', className: 'btn-sm' }
        ]
    });

    // Handle edit stock form submission
    $('#editStockForm').on('submit', function(e) {
        e.preventDefault();
        
        if(!validateStockForm()) {
            return;
        }
        
        $.ajax({
            url: 'php_action/updateStock.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#editStockModal').modal('hide');
                    stockTable.ajax.reload(null, false);
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.messages
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.messages
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to update stock quantity'
                });
            }
        });
    });
});

// Form validation function
function validateStockForm() {
    var quantity = $('#editQuantity').val();
    
    if (!quantity || isNaN(quantity) || parseFloat(quantity) < 0) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Input',
            text: 'Please enter a valid quantity'
        });
        return false;
    }
    
    return true;
}

// Function to edit stock
function editStock(stockId) {
    if(!stockId) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Invalid stock ID'
        });
        return;
    }

    // Reset form
    $('#editStockForm')[0].reset();
    
    // Set stock ID
    $('#editStockId').val(stockId);
    
    // Show modal
    $('#editStockModal').modal('show');
}
</script>

<?php require_once 'includes/footer.php'; ?> 