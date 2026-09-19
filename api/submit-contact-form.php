<?php
/**
 * Contact Form Submission Handler
 * Saves to database and sends email notifications
 */

define('GCM_INIT', true);
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/email-helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get form data
$name = sanitize_input($_POST['name'] ?? '');
$email = sanitize_input($_POST['email'] ?? '');
$phone = sanitize_input($_POST['phone'] ?? '');
$service = sanitize_input($_POST['service'] ?? '');
$message = sanitize_input($_POST['message'] ?? '');

// Validate required fields
if (empty($name) || empty($email) || empty($phone) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

// Get client IP
$ip_address = get_client_ip();

// Insert into database
$db = Database::getInstance();
$result = $db->execute(
    "INSERT INTO contact_submissions (name, email, phone, service, message, ip_address, submitted_at) 
     VALUES (?, ?, ?, ?, ?, ?, NOW())",
    [$name, $email, $phone, $service, $message, $ip_address],
    'ssssss'
);

if ($result['success']) {
    // Prepare submission data for emails
    $submission = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'service' => $service,
        'message' => $message
    ];
    
    // Send notification emails
    send_contact_form_notification($submission);
    send_contact_form_acknowledgment($submission);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Thank you! Your message has been received. We will contact you within 24 hours.'
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Sorry, there was an error submitting your form. Please try again or call us directly.'
    ]);
}
