<?php
/**
 * Generate Single FAQ API
 * Quick generate one FAQ
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

$db = Database::getInstance();
$gemini = new GeminiAPI(GEMINI_API_KEY);

try {
    // Get random category
    $categories = $db->fetchAll("SELECT slug, name FROM faq_categories WHERE is_active = 1");
    $selected_category = $categories[array_rand($categories)];
    $cat_slug = $selected_category['slug'];
    $cat_name = $selected_category['name'];
    
    // Topics for FAQ generation
    $topics = [
        'safety net installation process in Chennai',
        'types of safety nets and their uses',
        'cost and pricing details',
        'warranty coverage and terms',
        'maintenance requirements',
        'quality and durability factors',
        'installation timeline',
        'pigeon net effectiveness',
        'balcony safety solutions',
        'bird protection methods',
        'material specifications',
        'service area coverage',
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
    $prompt = "You are an expert FAQ writer for GCM Netting Solutions in Chennai.

Generate ONE question and answer about: '$topic'
Category: $cat_name

Format as JSON:
{
  \"question\": \"Natural question (10-20 words)?\",
  \"answer\": \"Detailed, helpful answer (150-250 words). Include specific details about GCM Netting Solutions, HDPE material quality, UV stabilization, 3-5 year warranty, professional installation in Chennai, and customer benefits.\"
}

Make it unique, valuable, and SEO-friendly.";

    $response = $gemini->generateContent($prompt);
    
    // Parse response
    $response = trim($response);
    $response = preg_replace('/^```json\s*|\s*```$/s', '', $response);
    $faq_data = json_decode($response, true);
    
    if (!$faq_data || !isset($faq_data['question']) || !isset($faq_data['answer'])) {
        throw new Exception('Failed to parse AI response');
    }
    
    $question = sanitize_input($faq_data['question']);
    $answer = sanitize_input($faq_data['answer']);
    
    // Generate slug
    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/', '-', $question)));
    $slug = substr($slug, 0, 100);
    
    // Check if exists
    $existing = $db->fetchOne("SELECT id FROM faqs WHERE slug = ?", [$slug], 's');
    if ($existing) {
        $slug = $slug . '-' . time();
    }
    
    // Generate meta
    $meta_title = substr($question, 0, 60);
    $meta_description = substr(strip_tags($answer), 0, 155);
    
    // Schema markup
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
    
    if (!$result) {
        throw new Exception('Failed to save FAQ');
    }
    
    // Update stats
    $db->execute(
        "UPDATE faq_settings SET total_generated = total_generated + 1, last_generation_time = NOW() WHERE id = 1"
    );
    
    // Log activity
    log_admin_activity(
        $_SESSION['admin_id'], 
        'faq_generated', 
        "Generated FAQ: " . substr($question, 0, 50)
    );
    
    echo json_encode([
        'success' => true,
        'question' => $question,
        'category' => $cat_name,
        'message' => 'FAQ generated successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
