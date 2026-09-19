<?php
/**
 * Page Deletion Protection System
 * Logs all deletion attempts and sends email alerts
 */

define('GCM_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../includes/email-notifications.php';

header('Content-Type: application/json');

// Check admin authentication
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$input    = json_decode(file_get_contents('php://input'), true) ?? [];
$action   = $input['action']   ?? ($_POST['action']   ?? '');
$filename = $input['filename'] ?? ($_POST['filename'] ?? '');
$pin      = $input['pin']      ?? ($_POST['pin']      ?? '');
$reason   = $input['reason']   ?? ($_POST['reason']   ?? '');

if ($action === 'delete_page') {
    if (empty($filename)) {
        echo json_encode(['error' => 'Filename required']);
        exit;
    }

    // Security check: Only allow deletion from generated-pages folder
    $protectedDir = __DIR__ . '/../../generated-pages/';
    $filePath     = $protectedDir . basename($filename);

    if (!file_exists($filePath)) {
        echo json_encode(['error' => 'File not found']);
        exit;
    }

    // ── AI Content Protection check ──────────────────────────────────────
    require_once __DIR__ . '/../includes/ai-content-protection.php';
    $protection = new AIContentProtection();
    $slug       = pathinfo($filename, PATHINFO_FILENAME);

    if ($protection->isProtected('service_page', $slug)) {
        if (empty($pin)) {
            echo json_encode([
                'success'   => false,
                'protected' => true,
                'message'   => 'This service page is protected by AI Content Security. Enter your Security PIN to request deletion approval.',
            ]);
            exit;
        }
        $result = $protection->requestDeleteWithApproval(
            'service_page', $slug, $slug, $pin, $reason,
            $_SESSION['admin_id'] ?? 0
        );
        echo json_encode([
            'success'   => false,
            'protected' => true,
            'pending'   => $result['pending'] ?? false,
            'message'   => $result['error'] ?? $result['message'] ?? 'Request processed.',
        ]);
        exit;
    }

    // Not protected — delete directly (log + email as before)
    $username   = $_SESSION['admin_username'] ?? 'Unknown';
    $ip_address = $_SERVER['REMOTE_ADDR'];
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if (!$conn->connect_error) {
            $stmt = $conn->prepare("INSERT INTO page_deletion_log (filename, deleted_by, ip_address, deleted_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param('sss', $filename, $username, $ip_address);
            $stmt->execute();
            $stmt->close();
            $conn->close();
        }
    } catch (Exception $e) { error_log("Deletion log error: " . $e->getMessage()); }
    sendPageDeletionAlert($filename, $username, $ip_address);

    if (unlink($filePath)) {
        echo json_encode(['success' => true, 'message' => 'Page deleted. Email notification sent.']);
    } else {
        echo json_encode(['error' => 'Failed to delete file']);
    }
    
} elseif ($action === 'get_deletion_log') {
    // Get recent deletion history
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            throw new Exception('Database connection failed');
        }
        
        $result = $conn->query("SELECT * FROM page_deletion_log ORDER BY deleted_at DESC LIMIT 50");
        $logs = [];
        
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        
        $conn->close();
        
        echo json_encode(['success' => true, 'logs' => $logs]);
        
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    
} else {
    echo json_encode(['error' => 'Invalid action']);
}
