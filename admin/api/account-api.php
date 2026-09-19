<?php
/**
 * Account Manager — AJAX API Endpoint
 * All mutations require an active admin session.
 */
ob_start();
define('GCM_INIT', true);
require_once '../../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Strict');
    session_name('GCM_ADMIN_SESSION');
    session_start();
}

header('Content-Type: application/json');

/* Auth guard */
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Not authenticated']);
    exit;
}

$adminId = (int)($_SESSION['admin_id'] ?? 0);

/* Parse input — support both FormData and JSON body */
$input = $_POST;
$raw   = file_get_contents('php://input');
if ($raw && empty($input)) {
    $input = json_decode($raw, true) ?? [];
}

$action = trim($input['action'] ?? '');

try {

require_once '../includes/account-manager.php';
$am = new AccountManager();

switch ($action) {

    /* ── Change password (initiates email confirmation) ── */
    case 'change_password':
        if (!$am->verifyCSRF('cp', $input['csrf_token'] ?? '')) {
            ob_end_clean(); echo json_encode(['ok' => false, 'error' => 'Security token mismatch. Refresh and try again.']); exit;
            break;
        }
        $result = $am->initiatePasswordChange(
            $adminId,
            $input['old_password']     ?? '',
            $input['new_password']     ?? '',
            $input['confirm_password'] ?? ''
        );
        ob_end_clean(); echo json_encode($result); exit;

    /* ── Change username (initiates email confirmation) ── */
    case 'change_username':
        if (!$am->verifyCSRF('cu', $input['csrf_token'] ?? '')) {
            ob_end_clean(); echo json_encode(['ok' => false, 'error' => 'Security token mismatch. Refresh and try again.']); exit;
            break;
        }
        $result = $am->initiateUsernameChange(
            $adminId,
            $input['current_password'] ?? '',
            strtolower(trim($input['new_username'] ?? ''))
        );
        ob_end_clean(); echo json_encode($result); exit;

    /* ── Create admin user (super_admin only) ──────────── */
    case 'create_user':
        if (!$am->verifyCSRF('add', $input['csrf_token'] ?? '')) {
            ob_end_clean(); echo json_encode(['ok' => false, 'error' => 'Security token mismatch. Refresh and try again.']); exit;
            break;
        }
        $result = $am->createAdminUser(
            $adminId,
            strtolower(trim($input['new_username'] ?? '')),
            $input['new_password'] ?? '',
            $input['new_email']    ?? '',
            $input['new_role']     ?? 'admin'
        );
        ob_end_clean(); echo json_encode($result); exit;

    /* ── Toggle user active/disabled (super_admin only) ── */
    case 'toggle_user':
        $result = $am->toggleUserActive($adminId, (int)($input['target_id'] ?? 0));
        ob_end_clean(); echo json_encode($result); exit;

    /* ── Delete admin user (super_admin only) ──────────── */
    case 'delete_user':
        $result = $am->deleteAdminUser(
            $adminId,
            (int)($input['target_id']      ?? 0),
            $input['confirm_password'] ?? ''
        );
        ob_end_clean(); echo json_encode($result); exit;

    /* ── Refresh CSRF token for a given form ────────────── */
    case 'refresh_csrf':
        $form  = preg_replace('/[^a-z_]/', '', $input['form'] ?? '');
        $token = $am->generateCSRF($form);
        ob_end_clean(); echo json_encode(['ok' => true, 'token' => $token]); exit;

    default:
        http_response_code(400);
        ob_end_clean(); echo json_encode(['ok' => false, 'error' => 'Unknown action']); exit;
}

} catch (\Throwable $e) {
    error_log('[account-api] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    while (ob_get_level() > 0) ob_end_clean();
    echo json_encode(['ok' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
