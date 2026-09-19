<?php
/**
 * Bulk Blog Generation API
 * Generate multiple blogs using AI
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

$count = (int)($_POST['count'] ?? 5);
$category = sanitize_input($_POST['category'] ?? '');
$service_id = (int)($_POST['service_id'] ?? 0);

$count = min($count, 10); // Limit to 10 per batch

$db = Database::getInstance();
$gemini = new GeminiAPI(GEMINI_API_KEY);

$created = 0;
$categories = ['Safety Tips', 'Installation Guide', 'Product Review', 'Industry News', 'Maintenance', 'Case Study'];

// Get service info if specified
$service_info = '';
if ($service_id > 0) {
    $service = $db->fetchOne("SELECT service_name FROM services WHERE id = ?", [$service_id], 'i');
    $service_info = $service ? $service['service_name'] : '';
}

for ($i = 0; $i < $count; $i++) {
    // Randomize category if not specified
    $use_category = $category ?: $categories[array_rand($categories)];
    
    // Generate unique blog content
    $prompt = "Write a unique, SEO-optimized blog post about ";
    $prompt .= $service_info ? "$service_info " : "safety nets ";
    $prompt .= "for category: $use_category. ";
    $prompt .= "Include: compelling title (50-60 chars), meta description (150-160 chars), ";
    $prompt .= "and 800-1200 words content with proper headings. ";
    $prompt .= "Make it informative, engaging, and helpful for Chennai customers. ";
    $prompt .= "Format: Title|MetaDesc|Content (use ## for H2, ### for H3)";
    
    try {
        $response = $gemini->generateContent($prompt);
        
        if ($response) {
            $parts = explode('|', $response, 3);
            if (count($parts) === 3) {
                $title = trim($parts[0]);
                $meta_desc = trim($parts[1]);
                $content = trim($parts[2]);
                $slug = create_slug($title);
                $excerpt = substr(strip_tags($content), 0, 200);
                
                // Insert blog
                $result = $db->execute("
                    INSERT INTO blog_posts 
                    (title, slug, excerpt, content, category, meta_description, is_published, is_ai_generated, created_at, published_at) 
                    VALUES (?, ?, ?, ?, ?, ?, 1, 1, NOW(), NOW())
                ", [$title, $slug, $excerpt, $content, $use_category, $meta_desc], 'ssssss');
                
                if ($result['success']) {
                    $created++;
                }
            }
        }
        
        // Small delay to avoid rate limits
        usleep(500000); // 0.5 second
    } catch (Exception $e) {
        error_log("Blog generation error: " . $e->getMessage());
    }
}

echo json_encode([
    'success' => $created > 0,
    'created' => $created,
    'message' => "Generated $created out of $count blogs"
]);
