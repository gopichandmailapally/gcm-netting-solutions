<?php
/**
 * Generate Sitemap API — delegates to shared sitemap-functions.php
 */
define('GCM_INIT', true);
require_once '../../config/config.php';
require_once __DIR__ . '/sitemap-functions.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_name('GCM_ADMIN_SESSION');
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

@set_time_limit(300);
@ini_set('memory_limit', '256M');

$result = gcm_generate_sitemap();

// Ping search engines if requested (manual "Submit to Google" button)
if ($result['success'] && !empty($_POST['ping'])) {
    gcm_ping_search_engines();
    $result['pinged'] = true;
}

// Save last manual generation time in settings
if ($result['success']) {
    $s = gcm_sitemap_settings();
    $s['last_refresh']      = date('Y-m-d H:i:s');
    $s['last_refresh_urls'] = $result['total_urls'];
    gcm_save_sitemap_settings($s);
}

echo json_encode($result);
