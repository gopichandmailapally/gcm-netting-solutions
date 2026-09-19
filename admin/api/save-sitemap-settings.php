<?php
/**
 * Save Sitemap Auto-Refresh Settings API
 */
define('GCM_INIT', true);
require_once '../../config/config.php';
require_once __DIR__ . '/sitemap-functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input        = json_decode(file_get_contents('php://input'), true) ?: [];
$auto_refresh = !empty($input['auto_refresh']);
$auto_ping    = !empty($input['auto_ping']);

$current = gcm_sitemap_settings();
$current['auto_refresh'] = $auto_refresh;
$current['auto_ping']    = $auto_ping;

$ok = gcm_save_sitemap_settings($current);
echo json_encode(['success' => $ok, 'settings' => $current]);
