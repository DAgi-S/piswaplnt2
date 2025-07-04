<?php
header('Content-Type: application/json');
require_once __DIR__ . '/emailConfig.php';

// Connect to piswap database
$mysqli = new mysqli('localhost', 'root', '', 'piswap');
if ($mysqli->connect_errno) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

// Collect and sanitize input
$full_name = trim($_POST['full_name'] ?? '');
$phone_number = trim($_POST['phone_number'] ?? '');
$email = trim($_POST['email'] ?? '');
$address = trim($_POST['address'] ?? '');
$company_name = trim($_POST['company_name'] ?? '');
$company_address = trim($_POST['company_address'] ?? '');
$business_sector = trim($_POST['business_sector'] ?? '');
$number_users = intval($_POST['number_users'] ?? 0);

if (!$full_name || !$phone_number || !$email) {
    echo json_encode(['success' => false, 'message' => 'Full name, phone number, and email are required.']);
    exit;
}

$stmt = $mysqli->prepare("INSERT INTO client_registrations (full_name, phone_number, email, address, company_name, company_address, business_sector, number_users) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param('sssssssi', $full_name, $phone_number, $email, $address, $company_name, $company_address, $business_sector, $number_users);

if ($stmt->execute()) {
    // Send notification email to admin
    $subject = 'New Piswap Registration Interest';
    $message = "<h2>New Client Registration Interest</h2>"
        . "<ul>"
        . "<li><strong>Full Name:</strong> {$full_name}</li>"
        . "<li><strong>Phone Number:</strong> {$phone_number}</li>"
        . "<li><strong>Email:</strong> {$email}</li>"
        . "<li><strong>Address:</strong> {$address}</li>"
        . "<li><strong>Company Name:</strong> {$company_name}</li>"
        . "<li><strong>Company Address:</strong> {$company_address}</li>"
        . "<li><strong>Business Sector:</strong> {$business_sector}</li>"
        . "<li><strong>Number of Users:</strong> {$number_users}</li>"
        . "</ul>";
    sendNotificationEmail($subject, $message);

    // Send confirmation email to client
    $client_subject = 'Thank you for your interest in PiSwap!';
    $client_message = "<h2>Thank you for your interest in PiSwap!</h2>"
        . "<p>Dear {$full_name},</p>"
        . "<p>We have received your registration and appreciate your interest in our system. Our team will review your information, set up your PiSwap system, and get back to you as soon as possible.</p>"
        . "<p>If you have any questions, feel free to reply to this email.</p>"
        . "<p>Best regards,<br>The PiSwap Team</p><br><br>"
        . "<p>Salem Mohammmed</p>"
        . "<p>General Manager</p>"
        . "<p>+2519067895</p>"
        . "<p>www.lebawi.com</p>"
        . "<p>Swap the Old. Embrace the Pi.</p>";

    sendNotificationEmail($client_subject, $client_message, $email);

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save registration.']);
}
$stmt->close();
$mysqli->close(); 