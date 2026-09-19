<?php
/**
 * Delete an approved review (from data/reviews/)
 * Admin-only endpoint.
 */

define('GCM_INIT', true);
require_once '../config/config.php';

header('Content-Type: application/json');

// Admin-only
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data     = json_decode(file_get_contents('php://input'), true);
$filename = basename($data['filename'] ?? '');

if (empty($filename) || !preg_match('/^[a-zA-Z0-9_\-\.]+\.json$/', $filename)) {
    echo json_encode(['success' => false, 'message' => 'Invalid filename']);
    exit;
}

$reviews_dir = dirname(__DIR__) . '/data/reviews';
$filepath    = $reviews_dir . '/' . $filename;

if (!file_exists($filepath)) {
    echo json_encode(['success' => false, 'message' => 'Review not found']);
    exit;
}

if (@unlink($filepath)) {
    echo json_encode(['success' => true, 'message' => 'Review deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete review']);
}
