<?php
/**
 * Save 2FA Settings API
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
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$current = gcm_2fa_settings();
$current['enabled']             = !empty($input['enabled']);
$current['email_otp']           = !empty($input['email_otp']);
$current['admin_email']         = filter_var(trim($input['admin_email'] ?? ''), FILTER_VALIDATE_EMAIL) ?: $current['admin_email'];
$current['otp_expiry_minutes']  = max(5, min(30, (int)($input['otp_expiry_minutes']  ?? 10)));
$current['trusted_device_days'] = max(1, min(90, (int)($input['trusted_device_days'] ?? 30)));
$current['smtp_host']           = trim($input['smtp_host']      ?? $current['smtp_host']      ?? 'smtp.hostinger.com');
$current['smtp_port']           = max(1, min(65535, (int)($input['smtp_port'] ?? 587)));
$current['smtp_encryption']     = in_array($input['smtp_encryption'] ?? '', ['tls','ssl','none']) ? $input['smtp_encryption'] : 'tls';
$current['smtp_username']       = trim($input['smtp_username']   ?? $current['smtp_username']  ?? '');
$current['smtp_password']       = trim($input['smtp_password']   ?? $current['smtp_password']  ?? '');
$current['smtp_from_name']      = trim($input['smtp_from_name']  ?? $current['smtp_from_name'] ?? 'GCM Netting Solutions Admin');

if (gcm_save_2fa_settings($current)) {
    echo json_encode(['success' => true, 'message' => '2FA settings saved.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save settings. Check file permissions on data/ folder.']);
}
