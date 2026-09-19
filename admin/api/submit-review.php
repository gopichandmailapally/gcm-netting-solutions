<?php
/**
 * Submit Review from Website (User Submission)
 */

define('GCM_INIT', true);
require_once '../../config/config.php';

header('Content-Type: application/json');

// Get form data
$customer_name = trim($_POST['customer_name'] ?? '');
$rating = (int)($_POST['rating'] ?? 0);
$category = trim($_POST['category'] ?? 'Overall Experience');
$review_text = trim($_POST['review_text'] ?? '');
$location = trim($_POST['location'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');

// Validation
if (empty($customer_name) || empty($review_text) || $rating < 1 || $rating > 5) {
    echo json_encode([
        'success' => false,
        'message' => 'Please fill all required fields and select a rating.'
    ]);
    exit;
}

if (strlen($review_text) < 20) {
    echo json_encode([
        'success' => false,
        'message' => 'Review must be at least 20 characters long.'
    ]);
    exit;
}

try {
    $reviews_dir = dirname(dirname(__DIR__)) . '/data/reviews';
    if (!file_exists($reviews_dir)) {
        mkdir($reviews_dir, 0755, true);
    }
    
    $pending_dir = $reviews_dir . '/pending';
    if (!file_exists($pending_dir)) {
        mkdir($pending_dir, 0755, true);
    }
    
    $timestamp = time();
    $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $customer_name)) . '-' . $timestamp;
    
    $review = [
        'id' => $timestamp,
        'customer_name' => htmlspecialchars($customer_name),
        'rating' => $rating,
        'category' => htmlspecialchars($category),
        'review_text' => htmlspecialchars($review_text),
        'location' => htmlspecialchars($location),
        'email' => htmlspecialchars($email),
        'phone' => htmlspecialchars($phone),
        'created_at' => date('Y-m-d H:i:s'),
        'status' => 'pending',
        'source' => 'website_submission',
        'verified' => false,
        'helpful_count' => 0,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    // Save as pending
    $review_file = $pending_dir . '/' . $slug . '.json';
    file_put_contents($review_file, json_encode($review, JSON_PRETTY_PRINT));
    
    // Send email notification to admin
    $admin_email = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'admin@gcmsafetynets.in';
    $subject = "New Review Submission - $rating Stars from $customer_name";
    $message = "
    <h2>New Review Awaiting Approval</h2>
    <p><strong>Customer:</strong> $customer_name</p>
    <p><strong>Rating:</strong> $rating/5 stars</p>
    <p><strong>Category:</strong> $category</p>
    <p><strong>Location:</strong> $location</p>
    <p><strong>Email:</strong> $email</p>
    <p><strong>Phone:</strong> $phone</p>
    <p><strong>Review:</strong></p>
    <p>$review_text</p>
    <p><a href='" . SITE_URL . "/admin/pages/manage-reviews.php'>Manage Reviews</a></p>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: GCM Netting Solutions <noreply@gcmsafetynets.in>\r\n";
    
    @mail($admin_email, $subject, $message, $headers);
    
    echo json_encode([
        'success' => true,
        'message' => 'Thank you for your review! It will be published after approval.'
    ]);
    
} catch (Exception $e) {
    error_log('Review Submission Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error submitting review. Please try again.'
    ]);
}
