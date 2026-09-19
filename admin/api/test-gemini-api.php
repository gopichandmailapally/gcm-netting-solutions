<?php
/**
 * Test Gemini API Connection
 * Verify API key is valid
 */

define('GCM_INIT', true);
define('ADMIN_ACCESS', true);
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/gemini-api.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Prefer primary key from DB; fall back to config constant
    $db = Database::getInstance();
    $primary = $db->fetchOne("SELECT api_key FROM gemini_api_keys WHERE is_primary = 1 LIMIT 1");
    $api_key = $primary['api_key'] ?? (defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '');

    if (empty($api_key)) {
        $_SESSION['api_test_status'] = 'error';
        $_SESSION['api_test_message'] = 'API key not configured';
        $_SESSION['api_test_time'] = time();
        echo json_encode([
            'success' => false,
            'message' => 'API key not configured. Please add your key in API Settings.'
        ]);
        exit;
    }
    
    // Test API connection using GeminiAPI class (with automatic model fallback)
    $gemini = new GeminiAPI($api_key);
    
    // Use the built-in test connection method
    $result = $gemini->testConnection();
    
    if ($result['success']) {
        $modelUsed = $gemini->getLastUsedModel();
        
        // Store test result in session (persists after page refresh)
        $_SESSION['api_test_status'] = 'success';
        $_SESSION['api_test_message'] = 'API Connected Successfully (Using: ' . ($modelUsed ?: 'gemini-2.0-flash-001') . ')';
        $_SESSION['api_test_time'] = time();
        
        echo json_encode([
            'success' => true,
            'message' => 'API connection successful! ✅ Your Gemini API key is working correctly.',
            'model' => $modelUsed ?: 'gemini-2.0-flash-001'
        ]);
    } else {
        // Store failure in session
        $_SESSION['api_test_status'] = 'error';
        $_SESSION['api_test_message'] = 'Connection Failed: ' . $result['message'];
        $_SESSION['api_test_time'] = time();
        
        echo json_encode([
            'success' => false,
            'message' => 'API Error: ' . $result['message']
        ]);
    }
    
} catch (Exception $e) {
    // Store exception in session
    $_SESSION['api_test_status'] = 'error';
    $_SESSION['api_test_message'] = 'Error: ' . $e->getMessage();
    $_SESSION['api_test_time'] = time();
    
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
