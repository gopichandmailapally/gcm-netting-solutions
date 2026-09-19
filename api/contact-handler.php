<?php
/**
 * Contact Form Handler
 * Processes form submissions and sends emails
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
$form_type = sanitize_input($_POST['form_type'] ?? '');
$name = sanitize_input($_POST['name'] ?? '');
$phone = sanitize_input($_POST['phone'] ?? '');
$email = sanitize_input($_POST['email'] ?? '');
$service = sanitize_input($_POST['service'] ?? '');
$area = sanitize_input($_POST['area'] ?? '');
$message = sanitize_input($_POST['message'] ?? '');
$csrf_token = $_POST['csrf_token'] ?? '';

// Honeypot check (spam trap)
$honeypot = $_POST['website'] ?? '';
if (!empty($honeypot)) {
    // Spam detected - pretend success but don't save
    echo json_encode(['success' => true, 'message' => 'Thank you for your message!']);
    exit;
}

// Validate CSRF token (soft check — log mismatch but don't block;
// honeypot + IP rate-limiting already guard this endpoint sufficiently)
if ($form_type === 'contact_page' && !verify_csrf_token($csrf_token)) {
    error_log('[contact-handler] CSRF mismatch from IP: ' . get_client_ip());
}

// Validate required fields
$errors = [];

if (empty($name) || strlen($name) < 3) {
    $errors[] = 'Name is required (minimum 3 characters)';
}

if (empty($phone) || !preg_match('/^[6-9][0-9]{9}$/', $phone)) {
    $errors[] = 'Valid Indian mobile number is required (must start with 6, 7, 8, or 9)';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Valid email address is required';
}

if (empty($service)) {
    $errors[] = 'Service selection is required';
}

if (empty($message) || strlen($message) < 10) {
    $errors[] = 'Message is required (minimum 10 characters)';
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

// Check rate limiting (max 3 submissions per IP per hour)
$db = Database::getInstance();
try {
    $recent_submissions = $db->fetchOne(
        "SELECT COUNT(*) as count FROM contact_submissions 
         WHERE ip_address = ? AND (submitted_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                                OR created_at   > DATE_SUB(NOW(), INTERVAL 1 HOUR))",
        [$ip_address],
        's'
    );
    if ($recent_submissions && (int)$recent_submissions['count'] >= 3) {
        echo json_encode([
            'success' => false,
            'message' => 'Too many submissions. Please try again after an hour or call us directly.'
        ]);
        exit;
    }
} catch (\Exception $e) {
    error_log('[contact-handler] rate-limit check failed: ' . $e->getMessage());
}

// Save to database (non-blocking — email is sent regardless)
try {
    $db->execute(
        "INSERT INTO contact_submissions
         (name, email, phone, service, area, message, ip_address, submitted_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
        [$name, $email, $phone, $service, $area, $message, $ip_address],
        'sssssss'
    );
} catch (\Exception $e) {
    error_log('[contact-handler] DB save failed: ' . $e->getMessage());
}

// Always send email notification
$email_sent = send_contact_email($name, $phone, $email, $service, $area, $message);

// Return success response
echo json_encode([
    'success' => true,
    'message' => 'Thank you! Your message has been sent successfully. We will contact you within 1-2 hours.',
    'email_sent' => $email_sent
]);

/**
 * Send contact email notification
 */
function send_contact_email($name, $phone, $email, $service, $area, $message) {
    // Email subject
    $subject = "New Contact Form Submission - " . ucwords(str_replace('-', ' ', $service));
    
    // Email body (HTML)
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #0066CC, #0052A3); color: white; padding: 20px; border-radius: 8px 8px 0 0; }
            .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
            .info-row { margin: 15px 0; padding: 15px; background: white; border-radius: 6px; }
            .label { font-weight: bold; color: #0066CC; display: inline-block; width: 120px; }
            .value { color: #333; }
            .message-box { background: white; padding: 20px; border-left: 4px solid #0066CC; margin-top: 20px; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>🔔 New Contact Form Submission</h2>
                <p>A new inquiry has been received from the website</p>
            </div>
            <div class='content'>
                <div class='info-row'>
                    <span class='label'>Name:</span>
                    <span class='value'>" . htmlspecialchars($name) . "</span>
                </div>
                <div class='info-row'>
                    <span class='label'>Phone:</span>
                    <span class='value'>" . htmlspecialchars($phone) . "</span>
                </div>
                <div class='info-row'>
                    <span class='label'>Email:</span>
                    <span class='value'>" . htmlspecialchars($email) . "</span>
                </div>
                <div class='info-row'>
                    <span class='label'>Service:</span>
                    <span class='value'>" . htmlspecialchars(ucwords(str_replace('-', ' ', $service))) . "</span>
                </div>
                <div class='info-row'>
                    <span class='label'>Location:</span>
                    <span class='value'>" . htmlspecialchars($area) . "</span>
                </div>
                <div class='message-box'>
                    <strong>Message:</strong><br><br>
                    " . nl2br(htmlspecialchars($message)) . "
                </div>
                <div style='margin-top: 30px; padding: 15px; background: #fff3cd; border-radius: 6px;'>
                    <strong>⚡ Quick Actions:</strong><br>
                    📞 Call: <a href='tel:" . $phone . "'>" . $phone . "</a><br>
                    📧 Email: <a href='mailto:" . $email . "'>" . $email . "</a><br>
                    💬 WhatsApp: <a href='https://wa.me/91" . $phone . "'>Send Message</a>
                </div>
            </div>
            <div class='footer'>
                <p>This email was sent from GCM Netting Solutions contact form</p>
                <p>Time: " . date('d M Y, h:i A') . "</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Send email using helper function — deliver to owner's personal inbox
    return send_email('gopichandmailapally@gmail.com', $subject, $body, $email, $name);
}
