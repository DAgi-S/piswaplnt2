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
        
        $stmt = $connect->prepare("SELECT * FROM print_settings WHERE template_name = ?");
        $stmt->bind_param('s', $template_name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Template not found");
        }
        
        $template = $result->fetch_assoc();
        
        echo json_encode(['success' => true, 'template' => $template]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
} 