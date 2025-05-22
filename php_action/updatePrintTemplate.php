<?php
require_once 'core.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        $required_fields = [
            'template_name', 'page_size', 'orientation', 'margin_top', 
            'margin_right', 'margin_bottom', 'margin_left', 'font_family', 
            'font_size', 'header_alignment', 'logo_path', 'logo_width', 
            'footer_text'
        ];
        
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                throw new Exception("Field '$field' is required");
            }
        }

        $template_name = $_POST['template_name'];
        
        // Update template
        $sql = "UPDATE print_settings SET 
            page_size = ?, 
            orientation = ?, 
            margin_top = ?, 
            margin_right = ?, 
            margin_bottom = ?, 
            margin_left = ?,
            font_family = ?, 
            font_size = ?, 
            header_alignment = ?,
            logo_path = ?, 
            logo_width = ?, 
            footer_text = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE template_name = ?";
        
        $stmt = $connect->prepare($sql);
        $stmt->bind_param('sssssssssssss',
            $_POST['page_size'],
            $_POST['orientation'],
            $_POST['margin_top'],
            $_POST['margin_right'],
            $_POST['margin_bottom'],
            $_POST['margin_left'],
            $_POST['font_family'],
            $_POST['font_size'],
            $_POST['header_alignment'],
            $_POST['logo_path'],
            $_POST['logo_width'],
            $_POST['footer_text'],
            $template_name
        );
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Template updated successfully']);
            } else {
                throw new Exception("Template not found or no changes made");
            }
        } else {
            throw new Exception("Error updating template: " . $connect->error);
        }
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
} 