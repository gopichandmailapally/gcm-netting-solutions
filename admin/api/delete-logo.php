<?php
/**
 * Delete Logo API
 * Remove logo files
 */

ob_start(); // buffer stray output so JSON is never corrupted

define('GCM_INIT', true);
require_once '../../config/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

function jsonOut(array $data): void {
    ob_clean();
    echo json_encode($data);
    exit;
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    jsonOut(['success' => false, 'message' => 'Unauthorized']);
}

$input = json_decode(file_get_contents('php://input'), true);
$type = $input['type'] ?? '';

if (empty($type) || !in_array($type, ['header', 'footer', 'favicon'])) {
    jsonOut(['success' => false, 'message' => 'Invalid logo type: ' . htmlspecialchars($type)]);
}

try {
    $upload_dir = dirname(dirname(dirname(__FILE__))) . '/uploads/';
    
    if ($type === 'header') {
        $filename = 'logo.png';
    } elseif ($type === 'footer') {
        $filename = 'logo-footer.png';
    } else {
        $filename = 'favicon.png';
    }
    
    $filepath = $upload_dir . $filename;
    
    if (!file_exists($filepath)) {
        jsonOut(['success' => false, 'message' => 'Logo file not found on server']);
    }

    if (unlink($filepath)) {
        jsonOut(['success' => true, 'message' => 'Logo deleted successfully']);
    } else {
        jsonOut(['success' => false, 'message' => 'Failed to delete — check /uploads/ folder permissions']);
    }

} catch (Exception $e) {
    error_log('[delete-logo] ' . $e->getMessage());
    jsonOut(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
