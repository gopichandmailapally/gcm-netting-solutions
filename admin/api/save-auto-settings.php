<?php
/**
 * Save Auto-Generation Settings
 */

define('ADMIN_ACCESS', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$daily_blogs = max(0, min(20, (int)($_POST['daily_blogs'] ?? 2)));
$daily_reviews = max(0, min(50, (int)($_POST['daily_reviews'] ?? 4)));

$db = Database::getInstance();

$result = $db->execute("
    UPDATE ai_content_settings 
    SET daily_blogs_count = ?, daily_reviews_count = ? 
    WHERE id = 1
", [$daily_blogs, $daily_reviews], 'ii');

echo json_encode([
    'success' => $result['success'],
    'message' => $result['success'] ? 'Settings saved successfully' : 'Failed to save settings'
]);
