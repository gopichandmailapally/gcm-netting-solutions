<?php
/**
 * Visitor Heartbeat API
 * Updates visitor activity to show they're still online
 */

define('GCM_INIT', true);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);

$session_id = $input['session_id'] ?? null;
$page_url = $input['page_url'] ?? '';
$time_spent = (int)($input['time_spent'] ?? 0);
$scroll_depth = (int)($input['scroll_depth'] ?? 0);

if (!$session_id) {
    echo json_encode(['success' => false]);
    exit;
}

$db = Database::getInstance();

try {
    // Update visitor status
    $db->execute(
        "UPDATE visitor_tracking SET 
         current_page = ?,
         last_activity = NOW(),
         is_online = 1,
         total_time_spent = ?
         WHERE session_id = ?",
        [$page_url, $time_spent, $session_id],
        'sis'
    );
    
    // Update last page view with time spent
    $db->execute(
        "UPDATE page_views SET 
         time_spent = ?,
         scroll_depth = ?
         WHERE session_id = ? 
         AND page_url = ?
         ORDER BY timestamp DESC LIMIT 1",
        [$time_spent, $scroll_depth, $session_id, $page_url],
        'iiss'
    );
    
    echo json_encode(['success' => true, 'time' => $time_spent]);
} catch (Exception $e) {
    echo json_encode(['success' => false]);
}
