<?php
require_once 'core.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_POST['template_name'])) {
            throw new Exception("Template name is required");
        }

        $template_name = $_POST['template_name'];
        
        // Don't allow deletion of default template
        if ($template_name === 'default') {
            throw new Exception("Cannot delete default template");
        }
        
        $stmt = $connect->prepare("DELETE FROM print_settings WHERE template_name = ?");
        $stmt->bind_param('s', $template_name);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Template deleted successfully']);
            } else {
                throw new Exception("Template not found");
            }
        } else {
            throw new Exception("Error deleting template");
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
} 