<?php
/**
 * Track Page View API
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
$page_title = $input['page_title'] ?? '';
$referrer = $input['referrer'] ?? '';

if (!$session_id) {
    echo json_encode(['success' => false]);
    exit;
}

$db = Database::getInstance();

try {
    $db->execute(
        "INSERT INTO page_views (session_id, page_url, page_title, referrer) VALUES (?, ?, ?, ?)",
        [$session_id, $page_url, $page_title, $referrer],
        'ssss'
    );
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
