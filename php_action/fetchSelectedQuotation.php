<?php
require_once 'core.php';
require_once 'db_connect.php';
require_once 'middleware.php';

if (!hasPermission('email_quotations')) {
    echo json_encode([
        'success' => false,
        'messages' => 'You do not have permission to email quotations'
    ]);
    exit();
}

if ($_POST) {
    $quotationId = $_POST['id'];
    
    // Fetch quotation details with client information
    $sql = "SELECT q.*, c.company_name, c.email as client_email
            FROM quotations q
            LEFT JOIN clients c ON q.client_id = c.id
            WHERE q.id = ?";
            
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $quotationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $quotation = $result->fetch_assoc();
    
    if ($quotation) {
        echo json_encode([
            'success' => true,
            'quotation_number' => $quotation['quotation_number'],
            'client_email' => $quotation['client_email'],
            'company_name' => $quotation['company_name']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'messages' => 'Quotation not found'
        ]);
    }
} 