<?php
/**
 * Review Submission Handler
 * Saves review as JSON to data/reviews/pending/ and emails admin
 */

define('GCM_INIT', true);
require_once '../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Honeypot spam check
if (!empty($_POST['website'] ?? '')) {
    echo json_encode(['success' => true, 'message' => 'Thank you for your review!']);
    exit;
}

// Collect & sanitise
$name        = trim(strip_tags($_POST['customer_name'] ?? ''));
$email       = trim($_POST['email'] ?? '');
$phone       = trim(preg_replace('/[^0-9]/', '', $_POST['phone'] ?? ''));
$service     = trim(strip_tags($_POST['service'] ?? ''));
$location    = trim(strip_tags($_POST['location'] ?? ''));
$rating      = (int)($_POST['rating'] ?? 0);
$review_text = trim(strip_tags($_POST['review_text'] ?? ''));

// Validate
if (strlen($name) < 2)           { echo json_encode(['success'=>false,'message'=>'Please enter your name.']); exit; }
if ($rating < 1 || $rating > 5)  { echo json_encode(['success'=>false,'message'=>'Please select a star rating.']); exit; }
if (empty($service))              { echo json_encode(['success'=>false,'message'=>'Please select the service you used.']); exit; }
if (strlen($review_text) < 20)   { echo json_encode(['success'=>false,'message'=>'Review must be at least 20 characters.']); exit; }
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success'=>false,'message'=>'Please enter a valid email address.']); exit;
}

// Rate limit: max 2 reviews per IP per day (simple file check)
$ip   = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$ipSafe = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $ip);

// Ensure directories exist with protection
$reviews_dir = dirname(__DIR__) . '/data/reviews';
$pending_dir = $reviews_dir . '/pending';
foreach ([$reviews_dir, $pending_dir] as $dir) {
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    if (!file_exists($dir . '/index.php')) {
        @file_put_contents($dir . '/index.php', "<?php header('Location: /'); exit;");
    }
}

// Check duplicate in same day by same IP
$todayPrefix = date('Ymd');
$dup = glob($pending_dir . '/' . $todayPrefix . '*_ip_' . $ipSafe . '.json');
if (count($dup) >= 2) {
    echo json_encode(['success'=>false,'message'=>'You have already submitted reviews today. Please try again tomorrow.']);
    exit;
}

// Build review object
$review = [
    'customer_name' => $name,
    'email'         => $email,
    'phone'         => $phone,
    'category'      => $service,
    'location'      => $location ?: 'Chennai',
    'rating'        => $rating,
    'review_text'   => $review_text,
    'status'        => 'pending',
    'source'        => 'user',
    'verified'      => false,
    'created_at'    => date('Y-m-d H:i:s'),
    'ip'            => $ip,
];

$slug     = preg_replace('/[^a-z0-9]+/', '-', strtolower($name));
$filename = $todayPrefix . '_' . time() . '_' . $slug . '_ip_' . $ipSafe . '.json';
$filepath = $pending_dir . '/' . $filename;

if (!file_put_contents($filepath, json_encode($review, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode(['success'=>false,'message'=>'Could not save your review. Please try again.']);
    exit;
}

// Email notification
send_new_review_email($review);

echo json_encode([
    'success' => true,
    'message' => 'Thank you, ' . htmlspecialchars($name) . '! Your review has been submitted and will appear on our website after approval.'
]);

function send_new_review_email($r) {
    $stars   = str_repeat('⭐', $r['rating']);
    $subject = "⭐ New " . $r['rating'] . "-Star Review from " . $r['customer_name'] . " – Pending Approval";
    $body = "
    <!DOCTYPE html><html><head>
    <style>
        body{font-family:Arial,sans-serif;line-height:1.6;color:#333;}
        .wrap{max-width:600px;margin:0 auto;}
        .hdr{background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;padding:25px;border-radius:8px 8px 0 0;}
        .body{background:#fafafa;padding:25px;border-radius:0 0 8px 8px;}
        .row{background:#fff;padding:12px 16px;border-radius:6px;margin:8px 0;}
        .lbl{font-weight:bold;color:#d97706;}
        .review-box{background:#fff;padding:20px;border-left:4px solid #f59e0b;margin:15px 0;border-radius:0 6px 6px 0;}
        .action-box{background:#fef3c7;padding:15px;border-radius:8px;margin-top:15px;}
        .btn{display:inline-block;background:#d97706;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;font-weight:bold;margin-top:10px;}
        .footer{text-align:center;color:#9ca3af;font-size:12px;padding:15px 0;}
    </style></head><body>
    <div class='wrap'>
        <div class='hdr'><h2>⭐ New Customer Review – Pending Approval</h2>
        <p>A customer has submitted a review on your website</p></div>
        <div class='body'>
            <div class='row'><span class='lbl'>Name: </span> " . htmlspecialchars($r['customer_name']) . "</div>
            <div class='row'><span class='lbl'>Rating: </span> $stars (" . $r['rating'] . "/5)</div>
            <div class='row'><span class='lbl'>Service: </span> " . htmlspecialchars($r['category']) . "</div>
            <div class='row'><span class='lbl'>Location: </span> " . htmlspecialchars($r['location']) . "</div>
            " . (!empty($r['email']) ? "<div class='row'><span class='lbl'>Email: </span> " . htmlspecialchars($r['email']) . "</div>" : "") . "
            " . (!empty($r['phone']) ? "<div class='row'><span class='lbl'>Phone: </span> " . htmlspecialchars($r['phone']) . "</div>" : "") . "
            <div class='review-box'><strong>Review:</strong><br><br>" . nl2br(htmlspecialchars($r['review_text'])) . "</div>
            <div class='action-box'>
                <strong>⚡ Action Required:</strong> Go to Admin Panel → Customer Management → Manage Reviews → <em>Pending</em> tab to Approve or Reject this review.<br>
                <a class='btn' href='https://gcmsafetynets.in/admin/pages/manage-reviews.php?tab=pending'>Review in Admin Panel →</a>
            </div>
        </div>
        <div class='footer'>GCM Netting Solutions &nbsp;|&nbsp; " . date('d M Y, h:i A') . "</div>
    </div></body></html>";

    return send_email('gopichandmailapally@gmail.com', $subject, $body);
}
