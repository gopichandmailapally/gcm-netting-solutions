<?php
/**
 * Delete Video API
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

$data = json_decode(file_get_contents('php://input'), true);
$filename = $data['filename'] ?? '';

if (empty($filename)) {
    echo json_encode(['success' => false, 'message' => 'Missing filename']);
    exit;
}

// Security: prevent directory traversal
if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
    echo json_encode(['success' => false, 'message' => 'Invalid filename']);
    exit;
}

$videos_dir = dirname(dirname(__DIR__)) . '/data/videos';
$video_file = $videos_dir . '/' . $filename;

if (!file_exists($video_file)) {
    echo json_encode(['success' => false, 'message' => 'Video not found']);
    exit;
}

try {
    unlink($video_file);
    
    echo json_encode([
        'success' => true,
        'message' => 'Video deleted successfully!'
    ]);
    
} catch (Exception $e) {
    error_log('Delete Video Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error deleting video: ' . $e->getMessage()
    ]);
}
