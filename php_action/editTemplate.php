<?php
require_once 'core.php';

if($_POST) {
    $valid['success'] = array('success' => false, 'messages' => array());

    $templateId = $_POST['templateId'];
    $letterCode = $_POST['letterCode'];
    $letterFor = $_POST['letterFor'];
    $location = $_POST['location'];
    $subject = $_POST['subject'];
    $letterContent = $_POST['letterContent'];

    $sql = "UPDATE letter_templates 
            SET letter_code = ?, 
                letter_for = ?, 
                location = ?, 
                letter_subject = ?, 
                letter_content = ? 
            WHERE id = ?";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param('sssssi', $letterCode, $letterFor, $location, $subject, $letterContent, $templateId);

    if($stmt->execute()) {
        $valid['success'] = true;
        $valid['messages'] = "Template updated successfully";
    } else {
        $valid['success'] = false;
        $valid['messages'] = "Error while updating template";
    }

    $stmt->close();
    $connect->close();

    echo json_encode($valid);
} 