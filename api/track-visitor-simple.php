<?php
/**
 * Simplified Track Visitor API - Step by step testing
 */

// Allow API access
define('GCM_INIT', true);

// Set error display
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // Step 1: Load config
    require_once __DIR__ . '/../config/config.php';
    
    // Step 2: Load database
    require_once __DIR__ . '/../config/database.php';
    
    // Step 3: Get input
    $input = json_decode(file_get_contents('php://input'), true);
    $session_id = $input['session_id'] ?? null;
    
    if (!$session_id) {
        echo json_encode(['success' => false, 'message' => 'Session ID required']);
        exit;
    }
    
    // Step 4: Get database instance
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Step 5: Simple INSERT query (no prepared statement complications)
    $session_id = $conn->real_escape_string($session_id);
    $ip = $_SERVER['REMOTE_ADDR'];
    $page_url = $conn->real_escape_string($input['page_url'] ?? 'test');
    $browser = $conn->real_escape_string($input['browser'] ?? 'Unknown');
    $device = $conn->real_escape_string($input['device_type'] ?? 'Desktop');
    
    // First check if exists
    $check = $conn->query("SELECT id FROM visitor_tracking WHERE session_id = '$session_id'");
    
    if ($check && $check->num_rows > 0) {
        // Update
        $sql = "UPDATE visitor_tracking SET 
                current_page = '$page_url',
                last_activity = NOW(),
                page_views = page_views + 1,
                is_online = 1
                WHERE session_id = '$session_id'";
        $conn->query($sql);
        $action = 'updated';
    } else {
        // Insert
        $sql = "INSERT INTO visitor_tracking (
                    session_id, ip_address, browser, device_type, 
                    current_page, entry_page, is_online
                ) VALUES (
                    '$session_id', '$ip', '$browser', '$device',
                    '$page_url', '$page_url', 1
                )";
        $conn->query($sql);
        $action = 'inserted';
    }
    
    if ($conn->error) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $conn->error,
            'sql' => $sql
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Visitor tracked',
            'action' => $action,
            'session_id' => $session_id,
            'ip' => $ip
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
} catch (Error $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Fatal error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
