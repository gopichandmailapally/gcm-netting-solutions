<?php
/**
 * Track Visitor Action API
 * Records custom visitor actions/events
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
$action_type = $input['action_type'] ?? '';
$action_data = $input['action_data'] ?? '';
$page_url = $input['page_url'] ?? '';

if (!$session_id || !$action_type) {
    echo json_encode(['success' => false]);
    exit;
}

$db = Database::getInstance();

try {
    $db->execute(
        "INSERT INTO visitor_actions (session_id, action_type, action_data, page_url) VALUES (?, ?, ?, ?)",
        [$session_id, $action_type, $action_data, $page_url],
        'ssss'
    );
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false]);
}
