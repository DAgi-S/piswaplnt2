<?php
require_once 'core.php';

header('Content-Type: application/json');

$response = array(
    'success' => false,
    'messages' => '',
    'html' => ''
);

if(!isset($_POST['purchaseId'])) {
    $response['messages'] = 'Purchase ID is required';
    echo json_encode($response);
    exit();
}

try {
    $purchaseId = intval($_POST['purchaseId']);
    
    if($purchaseId <= 0) {
        throw new Exception('Invalid purchase ID');
    }
    
    // Fetch purchase details with error handling
    $sql = "SELECT p.*, s.company_name 
            FROM purchases p 
            LEFT JOIN suppliers s ON p.supplier_id = s.id 
            WHERE p.id = ? AND p.active = 1";
            
    $stmt = $connect->prepare($sql);
    if(!$stmt) {
        throw new Exception("Database error: " . $connect->error);
    }
    
    $stmt->bind_param("i", $purchaseId);
    if(!$stmt->execute()) {
        throw new Exception("Error executing query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $purchase = $result->fetch_assoc();
    
    if(!$purchase) {
        throw new Exception("Purchase not found or has been deleted");
    }
    
    // Fetch purchase items with error handling
    $sql = "SELECT pi.*, pr.name as product_name, pr.product_id 
           FROM purchase_items pi
           LEFT JOIN products pr ON pi.product_id = pr.product_id
           WHERE pi.purchase_id = ?";
           
    $stmt = $connect->prepare($sql);
    if(!$stmt) {
        throw new Exception("Database error: " . $connect->error);
    }
    
    $stmt->bind_param("i", $purchaseId);
    if(!$stmt->execute()) {
        throw new Exception("Error fetching purchase items: " . $stmt->error);
    }
    
    $itemsResult = $stmt->get_result();
    
    // Fetch all active suppliers
    $supplierSql = "SELECT id, company_name FROM suppliers WHERE active = 1 ORDER BY company_name ASC";
    $supplierResult = $connect->query($supplierSql);
    if(!$supplierResult) {
        throw new Exception("Error fetching suppliers: " . $connect->error);
    }
    
    // Fetch all active products
    $productSql = "SELECT product_id, name FROM products WHERE status = 'active' ORDER BY name ASC";
    $productResult = $connect->query($productSql);
    if(!$productResult) {
        throw new Exception("Error fetching products: " . $connect->error);
    }
    
    // Build HTML for edit form
    $html = '<div id="edit-purchase-messages"></div>';
    $html .= '<form id="editPurchaseForm" method="post">';
    $html .= '<input type="hidden" name="purchaseId" value="'.$purchaseId.'">';
    
    // Supplier and Date section
    $html .= '<div class="row">';
    $html .= '<div class="col-md-6">';
    $html .= '<div class="form-group">';
    $html .= '<label for="supplier">Supplier</label>';
    $html .= '<select class="form-control select2" id="supplier" name="supplier" required>';
    $html .= '<option value="">Select Supplier</option>';
    while($supplier = $supplierResult->fetch_assoc()) {
        $selected = ($supplier['id'] == $purchase['supplier_id']) ? 'selected' : '';
        $html .= '<option value="'.$supplier['id'].'" '.$selected.'>'.htmlspecialchars($supplier['company_name']).'</option>';
    }
    $html .= '</select>';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '<div class="col-md-6">';
    $html .= '<div class="form-group">';
    $html .= '<label for="purchaseDate">Purchase Date</label>';
    $html .= '<input type="date" class="form-control" id="purchaseDate" name="purchaseDate" value="'.date('Y-m-d', strtotime($purchase['purchase_date'])).'" required>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Products table
    $html .= '<div class="table-responsive">';
    $html .= '<table class="table table-bordered table-hover" id="purchaseItems">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th>Product</th>';
    $html .= '<th style="width: 120px;">Quantity</th>';
    $html .= '<th style="width: 120px;">Rate</th>';
    $html .= '<th style="width: 120px;">Amount</th>';
    $html .= '<th style="width: 50px;"></th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    
    $rowCount = 0;
    while($item = $itemsResult->fetch_assoc()) {
        $rowCount++;
        $html .= '<tr id="row'.$rowCount.'">';
        $html .= '<td>';
        $html .= '<select class="form-control product-select" name="productId[]" required>';
        $html .= '<option value="">Select Product</option>';
        $productResult->data_seek(0);
        while($product = $productResult->fetch_assoc()) {
            $selected = ($product['product_id'] == $item['product_id']) ? 'selected' : '';
            $html .= '<option value="'.$product['product_id'].'" '.$selected.'>'.htmlspecialchars($product['name']).'</option>';
        }
        $html .= '</select>';
        $html .= '</td>';
        $html .= '<td><input type="number" class="form-control quantity" name="quantity[]" value="'.$item['quantity'].'" min="0.01" step="0.01" required></td>';
        $html .= '<td><input type="number" class="form-control rate" name="rate[]" value="'.$item['rate'].'" min="0.01" step="0.01" required></td>';
        $html .= '<td><input type="number" class="form-control amount" name="amount[]" value="'.$item['total'].'" readonly></td>';
        $html .= '<td><button type="button" class="btn btn-danger btn-sm removeItem"><i class="glyphicon glyphicon-trash"></i></button></td>';
        $html .= '</tr>';
    }
    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '<button type="button" class="btn btn-primary btn-sm" onclick="addItemRow()"><i class="glyphicon glyphicon-plus"></i> Add Item</button>';
    $html .= '</div>';
    
    // Totals section
    $html .= '<div class="row" style="margin-top: 20px;">';
    $html .= '<div class="col-md-6">';
    $html .= '<div class="form-group">';
    $html .= '<label for="note">Note</label>';
    $html .= '<textarea class="form-control" id="note" name="note" rows="3">'.htmlspecialchars($purchase['note']).'</textarea>';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '<div class="col-md-6">';
    $html .= '<div class="form-group">';
    $html .= '<label for="subTotal">Sub Total</label>';
    $html .= '<input type="number" class="form-control text-right" id="subTotal" name="subTotal" value="'.$purchase['sub_total'].'" readonly>';
    $html .= '</div>';
    
    $html .= '<div class="form-group">';
    $html .= '<label for="vat">VAT (15%)</label>';
    $html .= '<input type="number" class="form-control text-right" id="vat" name="vat" value="'.$purchase['vat'].'" readonly>';
    $html .= '</div>';
    
    $html .= '<div class="form-group">';
    $html .= '<label for="withholdingTaxEnabled">Withholding Tax</label>';
    $html .= '<div class="checkbox">';
    $checked = $purchase['withholding_tax_enabled'] ? 'checked' : '';
    $html .= '<label><input type="checkbox" id="withholdingTaxEnabled" name="withholdingTaxEnabled" '.$checked.'> Enable 2% Withholding Tax</label>';
    $html .= '</div>';
    $html .= '<input type="number" class="form-control text-right" id="withholdingAmount" name="withholdingAmount" value="'.$purchase['withholding_tax_amount'].'" readonly>';
    $html .= '</div>';
    
    $html .= '<div class="form-group">';
    $html .= '<label for="grandTotal">Grand Total</label>';
    $html .= '<input type="number" class="form-control text-right" id="grandTotal" name="grandTotal" value="'.$purchase['grand_total'].'" readonly>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    // Add Save and Close buttons
    $html .= '<div class="row" style="margin-top: 20px;">';
    $html .= '<div class="col-md-12 text-right">';
    $html .= '<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>';
    $html .= '<button type="button" class="btn btn-success" id="saveChangesBtn">Save Changes</button>';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '</form>';
    
    $response['success'] = true;
    $response['html'] = $html;
    
} catch(Exception $e) {
    $response['success'] = false;
    $response['messages'] = $e->getMessage();
    error_log("Edit Purchase Error: " . $e->getMessage());
}

echo json_encode($response);

if (isset($connect)) {
    $connect->close();
} 