<?php
/**
 * Generate Single FAQ - Simple Version (No Database Required)
 * Generates FAQ as JSON file
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/faq-categories.php';
require_once '../../includes/gemini-api.php';

// Start session with proper settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_path', '/');
    session_name('GCM_ADMIN_SESSION');
    session_start();
}

header('Content-Type: application/json');

// Authentication check - Allow if from admin panel
$is_authenticated = false;

// Check session
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    $is_authenticated = true;
}

// Fallback: Check referrer (for session cookie issues)
if (!$is_authenticated) {
    $referrer = $_SERVER['HTTP_REFERER'] ?? '';
    if (strpos($referrer, '/admin/') !== false && strpos($referrer, $_SERVER['HTTP_HOST']) !== false) {
        $is_authenticated = true; // Request from admin panel on same domain
    }
}

if (!$is_authenticated) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Please refresh admin panel and try again.',
        'debug' => [
            'session_id' => session_id(),
            'has_admin_session' => isset($_SESSION['admin_logged_in']),
            'referrer' => substr($referrer, 0, 50)
        ]
    ]);
    exit;
}

$gemini = new GeminiAPI(GEMINI_API_KEY);

try {
    // Get ALL 30 categories from config
    $all_categories = get_faq_categories();
    $cat_name = $all_categories[array_rand($all_categories)];
    $cat_key = $cat_name;
    
    // Topics for FAQ generation
    $topics = [
        'safety net installation process in Chennai',
        'types of safety nets and their uses',
        'cost and pricing details for safety nets',
        'warranty coverage and terms',
        'maintenance requirements for safety nets',
        'quality and durability factors',
        'installation timeline',
        'pigeon net effectiveness',
        'balcony safety solutions',
        'bird protection methods',
        'material specifications HDPE',
        'service area coverage in Chennai',
        'customization options available',
        'weather resistance features',
        'safety certifications',
        'free inspection service',
        'payment and financing options',
        'emergency installation availability',
        'net lifespan and replacement',
        'after-sales support'
    ];
    
    $topic = $topics[array_rand($topics)];
    
    // Generate FAQ with AI
    $prompt = "You are an expert FAQ writer for GCM Netting Solutions in Chennai, India.

Generate ONE frequently asked question and detailed answer about: '$topic'

Category: $cat_name

Requirements:
1. Question: Natural, conversational, 10-20 words, starts with What/How/Why/When/Do/Can/Is
2. Answer: Professional, informative, 150-250 words in HTML format
3. Include: Specific details about GCM Netting Solutions services in Chennai
4. Mention: Quality (HDPE material, UV stabilized), warranty (3-5 years), professional installation, call +91 99123 99224
5. Tone: Helpful, confident, customer-focused
6. SEO: Naturally include keywords like 'safety nets Chennai', 'installation'

Format as JSON ONLY (no markdown, no extra text):
{
  \"question\": \"Your question here?\",
  \"answer\": \"<p>Your detailed HTML answer here.</p>\"
}";

    $response = $gemini->generateContent($prompt);

    // Robust JSON extraction — handles text before/after JSON, backtick wrappers, etc.
    $response = trim($response);
    $response = preg_replace('/^```(?:json)?\s*/s', '', $response);
    $response = preg_replace('/\s*```\s*$/s', '', $response);
    $response = trim($response);
    // Extract from first { to last } (handles any surrounding explanatory text)
    $_fb = strpos($response, '{');
    $_lb = strrpos($response, '}');
    if ($_fb !== false && $_lb !== false && $_lb > $_fb) {
        $response = substr($response, $_fb, $_lb - $_fb + 1);
    }

    $faq_data = json_decode($response, true);

    // Retry once if parse failed — prompt Gemini again with stricter instruction
    if (!$faq_data || !isset($faq_data['question']) || !isset($faq_data['answer'])) {
        $retry_prompt = $prompt . "\n\nCRITICAL: Your previous response could not be parsed. Return ONLY valid JSON starting with { and ending with }. No markdown, no explanation, no extra text.";
        $retry_resp = trim($gemini->generateContent($retry_prompt));
        $retry_resp = preg_replace('/^```(?:json)?\s*/s', '', $retry_resp);
        $retry_resp = preg_replace('/\s*```\s*$/s', '', $retry_resp);
        $_rfb = strpos($retry_resp, '{'); $_rlb = strrpos($retry_resp, '}');
        if ($_rfb !== false && $_rlb !== false && $_rlb > $_rfb) {
            $retry_resp = substr($retry_resp, $_rfb, $_rlb - $_rfb + 1);
        }
        $faq_data = json_decode($retry_resp, true);
        if (!$faq_data || !isset($faq_data['question']) || !isset($faq_data['answer'])) {
            throw new Exception('Failed to parse AI response after retry: ' . substr($retry_resp, 0, 100));
        }
    }
    
    $question = trim($faq_data['question']);
    $answer = trim($faq_data['answer']);
    
    // Generate slug
    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/', '-', $question)));
    $slug = substr($slug, 0, 100);
    
    // Create FAQ data
    $faq = [
        'id' => time(),
        'question' => $question,
        'answer' => $answer,
        'category' => $cat_key,
        'category_name' => $cat_name,
        'slug' => $slug,
        'created_at' => date('Y-m-d H:i:s'),
        'is_active' => true,
        'views' => 0,
        'helpful_count' => 0
    ];
    
    // Save to JSON file
    $faqs_dir = dirname(dirname(__DIR__)) . '/data/faqs';
    if (!file_exists($faqs_dir)) {
        mkdir($faqs_dir, 0755, true);
    }
    
    $faq_file = $faqs_dir . '/' . $slug . '.json';
    file_put_contents($faq_file, json_encode($faq, JSON_PRETTY_PRINT));

    // Auto-protect: register with AI Content Security
    $_acp = dirname(__DIR__) . '/includes/ai-content-protection.php';
    if (file_exists($_acp) && !class_exists('AIContentProtection')) require_once $_acp;
    if (class_exists('AIContentProtection')) {
        try { (new AIContentProtection())->protect('faq', $slug, $question, 'data/faqs/' . $slug . '.json'); }
        catch (Exception $e) { /* non-fatal */ }
    }

    // Update stats
    $stats_file = $faqs_dir . '/stats.json';
    $stats = file_exists($stats_file) ? json_decode(file_get_contents($stats_file), true) : [];
    $stats['total_generated'] = ($stats['total_generated'] ?? 0) + 1;
    $stats['last_generation'] = date('Y-m-d H:i:s');
    file_put_contents($stats_file, json_encode($stats, JSON_PRETTY_PRINT));
    
    // Add to index
    $index_file = $faqs_dir . '/index.json';
    $index = file_exists($index_file) ? json_decode(file_get_contents($index_file), true) : [];
    $index[] = [
        'slug' => $slug,
        'question' => $question,
        'category' => $cat_name,
        'created_at' => date('Y-m-d H:i:s')
    ];
    file_put_contents($index_file, json_encode($index, JSON_PRETTY_PRINT));
    
    echo json_encode([
        'success' => true,
        'question' => $question,
        'category' => $cat_name,
        'slug' => $slug,
        'file' => $faq_file,
        'message' => 'FAQ generated and saved successfully!'
    ]);
    
} catch (Exception $e) {
    error_log('FAQ Generation Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
