<?php
/**
 * FAQ Question Submission Handler
 * Saves user questions for admin to answer
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
$user_name = sanitize_input($_POST['user_name'] ?? 'Anonymous');
$user_email = sanitize_input($_POST['user_email'] ?? '');
$question = sanitize_input($_POST['question'] ?? '');

// Validate required fields
if (empty($question)) {
    echo json_encode(['success' => false, 'message' => 'Please enter your question.']);
    exit;
}

// Validate email if provided
if (!empty($user_email) && !filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

// Insert into database
$db = Database::getInstance();
$result = $db->execute(
    "INSERT INTO faq_user_questions (user_name, user_email, question, status, submitted_at) 
     VALUES (?, ?, ?, 'pending', NOW())",
    [$user_name, $user_email, $question],
    'sss'
);

if ($result['success']) {
    // Prepare question data for email
    $questionData = [
        'user_name' => $user_name,
        'user_email' => $user_email,
        'question' => $question
    ];
    
    // Send notification to admin
    send_faq_question_notification($questionData);
    
    $response_message = 'Thank you for your question! Our team will answer it soon.';
    if (!empty($user_email)) {
        $response_message .= ' We will notify you by email when we have an answer.';
    }
    
    echo json_encode([
        'success' => true, 
        'message' => $response_message
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Sorry, there was an error submitting your question. Please try again or contact us directly.'
    ]);
}
