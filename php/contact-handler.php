<?php
/**
 * GCM NETTING SOLUTIONS - Legacy Contact Form Handler
 * Now uses SMTP via send_email() (same as api/contact-handler.php)
 * Plain mail() with From:gmail.com was rejected by Hostinger's MTA.
 */

define('GCM_INIT', true);
require_once __DIR__ . '/../config/config.php';

$redirect_ok  = '../thank-you.html';
$redirect_err = '../contact.php?error=1';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    exit('Method not allowed');
}

// Honeypot
if (!empty($_POST['website'])) {
    header('Location: ' . $redirect_ok);
    exit;
}

$name    = strip_tags(trim($_POST['name']    ?? ''));
$email   = filter_var(trim($_POST['email']   ?? ''), FILTER_SANITIZE_EMAIL);
$phone   = strip_tags(trim($_POST['phone']   ?? ''));
$service = strip_tags(trim($_POST['service'] ?? 'General Inquiry'));
$message = strip_tags(trim($_POST['message'] ?? ''));

if (empty($name) || empty($email) || empty($phone) || empty($message)) {
    header('Location: ' . $redirect_err);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ' . $redirect_err);
    exit;
}

$subject = "New Quote Request from: {$name} | GCM Netting Solutions";
$body = "
<!DOCTYPE html><html><body style='font-family:Arial,sans-serif;color:#333;'>
<div style='max-width:600px;margin:0 auto;'>
  <div style='background:linear-gradient(135deg,#0066CC,#0052A3);color:white;padding:20px;border-radius:8px 8px 0 0;'>
    <h2>🔔 New Quote Request</h2><p>A new inquiry from the website</p>
  </div>
  <div style='background:#f8f9fa;padding:30px;border-radius:0 0 8px 8px;'>
    <p><strong>Name:</strong> "    . htmlspecialchars($name)    . "</p>
    <p><strong>Phone:</strong> "   . htmlspecialchars($phone)   . "</p>
    <p><strong>Email:</strong> "   . htmlspecialchars($email)   . "</p>
    <p><strong>Service:</strong> " . htmlspecialchars($service) . "</p>
    <div style='background:white;padding:15px;border-left:4px solid #0066CC;margin-top:15px;'>
      <strong>Message:</strong><br><br>" . nl2br(htmlspecialchars($message)) . "
    </div>
    <p style='margin-top:20px;'><a href='tel:" . $phone . "'>📞 Call: " . $phone . "</a> &nbsp;|&nbsp; <a href='https://wa.me/91" . $phone . "'>💬 WhatsApp</a></p>
  </div>
  <div style='text-align:center;padding:15px;color:#666;font-size:12px;'>
    Submitted: " . date('d M Y, h:i A') . " | IP: " . ($_SERVER['REMOTE_ADDR'] ?? '') . "
  </div>
</div>
</body></html>";

send_email('gopichandmailapally@gmail.com', $subject, $body, $email, $name);

header('Location: ' . $redirect_ok);
exit;
?>
