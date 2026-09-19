<?php
/**
 * AI Content Protection — AJAX API Endpoint
 * Called by delete buttons across admin pages before any deletion is performed.
 *
 * Request (POST JSON or form):
 *   action         : 'check_protected' | 'request_delete' | 'protect'
 *   content_type   : page | blog | review | faq
 *   content_id     : unique identifier
 *   content_title  : human-readable title (optional)
 *   pin            : security PIN (for delete)
 *   reason         : deletion reason (for delete)
 *
 * Response: JSON { allowed:bool, protected:bool, error:string, message:string }
 */

define('GCM_INIT', true);
require_once '../../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_path', '/');
    session_name('GCM_ADMIN_SESSION');
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

require_once '../includes/ai-content-protection.php';
$protection = new AIContentProtection();
$admin_id   = $_SESSION['admin_id'] ?? 0;

$input = [];
$raw   = file_get_contents('php://input');
if ($raw) {
    $input = json_decode($raw, true) ?? [];
}
$input = array_merge($_POST, $input);

$action        = $input['action']        ?? '';
$content_type  = $input['content_type']  ?? '';
$content_id    = $input['content_id']    ?? '';
$content_title = $input['content_title'] ?? '';
$content_path  = $input['content_path']  ?? '';
$pin           = $input['pin']           ?? '';
$reason        = $input['reason']        ?? '';

switch ($action) {

    /* Check if a piece of content is currently protected */
    case 'check_protected':
        $is_protected = $protection->isProtected($content_type, $content_id);
        echo json_encode([
            'protected' => $is_protected,
            'has_pin'   => $protection->hasPinSet(),
            'settings'  => $protection->getAllSettings(),
        ]);
        break;

    /* Validate PIN + reason before deletion is executed by the caller */
    case 'request_delete':
        if (!$content_type || !$content_id) {
            echo json_encode(['allowed' => false, 'error' => 'Missing content_type or content_id']);
            break;
        }
        if (!$protection->isProtected($content_type, $content_id)) {
            echo json_encode(['allowed' => true, 'message' => 'Content is not protected — deletion allowed.']);
            break;
        }
        $result = $protection->requestDelete(
            $content_type, $content_id, $content_title,
            $pin, $reason, $admin_id
        );
        echo json_encode($result);
        break;

    /* Register new AI-generated content as protected */
    case 'protect':
        if (!$content_type || !$content_id) {
            echo json_encode(['success' => false, 'error' => 'Missing content_type or content_id']);
            break;
        }
        $ok = $protection->protect($content_type, $content_id, $content_title, $content_path, $admin_id);
        echo json_encode(['success' => (bool)$ok]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action']);
}
