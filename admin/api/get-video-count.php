<?php
/**
 * Get Video Count API
 */

define('GCM_INIT', true);
require_once '../../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$videos_dir = dirname(dirname(__DIR__)) . '/data/videos';
$count = 0;

if (is_dir($videos_dir)) {
    $files = glob($videos_dir . '/*.json');
    foreach ($files as $file) {
        $basename = basename($file);
        if ($basename !== 'stats.json') {
            $count++;
        }
    }
}

echo json_encode([
    'success' => true,
    'count' => $count
]);
