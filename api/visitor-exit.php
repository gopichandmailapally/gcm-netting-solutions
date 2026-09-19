<?php
/**
 * Visitor Exit API
 * Marks visitor as offline when they leave
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
$time_spent = (int)($input['time_spent'] ?? 0);
$scroll_depth = (int)($input['scroll_depth'] ?? 0);

if (!$session_id) {
    exit;
}

$db = Database::getInstance();

try {
    $db->execute(
        "UPDATE visitor_tracking SET 
         is_online = 0,
         total_time_spent = ?,
         last_activity = DATE_SUB(NOW(), INTERVAL 6 MINUTE)
         WHERE session_id = ?",
        [$time_spent, $session_id],
        'is'
    );
} catch (Exception $e) {
    // Silent fail for beacon
}
