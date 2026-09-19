<?php
/**
 * Toggle FAQ Auto-Generation API
 */

define('ADMIN_ACCESS', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$enabled = isset($input['enabled']) && $input['enabled'] ? 1 : 0;

$db = Database::getInstance();

try {
    $result = $db->execute(
        "UPDATE faq_settings SET auto_generate_enabled = ? WHERE id = 1",
        [$enabled],
        'i'
    );
    
    if ($result) {
        log_admin_activity(
            $_SESSION['admin_id'], 
            'faq_auto_generation_toggled', 
            $enabled ? 'Enabled FAQ auto-generation' : 'Disabled FAQ auto-generation'
        );
        
        echo json_encode([
            'success' => true,
            'enabled' => $enabled,
            'message' => $enabled ? 'Auto-generation enabled' : 'Auto-generation disabled'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update settings'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
