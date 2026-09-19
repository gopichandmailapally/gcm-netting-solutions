<?php
/**
 * Submit FAQ Question API
 * Handles user question submissions from FAQ page
 */

define('GCM_INIT', true);
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/email-helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Validate inputs
    $user_name = trim($_POST['user_name'] ?? '');
    $user_email = trim($_POST['user_email'] ?? '');
    $question = trim($_POST['question'] ?? '');
    $category = trim($_POST['category'] ?? '');
    
    if (empty($user_name) || empty($user_email) || empty($question) || empty($category)) {
        throw new Exception('All fields are required');
    }
    
    if (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address');
    }
    
    if (strlen($question) < 10) {
        throw new Exception('Question must be at least 10 characters long');
    }
    
    // Insert question into database with category
    $result = $db->execute(
        "INSERT INTO faq_user_questions (user_name, user_email, question, category, status, submitted_at) 
         VALUES (?, ?, ?, ?, 'pending', NOW())",
        [$user_name, $user_email, $question, $category],
        'ssss'
    );
    
    if (!$result) {
        throw new Exception('Failed to submit question');
    }
    
    // Send notification to admin
    try {
        send_faq_question_notification([
            'user_name' => $user_name,
            'user_email' => $user_email,
            'question' => $question
        ]);
    } catch (Exception $e) {
        // Log email error but don't fail the request
        error_log('FAQ question notification email failed: ' . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Question submitted successfully! We will answer within 24 hours.'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
