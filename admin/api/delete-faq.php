<?php
/**
 * Delete FAQ API
 */

define('GCM_INIT', true);
require_once '../../config/config.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data     = json_decode(file_get_contents('php://input'), true);
$filename = $data['filename'] ?? '';
$pin      = $data['pin']      ?? '';
$reason   = $data['reason']   ?? '';

if (empty($filename)) {
    echo json_encode(['success' => false, 'message' => 'Filename required']);
    exit;
}

// Security: prevent directory traversal
if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
    echo json_encode(['success' => false, 'message' => 'Invalid filename']);
    exit;
}

$faqs_dir  = dirname(dirname(__DIR__)) . '/data/faqs';
$file_path = $faqs_dir . '/' . $filename;

if (!file_exists($file_path)) {
    echo json_encode(['success' => false, 'message' => 'File not found']);
    exit;
}

// ── AI Content Protection ────────────────────────────────────
require_once dirname(__DIR__) . '/includes/ai-content-protection.php';
$protection = new AIContentProtection();
$content_id = pathinfo($filename, PATHINFO_FILENAME);

if ($protection->isProtected('faq', $content_id)) {
    if (empty($pin)) {
        echo json_encode([
            'success'   => false,
            'protected' => true,
            'message'   => 'This FAQ is protected by AI Content Security. Enter your Security PIN to request deletion approval.',
        ]);
        exit;
    }
    $file_data = @json_decode(file_get_contents($file_path), true);
    $title     = $file_data['question'] ?? $content_id;
    $result    = $protection->requestDeleteWithApproval(
        'faq', $content_id, $title, $pin, $reason,
        $_SESSION['admin_id'] ?? 0
    );
    echo json_encode([
        'success'   => false,
        'protected' => true,
        'pending'   => $result['pending'] ?? false,
        'message'   => $result['error']   ?? $result['message'] ?? 'Request processed.',
    ]);
    exit;
}

// Not in protection table — delete directly
if (unlink($file_path)) {
    echo json_encode(['success' => true, 'message' => 'FAQ deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete file']);
}
