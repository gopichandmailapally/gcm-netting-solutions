<?php
/**
 * Approve or Reject a pending review
 * Moves JSON from data/reviews/pending/ → data/reviews/ (approve)
 * or deletes it (reject). Admin-only endpoint.
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
$action   = $data['action'] ?? 'approve';

if (empty($filename) || !preg_match('/^[a-zA-Z0-9_\-\.]+\.json$/', $filename)) {
    echo json_encode(['success' => false, 'message' => 'Invalid filename']);
    exit;
}

$pending_dir  = dirname(__DIR__) . '/data/reviews/pending';
$approved_dir = dirname(__DIR__) . '/data/reviews';
$src          = $pending_dir . '/' . $filename;

if (!file_exists($src)) {
    echo json_encode(['success' => false, 'message' => 'Review file not found']);
    exit;
}

if ($action === 'approve') {
    $review = json_decode(file_get_contents($src), true);
    if (!$review) {
        echo json_encode(['success' => false, 'message' => 'Corrupt review file']);
        exit;
    }
    $review['status']      = 'approved';
    $review['verified']    = true;
    $review['approved_at'] = date('Y-m-d H:i:s');

    $dest = $approved_dir . '/' . $filename;
    if (file_put_contents($dest, json_encode($review, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        @unlink($src);
        echo json_encode(['success' => true, 'message' => 'Review approved and published on website']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save approved review']);
    }
} else {
    // reject — just delete
    @unlink($src);
    echo json_encode(['success' => true, 'message' => 'Review rejected and removed']);
}
