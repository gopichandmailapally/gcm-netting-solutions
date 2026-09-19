<?php
/**
 * Review Submission Handler
 * Processes customer review submissions
 */

define('GCM_INIT', true);
require_once '../config/config.php';
require_once '../config/database.php';

// Set JSON header
header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get form data
$name = sanitize_input($_POST['name'] ?? '');
$phone = sanitize_input($_POST['phone'] ?? '');
$email = sanitize_input($_POST['email'] ?? '');
$service_id = (int)($_POST['service_id'] ?? 0);
$area_id = (int)($_POST['area_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$review_text = sanitize_input($_POST['review_text'] ?? '');
$csrf_token = $_POST['csrf_token'] ?? '';

// Honeypot check
$honeypot = $_POST['website'] ?? '';
if (!empty($honeypot)) {
    echo json_encode(['success' => true, 'message' => 'Thank you for your review!']);
    exit;
}

// Validate CSRF token
if (!verify_csrf_token($csrf_token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh and try again.']);
    exit;
}

// Validate required fields
$errors = [];

if (empty($name) || strlen($name) < 3) {
    $errors[] = 'Name is required (minimum 3 characters)';
}

if (empty($phone) || !preg_match('/^[6-9][0-9]{9}$/', $phone)) {
    $errors[] = 'Valid Indian mobile number is required (must start with 6, 7, 8, or 9)';
}

if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Valid email address is required';
}

if ($service_id <= 0) {
    $errors[] = 'Service selection is required';
}

if ($rating < 1 || $rating > 5) {
    $errors[] = 'Rating is required (1-5 stars)';
}

if (empty($review_text) || strlen($review_text) < 20) {
    $errors[] = 'Review is required (minimum 20 characters)';
}

// If validation fails
if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => implode('. ', $errors)
    ]);
    exit;
}

// Get client IP
$ip_address = get_client_ip();

// Check rate limiting (max 2 reviews per IP per day)
$db = Database::getInstance();
$recent_reviews = $db->fetchOne(
    "SELECT COUNT(*) as count FROM reviews 
     WHERE ip_address = ? AND submitted_at > DATE_SUB(NOW(), INTERVAL 1 DAY)",
    [$ip_address],
    's'
);

if ($recent_reviews && $recent_reviews['count'] >= 2) {
    echo json_encode([
        'success' => false,
        'message' => 'You have already submitted reviews recently. Please try again tomorrow.'
    ]);
    exit;
}

// Save to database
$result = $db->execute(
    "INSERT INTO reviews 
    (customer_name, customer_email, customer_phone, rating, review_text, service_id, area_id, is_verified, is_published, ip_address, submitted_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0, ?, NOW())",
    [
        $name,
        $email,
        $phone,
        $rating,
        $review_text,
        $service_id,
        $area_id > 0 ? $area_id : null,
        $ip_address
    ],
    'sssisiss'
);

if (!$result['success']) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to submit your review. Please try again.'
    ]);
    exit;
}

// Send notification email to admin
send_review_notification($name, $phone, $rating, $review_text);

// Return success response
echo json_encode([
    'success' => true,
    'message' => 'Thank you for your review! It will be published after verification (usually within 24 hours).'
]);

/**
 * Send review notification email
 */
function send_review_notification($name, $phone, $rating, $review_text) {
    $subject = "New Review Submitted - $rating Stars";
    
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #FFA500; color: white; padding: 20px; border-radius: 8px 8px 0 0; }
            .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
            .rating { font-size: 24px; color: #FFA500; }
            .review-box { background: white; padding: 20px; border-left: 4px solid #FFA500; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>⭐ New Customer Review</h2>
            </div>
            <div class='content'>
                <p><strong>Customer Name:</strong> " . htmlspecialchars($name) . "</p>
                <p><strong>Phone:</strong> " . htmlspecialchars($phone) . "</p>
                <p><strong>Rating:</strong> <span class='rating'>" . str_repeat('⭐', $rating) . "</span></p>
                <div class='review-box'>
                    <strong>Review:</strong><br><br>
                    " . nl2br(htmlspecialchars($review_text)) . "
                </div>
                <p style='margin-top: 30px; padding: 15px; background: #fff3cd; border-radius: 6px;'>
                    <strong>Action Required:</strong> Login to admin panel to verify and publish this review.
                </p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    send_email(COMPANY_EMAIL, $subject, $body);
}
