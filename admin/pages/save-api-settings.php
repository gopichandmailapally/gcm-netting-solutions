<?php
/**
 * Save API Settings
 */
define('GCM_INIT', true);
require_once '../../config/config.php';

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $apiKey = $_POST['api_key'] ?? '';
    $contentLength = (int)($_POST['content_length'] ?? 700);
    $writingStyle = $_POST['writing_style'] ?? 'professional';
    
    // Save to JSON file
    $settings = [
        'api_key' => $apiKey,
        'content_length' => $contentLength,
        'writing_style' => $writingStyle,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $settingsFile = '../../config/gemini-api-settings.json';
    
    // Create config directory if it doesn't exist
    $configDir = dirname($settingsFile);
    if (!is_dir($configDir)) {
        mkdir($configDir, 0755, true);
    }
    
    // Save settings
    if (file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT))) {
        $_SESSION['success_message'] = 'Settings saved successfully!';
    } else {
        $_SESSION['error_message'] = 'Failed to save settings. Check file permissions.';
    }
    
    header('Location: ai-page-generator.php');
    exit;
}
