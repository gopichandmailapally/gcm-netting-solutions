<?php
/**
 * Test 2FA OTP Delivery — sends a test OTP and returns result
 */
define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../includes/2fa-helper.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_name('GCM_ADMIN_SESSION');
    session_start();
}
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input   = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$channel = $input['channel'] ?? ''; // 'email' or 'whatsapp'
$cfg     = gcm_2fa_settings();
$otp     = gcm_generate_otp();

if ($channel === 'email') {
    if (empty($cfg['admin_email'])) {
        echo json_encode(['success' => false, 'message' => 'Admin email not configured in settings.']);
        exit;
    }
    $method = (!empty($cfg['smtp_username']) && !empty($cfg['smtp_password'])) ? 'SMTP' : 'PHP mail()';
    $ok = gcm_send_email_otp($cfg['admin_email'], $otp, (int)($cfg['otp_expiry_minutes'] ?? 10));
    echo json_encode([
        'success' => $ok,
        'method'  => $method,
        'message' => $ok
            ? "✅ [{$method}] Test OTP {$otp} sent to {$cfg['admin_email']} — check your inbox"
            : "❌ [{$method}] Send failed — " . ($method === 'SMTP'
                ? 'check SMTP credentials (host/port/username/password)'
                : 'mail() failed — configure SMTP above for reliable delivery'),
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Specify channel: email or whatsapp']);
}
