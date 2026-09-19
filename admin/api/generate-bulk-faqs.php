<?php
/**
 * Bulk FAQ Generator API
 * Generate multiple FAQs with AI
 */

define('ADMIN_ACCESS', true);
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/gemini-api.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$faq_count = (int)($_POST['faq_count'] ?? 5);
$category = sanitize_input($_POST['category'] ?? 'random');
$topics = sanitize_input($_POST['topics'] ?? '');

if ($faq_count < 1 || $faq_count > 50) {
    echo json_encode(['success' => false, 'message' => 'FAQ count must be between 1 and 50']);
    exit;
}

$db = Database::getInstance();
$gemini = new GeminiAPI(GEMINI_API_KEY);

// Get categories
$categories = $db->fetchAll("SELECT slug, name FROM faq_categories WHERE is_active = 1");
$category_map = array_column($categories, 'name', 'slug');

// FAQ topics for generation
$default_topics = [
    'safety net installation process',
    'types of safety nets available',
    'pricing and cost estimation',
    'warranty and guarantee details',
    'maintenance and cleaning',
    'material quality and durability',
    'service areas in Chennai',
    'installation time and process',
    'safety certifications',
    'customization options',
    'pigeon net benefits',
    'balcony safety for children',
    'bird protection methods',
    'cricket net specifications',
    'construction safety requirements',
    'net color options',
    'weather resistance',
    'fire safety compliance',
    'net replacement procedure',
    'emergency installation services',
    'bulk order discounts',
    'payment methods accepted',
    'free site inspection',
    'after-sales service',
    'product comparison',
    'installation on high-rise buildings',
    'pet safety with nets',
    'UV protection in nets',
    'net lifespan expectations',
    'seasonal maintenance tips'
];

if (!empty($topics)) {
    $custom_topics = array_map('trim', explode(',', $topics));
    $generation_topics = array_merge($custom_topics, $default_topics);
} else {
    $generation_topics = $default_topics;
}

shuffle($generation_topics);

$generated = 0;
$errors = [];

try {
    for ($i = 0; $i < $faq_count; $i++) {
        // Select category
        if ($category == 'random') {
            $selected_category = $categories[array_rand($categories)];
            $cat_slug = $selected_category['slug'];
            $cat_name = $selected_category['name'];
        } else {
            $cat_slug = $category;
            $cat_name = $category_map[$category] ?? 'General';
        }
        
        // Select topic
        $topic_index = $i % count($generation_topics);
        $topic = $generation_topics[$topic_index];
        
        // Generate FAQ with AI
        $prompt = "You are an expert FAQ writer for GCM Netting Solutions, a professional safety net installation company in Chennai, India.

Generate ONE frequently asked question and its detailed answer about: '$topic'

Category: $cat_name

Requirements:
1. Question: Natural, conversational, 10-20 words, starts with What/How/Why/When/Do/Can/Is
2. Answer: Professional, informative, 150-250 words
3. Include: Specific details about GCM Netting Solutions services in Chennai
4. Mention: Quality (HDPE material, UV stabilized), warranty (3-5 years), professional installation
5. Tone: Helpful, confident, customer-focused
6. SEO: Naturally include keywords like 'safety nets Chennai', 'installation', relevant service names

Format (JSON):
{
  \"question\": \"Your question here?\",
  \"answer\": \"Your detailed answer here.\"
}

Generate unique, valuable content. No generic responses.";

        try {
            $response = $gemini->generateContent($prompt);
            
            // Parse JSON response
            $response = trim($response);
            $response = preg_replace('/^```json\s*|\s*```$/s', '', $response);
            $faq_data = json_decode($response, true);
            
            if (!$faq_data || !isset($faq_data['question']) || !isset($faq_data['answer'])) {
                $errors[] = "Failed to parse FAQ #" . ($i + 1);
                continue;
            }
            
            $question = sanitize_input($faq_data['question']);
            $answer = sanitize_input($faq_data['answer']);
            
            // Generate slug
            $slug = generate_slug($question);
            
            // Check if similar FAQ exists
            $existing = $db->fetchOne(
                "SELECT id FROM faqs WHERE slug = ? OR question = ?",
                [$slug, $question],
                'ss'
            );
            
            if ($existing) {
                $slug = $slug . '-' . time();
            }
            
            // Generate meta data
            $meta_title = substr($question, 0, 60);
            $meta_description = substr(strip_tags($answer), 0, 155);
            
            // Generate schema markup
            $schema = json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => strip_tags($answer)
                ]
            ]);
            
            // Insert FAQ
            $result = $db->execute(
                "INSERT INTO faqs (question, answer, category, slug, is_ai_generated, meta_title, meta_description, schema_markup, is_active) 
                 VALUES (?, ?, ?, ?, 1, ?, ?, ?, 1)",
                [$question, $answer, $cat_slug, $slug, $meta_title, $meta_description, $schema],
                'sssssss'
            );
            
            if ($result) {
                $generated++;
            }
            
            // Delay to respect API rate limits
            usleep(500000); // 0.5 seconds
            
        } catch (Exception $e) {
            $errors[] = "Error generating FAQ #" . ($i + 1) . ": " . $e->getMessage();
            error_log($e->getMessage());
        }
    }
    
    // Update settings
    $db->execute(
        "UPDATE faq_settings SET total_generated = total_generated + ?, last_generation_time = NOW() WHERE id = 1",
        [$generated],
        'i'
    );
    
    // Log activity
    log_admin_activity(
        $_SESSION['admin_id'], 
        'bulk_faq_generation', 
        "Generated $generated FAQs (requested: $faq_count)"
    );
    
    echo json_encode([
        'success' => true,
        'generated' => $generated,
        'requested' => $faq_count,
        'errors' => $errors,
        'message' => "Successfully generated $generated FAQs"
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

function generate_slug($text) {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return substr($text, 0, 100);
}
