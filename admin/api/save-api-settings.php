<?php
/**
 * Save API Settings
 * Update Gemini API configuration directly to config.php
 */

// Start session first (before any output)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Start output buffering to catch any unexpected output
ob_start();

define('ADMIN_ACCESS', true);
define('GCM_INIT', true);

// Check authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    ob_clean();
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode([
        'success' => false, 
        'message' => 'Unauthorized - Please login again',
        'debug' => [
            'session_exists' => isset($_SESSION['admin_logged_in']),
            'session_value' => $_SESSION['admin_logged_in'] ?? 'NOT SET',
            'session_id' => session_id()
        ]
    ]);
    exit;
}

// Get input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

$gemini_api_key = trim($input['gemini_api_key'] ?? '');
$gemini_model = trim($input['gemini_model'] ?? 'gemini-pro');
$api_timeout = (int)($input['api_timeout'] ?? 30);
$api_retry_count = (int)($input['api_retry_count'] ?? 3);
$max_tokens = (int)($input['max_tokens'] ?? 4096);

if (empty($gemini_api_key)) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'API key is required']);
    exit;
}

try {
    // Path to config.php
    $config_file = dirname(dirname(__DIR__)) . '/config/config.php';
    
    if (!file_exists($config_file)) {
        throw new Exception('Config file not found at: ' . $config_file);
    }
    
    if (!is_writable($config_file)) {
        throw new Exception('Config file is not writable. Check file permissions.');
    }
    
    // Read current config
    $config_content = file_get_contents($config_file);
    
    if ($config_content === false) {
        throw new Exception('Could not read config file');
    }
    
    // Update GEMINI_API_KEY
    $pattern = "/define\(\s*'GEMINI_API_KEY'\s*,\s*'[^']*'\s*\);/";
    $replacement = "define('GEMINI_API_KEY', '{$gemini_api_key}');";
    $config_content = preg_replace($pattern, $replacement, $config_content);
    
    // Update GEMINI_MODEL
    $pattern = "/define\(\s*'GEMINI_MODEL'\s*,\s*'[^']*'\s*\);/";
    $replacement = "define('GEMINI_MODEL', '{$gemini_model}');";
    $config_content = preg_replace($pattern, $replacement, $config_content);
    
    // Update MAX_TOKENS
    $pattern = "/define\(\s*'MAX_TOKENS'\s*,\s*\d+\s*\);/";
    $replacement = "define('MAX_TOKENS', {$max_tokens});";
    $config_content = preg_replace($pattern, $replacement, $config_content);
    
    // Write back to file
    $write_result = file_put_contents($config_file, $config_content);
    
    if ($write_result === false) {
        throw new Exception('Failed to write to config file. Check permissions.');
    }
    
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'API settings saved successfully!'
    ]);
    
} catch (Exception $e) {
    error_log("API Settings Save Error: " . $e->getMessage());
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
