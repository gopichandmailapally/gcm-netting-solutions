<?php
/**
 * DIRECT FAQ Generation Test (No Session Check)
 * This bypasses session to test if API actually works
 * URL: /admin/api/test-generation-direct.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('GCM_INIT', true);

header('Content-Type: application/json');

try {
    // Load required files
    require_once '../../config/config.php';
    require_once '../../config/faq-categories.php';
    require_once '../../includes/gemini-api.php';
    
    // Check API key
    if (!defined('GEMINI_API_KEY') || empty(GEMINI_API_KEY)) {
        throw new Exception('GEMINI_API_KEY not configured in config.php');
    }
    
    // Get categories
    $categories = get_faq_categories();
    if (empty($categories)) {
        throw new Exception('No categories found in faq-categories.php');
    }
    
    // Pick random category
    $category = $categories[array_rand($categories)];
    
    // Create Gemini API instance
    $gemini = new GeminiAPI(GEMINI_API_KEY);
    
    // Generate FAQ
    $prompt = "Generate 1 FAQ about safety nets for the category: $category

Requirements:
- Question should be natural and conversational about safety nets, bird netting, or pigeon control in Chennai
- Answer should be detailed and helpful (150-250 words)
- Must be relevant to: $category
- Return ONLY valid JSON, no markdown or extra text

JSON format:
{
    \"question\": \"Your question here?\",
    \"answer\": \"Your detailed answer here.\"
}";
    
    $response = $gemini->generateContent($prompt);
    
    if (!$response || empty($response)) {
        throw new Exception('Empty response from Gemini API. Check your API key and internet connection.');
    }
    
    // Parse JSON from response
    $json_start = strpos($response, '{');
    $json_end = strrpos($response, '}');
    
    if ($json_start === false || $json_end === false) {
        throw new Exception('No JSON found in API response. Response: ' . substr($response, 0, 300));
    }
    
    $json_str = substr($response, $json_start, $json_end - $json_start + 1);
    $faq_data = json_decode($json_str, true);
    
    if (!$faq_data || !isset($faq_data['question']) || !isset($faq_data['answer'])) {
        throw new Exception('Invalid FAQ data structure. JSON: ' . $json_str);
    }
    
    // Success!
    echo json_encode([
        'success' => true,
        'message' => 'FAQ generated successfully! API is working!',
        'question' => $faq_data['question'],
        'answer' => substr($faq_data['answer'], 0, 100) . '...',
        'category' => $category,
        'test_info' => [
            'api_key_configured' => true,
            'api_key_length' => strlen(GEMINI_API_KEY),
            'categories_count' => count($categories),
            'gemini_class_exists' => class_exists('GeminiAPI')
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine(),
        'test_info' => [
            'config_loaded' => defined('GCM_INIT'),
            'api_key_defined' => defined('GEMINI_API_KEY'),
            'categories_function_exists' => function_exists('get_faq_categories')
        ]
    ], JSON_PRETTY_PRINT);
}
?>
