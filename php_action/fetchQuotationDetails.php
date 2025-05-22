<?php
// Prevent any unwanted output
ob_start();

require_once 'core.php';
require_once 'db_connect.php';
require_once 'middleware.php';

// Function to send JSON response
function sendJsonResponse($success, $data = null, $message = '') {
    // Clear any previous output
    ob_clean();
    
    // Set proper JSON header
    header('Content-Type: application/json');
    
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'messages' => $message
    ]);
    exit();
}

// Check permissions
if (!hasPermission('view_quotations')) {
    sendJsonResponse(false, null, 'You do not have permission to view quotations');
}

// Validate input
if (!isset($_POST['id']) || empty($_POST['id'])) {
    sendJsonResponse(false, null, 'Invalid quotation ID');
}

try {
    $quotationId = intval($_POST['id']);

    // Fetch quotation details with complete information
    $sql = "SELECT q.*, c.*, u.username as created_by_name
            FROM quotations q
            LEFT JOIN clients c ON q.client_id = c.id
            LEFT JOIN users u ON q.created_by = u.user_id
            WHERE q.id = ?";

    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error preparing quotation query: " . $connect->error);
    }

    $stmt->bind_param("i", $quotationId);
    if (!$stmt->execute()) {
        throw new Exception("Error executing quotation query: " . $stmt->error);
    }

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $quotation = $result->fetch_assoc();
        
        // Fetch quotation items with product details
        $sql = "SELECT qi.*, p.name as product_name, p.description as product_description
                 FROM quotation_items qi
                 LEFT JOIN products p ON qi.product_id = p.product_id
                 WHERE qi.quotation_id = ?";
        
        $itemsStmt = $connect->prepare($sql);
        if (!$itemsStmt) {
            throw new Exception("Error preparing items query: " . $connect->error);
        }

        $itemsStmt->bind_param("i", $quotationId);
        if (!$itemsStmt->execute()) {
            throw new Exception("Error executing items query: " . $itemsStmt->error);
        }

        $itemsResult = $itemsStmt->get_result();
        
        $items = array();
        while ($item = $itemsResult->fetch_assoc()) {
            $items[] = array(
                'product_name' => $item['product_name'],
                'description' => $item['product_description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total' => $item['total_price']
            );
        }
        
        // Format the response data with additional fields
        $data = array(
            'id' => $quotation['id'],
            'quotation_number' => $quotation['quotation_number'],
            'client_name' => $quotation['company_name'],
            'client_email' => $quotation['email'],
            'client_phone' => $quotation['phone'],
            'client_address' => $quotation['address'],
            'tin_number' => $quotation['tin_number'],
            'created_at' => date('d M Y', strtotime($quotation['created_at'])),
            'created_by' => $quotation['created_by_name'],
            'status' => $quotation['status'],
            'sub_total' => $quotation['sub_total'],
            'vat_amount' => $quotation['vat_amount'],
            'grand_total' => $quotation['grand_total'],
            'notes' => $quotation['note'],
            'items' => $items
        );
        
        sendJsonResponse(true, $data);
    } else {
        sendJsonResponse(false, null, 'Quotation not found');
    }

} catch (Exception $e) {
    sendJsonResponse(false, null, 'Error: ' . $e->getMessage());
} finally {
    // Clean up
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($itemsStmt)) {
        $itemsStmt->close();
    }
    if (isset($connect)) {
        $connect->close();
    }
} 