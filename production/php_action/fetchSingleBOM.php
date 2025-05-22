<?php
require_once 'core.php';
require_once 'db_connect.php';

// Clear any previous output that might corrupt JSON
while (ob_get_level()) ob_end_clean();
header('Content-Type: application/json');

try {
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        throw new Exception("BOM ID is required");
    }

    $bomId = intval($_POST['id']);

    // Fetch BOM details with product and material information
    $query = "SELECT 
        b.id,
        b.product_id,
        b.material_id,
        b.quantity_required,
        b.wastage_percent,
        b.status,
        p.name as product_name,
        rm.material_code,
        rm.name as material_name,
        rm.unit
    FROM product_bom b
    JOIN production_products p ON b.product_id = p.id
    JOIN raw_materials rm ON b.material_id = rm.id
    WHERE b.id = ?";

    $stmt = $connect->prepare($query);
    if (!$stmt) {
        throw new Exception("Failed to prepare query: " . $connect->error);
    }

    $stmt->bind_param("i", $bomId);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute query: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception("BOM not found");
    }

    $bom = $result->fetch_assoc();

    // Fetch all active raw materials
    $materialsQuery = "SELECT 
        id,
        material_code,
        name,
        unit,
        current_stock,
        reserved_quantity
    FROM raw_materials 
    WHERE status = 'active'
    ORDER BY name ASC";

    $materialsResult = $connect->query($materialsQuery);
    if (!$materialsResult) {
        throw new Exception("Failed to fetch raw materials");
    }

    $materials = array();
    while ($row = $materialsResult->fetch_assoc()) {
        // Calculate available stock
        $available_stock = floatval($row['current_stock']) - floatval($row['reserved_quantity']);
        
        $materials[] = array(
            'id' => $row['id'],
            'text' => $row['material_code'] . ' - ' . $row['name'],
            'material_code' => $row['material_code'],
            'name' => $row['name'],
            'unit' => $row['unit'],
            'current_stock' => number_format($row['current_stock'], 2),
            'available_stock' => number_format($available_stock, 2)
        );
    }

    // Generate HTML for edit modal
    $html = '
    <form class="form-horizontal" id="editBOMForm" action="php_action/updateBOM.php" method="POST">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title"><i class="fa fa-edit"></i> Edit Bill of Materials</h4>
        </div>
        <div class="modal-body">
            <input type="hidden" name="bom_id" value="' . $bom['id'] . '">
            
            <div class="form-group">
                <label class="control-label col-sm-3">Product:</label>
                <div class="col-sm-9">
                    <input type="text" class="form-control" value="' . htmlspecialchars($bom['product_name']) . '" readonly>
                    <input type="hidden" name="product_id" value="' . $bom['product_id'] . '">
                </div>
            </div>
            
            <div class="form-group">
                <label class="control-label col-sm-3">Materials Required:</label>
                <div class="col-sm-9">
                    <table class="table table-bordered" id="editBomMaterialsTable">
                        <thead>
                            <tr>
                                <th>Raw Material</th>
                                <th>Quantity Required</th>
                                <th>Unit</th>
                                <th>Wastage %</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <select class="form-control material-select" name="materials[]" required>
                                        <option value="">Select Raw Material</option>';
    
    // Add options for all materials
    foreach ($materials as $material) {
        $selected = ($material['id'] == $bom['material_id']) ? 'selected' : '';
        $html .= '<option value="' . $material['id'] . '" 
                    data-material-code="' . htmlspecialchars($material['material_code']) . '"
                    data-name="' . htmlspecialchars($material['name']) . '"
                    data-unit="' . htmlspecialchars($material['unit']) . '"
                    ' . $selected . '>
                    ' . htmlspecialchars($material['text']) . '
                </option>';
    }

    $html .= '          </select>
                                </td>
                                <td>
                                    <input type="number" class="form-control quantity-required" 
                                        name="quantities[]" step="0.01" min="0.01" 
                                        value="' . number_format($bom['quantity_required'], 2) . '" required>
                                </td>
                                <td class="material-unit">' . htmlspecialchars($bom['unit']) . '</td>
                                <td>
                                    <input type="number" class="form-control wastage-percent" 
                                        name="wastage[]" step="0.01" min="0" max="100" 
                                        value="' . number_format($bom['wastage_percent'], 2) . '" required>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm remove-material">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-success" id="addEditMaterialRow">
                        <i class="fa fa-plus"></i> Add Material
                    </button>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary" id="updateBOMBtn">Update BOM</button>
        </div>
    </form>';

    echo json_encode(array(
        'success' => true,
        'html' => $html,
        'data' => $bom,
        'materials' => $materials
    ));

} catch (Exception $e) {
    error_log("Error in fetchSingleBOM.php: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'messages' => $e->getMessage()
    ));
}

$connect->close();