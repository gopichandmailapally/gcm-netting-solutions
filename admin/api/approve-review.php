<?php
/**
 * Approve or Reject Review
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
$action = $data['action'] ?? ''; // 'approve' or 'reject'

if (empty($filename) || empty($action)) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

// Security
if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
    echo json_encode(['success' => false, 'message' => 'Invalid filename']);
    exit;
}

$reviews_dir = dirname(dirname(__DIR__)) . '/data/reviews';
$pending_dir = $reviews_dir . '/pending';
$pending_file = $pending_dir . '/' . $filename;

if (!file_exists($pending_file)) {
    echo json_encode(['success' => false, 'message' => 'Review not found']);
    exit;
}

try {
    $review = json_decode(file_get_contents($pending_file), true);
    
    if ($action === 'approve') {
        // Move to approved reviews
        $review['status'] = 'approved';
        $review['approved_at'] = date('Y-m-d H:i:s');
        $review['approved_by'] = $_SESSION['admin_username'] ?? 'admin';
        
        $approved_file = $reviews_dir . '/' . $filename;
        file_put_contents($approved_file, json_encode($review, JSON_PRETTY_PRINT));
        unlink($pending_file);
        
        echo json_encode([
            'success' => true,
            'message' => 'Review approved and published!'
        ]);
    } else if ($action === 'reject') {
        // Delete pending review
        unlink($pending_file);
        
        echo json_encode([
            'success' => true,
            'message' => 'Review rejected and deleted.'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
