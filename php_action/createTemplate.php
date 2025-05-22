<?php
require_once 'core.php';

if($_POST) {
    $valid['success'] = array('success' => false, 'messages' => array());

    // Get POST data
    $letterCode = trim($_POST['letterCode']);
    $letterFor = trim($_POST['letterFor']);
    $location = trim($_POST['location']);
    $subject = trim($_POST['subject']);
    $letterContent = trim($_POST['letterContent']);

    // Server-side validation
    $errors = array();

    // Validate Letter Code
    if(empty($letterCode)) {
        $errors[] = "Letter Code is required";
    } elseif(strlen($letterCode) > 50) {
        $errors[] = "Letter Code cannot exceed 50 characters";
    }

    // Validate Letter For
    if(empty($letterFor)) {
        $errors[] = "Letter For is required";
    } elseif(strlen($letterFor) > 100) {
        $errors[] = "Letter For cannot exceed 100 characters";
    }

    // Validate Location
    if(empty($location)) {
        $errors[] = "Location is required";
    } elseif(strlen($location) > 100) {
        $errors[] = "Location cannot exceed 100 characters";
    }

    // Validate Subject
    if(empty($subject)) {
        $errors[] = "Subject is required";
    } elseif(strlen($subject) > 255) {
        $errors[] = "Subject cannot exceed 255 characters";
    }

    // Validate Letter Content
    if(empty($letterContent)) {
        $errors[] = "Letter Content is required";
    }

    // Check for duplicate letter code
    $checkSql = "SELECT id FROM letter_templates WHERE letter_code = ?";
    $checkStmt = $connect->prepare($checkSql);
    $checkStmt->bind_param("s", $letterCode);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if($result->num_rows > 0) {
        $errors[] = "Letter Code already exists";
    }
    $checkStmt->close();

    // If no errors, proceed with insertion
    if(empty($errors)) {
        $sql = "INSERT INTO letter_templates (letter_code, letter_for, location, letter_subject, letter_content) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("sssss", $letterCode, $letterFor, $location, $subject, $letterContent);

        if($stmt->execute()) {
            $valid['success'] = true;
            $valid['messages'] = "Template added successfully";
        } else {
            $valid['success'] = false;
            $valid['messages'] = "Error while adding template";
        }

        $stmt->close();
    } else {
        $valid['success'] = false;
        $valid['messages'] = implode("<br>", $errors);
    }

    $connect->close();
    echo json_encode($valid);
} 