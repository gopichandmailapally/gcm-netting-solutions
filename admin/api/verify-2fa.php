<?php
/**
 * Verify 2FA OTPs and complete login
 */
define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../includes/security.php';
require_once '../includes/2fa-helper.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure',   1);
    session_name('GCM_ADMIN_SESSION');
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Guard
if (empty($_SESSION['_2fa_pending']) || empty($_SESSION['_2fa_user_id']) || empty($_SESSION['_2fa_token'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
    exit;
}
if ((time() - ($_SESSION['_2fa_created_at'] ?? 0)) > 900) {
    echo json_encode(['success' => false, 'message' => 'Verification window expired. Please login again.']);
    exit;
}

$session_token  = $_SESSION['_2fa_token'];
$user_id        = (int)$_SESSION['_2fa_user_id'];
$remember       = ($_POST['remember_device'] ?? '0') === '1';

$cfg = gcm_2fa_settings();
$db  = Database::getInstance();
$pdo = $db->getConnection();
gcm_2fa_init_tables($pdo);

$otp_email = trim($_POST['otp_email'] ?? '');
$otp_wa    = trim($_POST['otp_wa']    ?? '');

// Verify Email OTP (if enabled)
if (!empty($cfg['email_otp'])) {
    if (strlen($otp_email) !== 6 || !ctype_digit($otp_email)) {
        echo json_encode(['success' => false, 'message' => 'Please enter the 6-digit Email OTP.', 'field' => 'email']);
        exit;
    }
    if (!gcm_verify_otp($pdo, $session_token, 'email', $otp_email)) {
        echo json_encode(['success' => false, 'message' => 'Email OTP is incorrect or expired.', 'field' => 'email']);
        exit;
    }
}

// Email OTP valid — complete login
$security = new AdminSecurity();
$security->initSecureSession($user_id);
$_SESSION['admin_username'] = $_SESSION['_2fa_username'] ?? '';
$_SESSION['admin_role']     = $_SESSION['_2fa_role']     ?? 'admin';

// Remember device
if ($remember) {
    gcm_create_trusted_device($pdo, (int)($cfg['trusted_device_days'] ?? 30));
}

// Clean up pending 2FA session vars
foreach (['_2fa_pending','_2fa_user_id','_2fa_username','_2fa_role','_2fa_token','_2fa_created_at'] as $k) {
    unset($_SESSION[$k]);
}

// Log
$security->logSecurityEvent($user_id, '2fa_login_success', '2FA verified' . ($remember ? ' — device trusted' : ''));

echo json_encode(['success' => true, 'redirect' => 'dashboard.php']);
