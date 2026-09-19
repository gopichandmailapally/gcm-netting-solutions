<?php
/**
 * Toggle Auto-Generation On/Off for FAQ, Blog, and Review Generators
 */

define('GCM_INIT', true);
require_once '../../config/config.php';

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

// Get input
$input = json_decode(file_get_contents('php://input'), true);
$enabled = isset($input['enabled']) ? (bool)$input['enabled'] : false;
$type = $input['type'] ?? 'faq'; // faq, blog, or review

// Config file path
$config_file = dirname(dirname(__DIR__)) . '/config/auto-generation-config.json';

try {
    // Read current config
    if (file_exists($config_file)) {
        $config = json_decode(file_get_contents($config_file), true);
    } else {
        // Create default config
        $config = [
            'faq_generator' => ['enabled' => false, 'daily_count' => 1, 'generation_time' => 'random'],
            'blog_generator' => ['enabled' => false, 'daily_count' => 1, 'generation_time' => 'random'],
            'review_generator' => ['enabled' => false, 'daily_count' => 1, 'generation_time' => 'random']
        ];
    }
    
    // Update the specific generator
    $generator_key = $type . '_generator';
    if (isset($config[$generator_key])) {
        $config[$generator_key]['enabled'] = $enabled;
    } else {
        $config[$generator_key] = [
            'enabled' => $enabled,
            'daily_count' => 1,
            'generation_time' => 'random'
        ];
    }
    
    // Save config
    file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
    
    echo json_encode([
        'success' => true,
        'enabled' => $enabled,
        'type' => $type,
        'message' => ucfirst($type) . ' auto-generation ' . ($enabled ? 'enabled' : 'disabled')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
