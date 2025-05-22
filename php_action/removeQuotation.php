<?php
require_once 'core.php';
require_once 'db_connect.php';
require_once 'middleware.php';

if (!hasPermission('delete_quotations')) {
    echo json_encode([
        'success' => false,
        'messages' => 'You do not have permission to delete quotations'
    ]);
    exit();
}

if ($_POST) {
    $response = array();
    
    // Begin transaction
    $connect->begin_transaction();
    
    try {
        $quotationId = $_POST['id'];
        
        // Delete quotation items first (foreign key constraint)
        $sql = "DELETE FROM quotation_items WHERE quotation_id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("i", $quotationId);
        $stmt->execute();
        
        // Delete quotation
        $sql = "DELETE FROM quotations WHERE id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("i", $quotationId);
        $stmt->execute();
        
        // Commit transaction
        $connect->commit();
        
        $response['success'] = true;
        $response['messages'] = 'Quotation deleted successfully';
        
    } catch(Exception $e) {
        // Rollback transaction on error
        $connect->rollback();
        
        $response['success'] = false;
        $response['messages'] = 'Error while deleting quotation: ' . $e->getMessage();
    }
    
    echo json_encode($response);
} 