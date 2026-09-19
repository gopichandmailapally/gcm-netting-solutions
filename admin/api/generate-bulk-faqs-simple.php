<?php
/**
 * Bulk FAQ Generator - Simple Version (No Database)
 * Generates multiple FAQs as JSON files
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/faq-categories.php';
require_once '../../includes/gemini-api.php';

// CRITICAL: Extend execution time for large batches
set_time_limit(600); // 10 minutes
ini_set('max_execution_time', '600');
ini_set('memory_limit', '512M');

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
            'referrer' => substr($referrer ?? '', 0, 50)
        ]
    ]);
    exit;
}

$faq_count = (int)($_POST['faq_count'] ?? 5);
$faq_count = max(1, min(50, $faq_count)); // 1-50 limit

$gemini = new GeminiAPI(GEMINI_API_KEY);

// Get ALL 30 categories from config
$all_categories = get_faq_categories();
$categories = [];
foreach ($all_categories as $cat) {
    $categories[$cat] = $cat;
}

// Topics
$topics = [
    'safety net installation process',
    'pigeon net effectiveness',
    'balcony safety for children and pets',
    'types of safety nets',
    'pricing and cost factors',
    'warranty coverage details',
    'maintenance requirements',
    'HDPE material quality',
    'UV stabilized nets durability',
    'service areas in Chennai',
    'installation timeline',
    'customization options',
    'weather resistance',
    'net lifespan expectations',
    'free inspection service',
    'payment methods',
    'emergency installation',
    'after-sales support',
    'bird protection methods',
    'cricket practice nets',
    'construction site safety',
    'net color options',
    'fire safety compliance',
    'high-rise installation',
    'bulk order discounts'
];

$generated = 0;
$errors = [];
$generated_faqs = [];

// Create directory
$faqs_dir = dirname(dirname(__DIR__)) . '/data/faqs';
if (!file_exists($faqs_dir)) {
    mkdir($faqs_dir, 0755, true);
}

try {
    // Shuffle categories array for better randomization
    $shuffled_categories = $categories;
    shuffle($shuffled_categories);
    $category_index = 0;
    
    for ($i = 0; $i < $faq_count; $i++) {
        // Use shuffled categories in rotation to ensure ALL categories get FAQs
        // This ensures even distribution across all 30 categories
        $cat_name = $shuffled_categories[$category_index % count($shuffled_categories)];
        $cat_key = $cat_name;
        
        // Move to next category
        $category_index++;
        
        // Re-shuffle after going through all categories once
        if ($category_index % count($shuffled_categories) === 0) {
            shuffle($shuffled_categories);
        }
        
        // Random topic
        $topic = $topics[array_rand($topics)];
        
        // Generate FAQ
        $prompt = "You are an expert FAQ writer for GCM Netting Solutions in Chennai, India.

Generate ONE unique FAQ about: '$topic'
Category: $cat_name

Format as JSON (no markdown):
{
  \"question\": \"Natural question (10-20 words)?\",
  \"answer\": \"<p>Professional HTML answer (150-250 words). Include GCM Netting Solutions, HDPE material, 3-5 year warranty, professional installation in Chennai, call +91 99123 99224.</p>\"
}";

        try {
            $response = $gemini->generateContent($prompt);
            $response = trim($response);
            $response = preg_replace('/^```json\s*|\s*```$/s', '', $response);
            $response = preg_replace('/^```\s*|\s*```$/s', '', $response);
            
            $faq_data = json_decode($response, true);
            
            if (!$faq_data || !isset($faq_data['question']) || !isset($faq_data['answer'])) {
                $errors[] = "FAQ #" . ($i + 1) . ": Parse failed";
                continue;
            }
            
            $question = trim($faq_data['question']);
            $answer = trim($faq_data['answer']);
            
            // Generate slug
            $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/', '-', $question)));
            $slug = substr($slug, 0, 100) . '-' . time() . '-' . $i;
            
            // Create FAQ
            $faq = [
                'id' => time() + $i,
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
            
            // Save to file
            $faq_file = $faqs_dir . '/' . $slug . '.json';
            file_put_contents($faq_file, json_encode($faq, JSON_PRETTY_PRINT));

            // Auto-protect: register with AI Content Security
            $_acp = dirname(__DIR__) . '/includes/ai-content-protection.php';
            if (file_exists($_acp) && !class_exists('AIContentProtection')) require_once $_acp;
            if (class_exists('AIContentProtection')) {
                try { (new AIContentProtection())->protect('faq', $slug, $question, 'data/faqs/' . $slug . '.json'); }
                catch (\Exception $e) { /* non-fatal */ }
            }

            // Also save to MySQL faqs table (survives file deletions)
            try {
                $db->execute(
                    "INSERT IGNORE INTO faqs (question, answer, category, slug, is_ai_generated, is_active, created_at)
                     VALUES (?, ?, ?, ?, 1, 1, NOW())",
                    [$question, $answer, $cat_key, $slug]
                );
            } catch (\Exception $e) { /* non-fatal */ }
            
            $generated++;
            $generated_faqs[] = [
                'question' => $question,
                'category' => $cat_name
            ];
            
            // Small delay
            usleep(500000); // 0.5 seconds
            
        } catch (Exception $e) {
            $errors[] = "FAQ #" . ($i + 1) . ": " . $e->getMessage();
        }
    }
    
    // Update stats
    $stats_file = $faqs_dir . '/stats.json';
    $stats = file_exists($stats_file) ? json_decode(file_get_contents($stats_file), true) : [];
    $stats['total_generated'] = ($stats['total_generated'] ?? 0) + $generated;
    $stats['last_generation'] = date('Y-m-d H:i:s');
    file_put_contents($stats_file, json_encode($stats, JSON_PRETTY_PRINT));
    
    // Update index
    $index_file = $faqs_dir . '/index.json';
    $index = file_exists($index_file) ? json_decode(file_get_contents($index_file), true) : [];
    foreach ($generated_faqs as $faq_info) {
        $index[] = [
            'question' => $faq_info['question'],
            'category' => $faq_info['category'],
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
    file_put_contents($index_file, json_encode($index, JSON_PRETTY_PRINT));
    
    echo json_encode([
        'success' => true,
        'generated' => $generated,
        'requested' => $faq_count,
        'errors' => $errors,
        'faqs' => $generated_faqs,
        'message' => "Successfully generated $generated FAQs!"
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
