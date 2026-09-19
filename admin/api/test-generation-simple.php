<?php
/**
 * Simple FAQ Generation Test
 * Call this directly from browser while logged into admin
 * URL: /admin/api/test-generation-simple.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Not logged in as admin',
        'session_id' => session_id(),
        'session_data' => $_SESSION
    ]);
    exit;
}

define('GCM_INIT', true);

try {
    // Load required files
    require_once '../../config/config.php';
    require_once '../../config/faq-categories.php';
    require_once '../../includes/gemini-api.php';
    
    // Check API key
    if (!defined('GEMINI_API_KEY') || empty(GEMINI_API_KEY)) {
        throw new Exception('GEMINI_API_KEY not configured');
    }
    
    // Get categories
    $categories = get_faq_categories();
    if (empty($categories)) {
        throw new Exception('No categories found');
    }
    
    // Pick random category
    $category = $categories[array_rand($categories)];
    
    // Create Gemini API instance
    $gemini = new GeminiAPI(GEMINI_API_KEY);
    
    // Generate FAQ
    $prompt = "Generate 1 FAQ about safety nets for '$category' category. 
    
    Requirements:
    - Question should be natural and conversational
    - Answer should be detailed and helpful (150-250 words)
    - Focus on safety nets, bird netting, pigeon control in Chennai
    - Return ONLY valid JSON
    
    JSON format:
    {
        \"question\": \"Your question here?\",
        \"answer\": \"Your detailed answer here.\"
    }
    
    Generate now:";
    
    $response = $gemini->generateContent($prompt);
    
    if (!$response || empty($response)) {
        throw new Exception('Empty response from Gemini API');
    }
    
    // Parse JSON from response
    $json_start = strpos($response, '{');
    $json_end = strrpos($response, '}');
    
    if ($json_start === false || $json_end === false) {
        throw new Exception('No JSON found in response: ' . substr($response, 0, 200));
    }
    
    $json_str = substr($response, $json_start, $json_end - $json_start + 1);
    $faq_data = json_decode($json_str, true);
    
    if (!$faq_data || !isset($faq_data['question']) || !isset($faq_data['answer'])) {
        throw new Exception('Invalid FAQ data: ' . $json_str);
    }
    
    // Success!
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'question' => $faq_data['question'],
        'answer' => $faq_data['answer'],
        'category' => $category,
        'api_key_length' => strlen(GEMINI_API_KEY),
        'categories_count' => count($categories),
        'raw_response_preview' => substr($response, 0, 100)
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
?>
