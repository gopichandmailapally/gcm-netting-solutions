<?php
/**
 * Save Google Search Console Settings
 */
define('GCM_INIT', true);
require_once '../../config/config.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];

$settings_file = dirname(dirname(dirname(__FILE__))) . '/data/gsc-settings.json';
$current = file_exists($settings_file) ? (json_decode(file_get_contents($settings_file), true) ?: []) : [];

$current['property_url']       = trim($input['property_url']       ?? $current['property_url']       ?? '');
$current['verification_code']  = trim($input['verification_code']  ?? $current['verification_code']  ?? '');
$current['auto_submit']        = !empty($input['auto_submit']);
$current['oauth_client_id']    = trim($input['oauth_client_id']    ?? $current['oauth_client_id']    ?? '');
$current['oauth_client_secret']= trim($input['oauth_client_secret']?? $current['oauth_client_secret']?? '');

$ok = file_put_contents($settings_file, json_encode($current, JSON_PRETTY_PRINT)) !== false;
echo json_encode(['success' => $ok]);
