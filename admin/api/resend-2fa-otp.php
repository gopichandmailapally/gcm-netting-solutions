<?php
/**
 * Resend 2FA OTPs
 */
define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../includes/2fa-helper.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_name('GCM_ADMIN_SESSION');
    session_start();
}

if (empty($_SESSION['_2fa_pending']) || empty($_SESSION['_2fa_token'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired.']);
    exit;
}

// Rate-limit: once per 60 seconds
$last_resend = $_SESSION['_2fa_last_resend'] ?? 0;
if ((time() - $last_resend) < 60) {
    $wait = 60 - (time() - $last_resend);
    echo json_encode(['success' => false, 'message' => "Please wait {$wait} seconds before resending."]);
    exit;
}

$cfg = gcm_2fa_settings();
$db  = Database::getInstance();
$pdo = $db->getConnection();
gcm_2fa_init_tables($pdo);

$results = gcm_dispatch_otps($pdo, $_SESSION['_2fa_token'], $cfg);
$_SESSION['_2fa_last_resend'] = time();

echo json_encode(['success' => true, 'sent' => $results]);
