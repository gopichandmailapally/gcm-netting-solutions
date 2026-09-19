<?php
/**
 * Remove a Trusted Device
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
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$id    = (int)($input['id'] ?? 0);

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid device ID']);
    exit;
}

$db  = Database::getInstance();
$pdo = $db->getConnection();
gcm_2fa_init_tables($pdo);

$pdo->prepare("DELETE FROM admin_trusted_devices WHERE id=?")->execute([$id]);
echo json_encode(['success' => true]);
