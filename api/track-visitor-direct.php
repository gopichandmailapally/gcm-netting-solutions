<?php
/**
 * Ultra-Simple Visitor Tracker - Direct MySQL connection
 * No dependencies, no fancy code, just works
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Database credentials (hardcoded to avoid config issues)
$db_host = 'localhost';
$db_user = 'gcmsafetynets_user';
$db_pass = 't856zxMjLey8bpU5';
$db_name = 'gcmsafetynets_db';

try {
    // Get input
    $input = json_decode(file_get_contents('php://input'), true);
    $session_id = $input['session_id'] ?? null;
    
    if (!$session_id) {
        echo json_encode(['success' => false, 'error' => 'No session ID']);
        exit;
    }
    
    // Connect to database
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'error' => 'DB connection failed: ' . $conn->connect_error]);
        exit;
    }
    
    // Escape inputs
    $session_id = $conn->real_escape_string($session_id);
    $ip = $_SERVER['REMOTE_ADDR'];
    $page_url = $conn->real_escape_string($input['page_url'] ?? '/');
    $browser = $conn->real_escape_string($input['browser'] ?? 'Unknown');
    $device = $conn->real_escape_string($input['device_type'] ?? 'Desktop');
    
    // Check if visitor exists
    $check = $conn->query("SELECT id FROM visitor_tracking WHERE session_id = '$session_id'");
    
    if ($check && $check->num_rows > 0) {
        // Update existing visitor
        $conn->query("UPDATE visitor_tracking SET 
            current_page = '$page_url',
            last_activity = NOW(),
            page_views = page_views + 1,
            is_online = 1
            WHERE session_id = '$session_id'");
        $action = 'updated';
    } else {
        // Insert new visitor
        $conn->query("INSERT INTO visitor_tracking (
            session_id, ip_address, browser, device_type,
            current_page, entry_page, is_online, first_visit, last_activity
        ) VALUES (
            '$session_id', '$ip', '$browser', '$device',
            '$page_url', '$page_url', 1, NOW(), NOW()
        )");
        $action = 'created';
    }
    
    if ($conn->error) {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    } else {
        echo json_encode([
            'success' => true,
            'action' => $action,
            'session_id' => $session_id,
            'ip' => $ip
        ]);
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
