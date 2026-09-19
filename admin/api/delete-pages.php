<?php
/**
 * Delete Pages API
 * Handle single and bulk page deletion
 */

define('ADMIN_ACCESS', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input    = json_decode(file_get_contents('php://input'), true);
$page_ids = $input['ids']    ?? [];
$pin      = $input['pin']    ?? '';
$reason   = $input['reason'] ?? '';

if (empty($page_ids) || !is_array($page_ids)) {
    echo json_encode(['success' => false, 'message' => 'No pages selected']);
    exit;
}

// ── Block bulk deletes — protected content must be deleted one at a time ──
if (count($page_ids) > 1) {
    echo json_encode([
        'success'   => false,
        'protected' => true,
        'message'   => 'Bulk delete is disabled for AI-protected service pages. Please delete pages one at a time — each requires your Security PIN and admin email approval.',
    ]);
    exit;
}

$db = Database::getInstance();

try {
    $id   = (int)$page_ids[0];
    $page = $db->fetchOne("SELECT id, page_slug, title FROM generated_pages WHERE id = ?", [$id], 'i');

    if (!$page) {
        echo json_encode(['success' => false, 'message' => 'Page not found']);
        exit;
    }

    $slug = $page['page_slug'] ?? ($page['slug'] ?? '');

    // ── AI Content Protection check ──────────────────────────────────────
    require_once dirname(__DIR__) . '/includes/ai-content-protection.php';
    $protection = new AIContentProtection();

    if ($protection->isProtected('service_page', $slug)) {
        if (empty($pin)) {
            echo json_encode([
                'success'   => false,
                'protected' => true,
                'message'   => 'This service page is protected by AI Content Security. Enter your Security PIN to request deletion approval.',
            ]);
            exit;
        }
        $title  = $page['title'] ?? $slug;
        $result = $protection->requestDeleteWithApproval(
            'service_page', $slug, $title, $pin, $reason,
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

    // Not protected — delete directly
    $file_path = dirname(dirname(dirname(__FILE__))) . '/generated-pages/' . $slug . '.php';
    if (file_exists($file_path)) unlink($file_path);
    $db->execute("DELETE FROM generated_pages WHERE id = ?", [$id], 'i');

    echo json_encode(['success' => true, 'deleted' => 1, 'message' => 'Page deleted successfully']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
