<?php
/**
 * Generate Meta Descriptions with AI
 * Automatically create meta descriptions for pages
 */

define('ADMIN_ACCESS', true);
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/gemini-api.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$target = $input['target'] ?? 'missing_only';
$length = (int)($input['length'] ?? 155);

$db = Database::getInstance();
$gemini = new GeminiAPI(GEMINI_API_KEY);

$count = 0;

try {
    // Get pages based on target
    $where_clause = $target == 'missing_only' 
        ? "WHERE (meta_description IS NULL OR meta_description = '') AND is_active = 1" 
        : "WHERE is_active = 1";
    
    if ($target == 'service_pages') {
        $pages = $db->fetchAll("SELECT id, title, content FROM generated_pages $where_clause LIMIT 100");
    } elseif ($target == 'blog_posts') {
        $pages = $db->fetchAll("SELECT id, title, content FROM blog_posts WHERE is_published = 1 LIMIT 100");
    } else {
        $pages = $db->fetchAll("SELECT id, title, content FROM generated_pages $where_clause LIMIT 50");
        $blogs = $db->fetchAll("SELECT id, title, content FROM blog_posts WHERE is_published = 1 AND (meta_description IS NULL OR meta_description = '') LIMIT 50");
        $pages = array_merge($pages, $blogs);
    }
    
    foreach ($pages as $page) {
        $title = $page['title'];
        $content_preview = substr(strip_tags($page['content']), 0, 300);
        
        $prompt = "Write a compelling meta description for this page. Title: '$title'. Content preview: '$content_preview'. Requirements: Exactly $length characters, include keywords, make it clickable, no special characters. Only return the meta description text, nothing else.";
        
        try {
            $meta_desc = $gemini->generateContent($prompt);
            
            if ($meta_desc) {
                // Clean and trim to exact length
                $meta_desc = trim($meta_desc);
                $meta_desc = preg_replace('/^["\']|["\']$/', '', $meta_desc);
                $meta_desc = substr($meta_desc, 0, $length);
                
                // Determine table
                $table = isset($page['slug']) ? 'generated_pages' : 'blog_posts';
                
                $db->execute(
                    "UPDATE $table SET meta_description = ?, updated_at = NOW() WHERE id = ?",
                    [$meta_desc, $page['id']],
                    'si'
                );
                
                $count++;
            }
            
            // Delay to respect API limits
            usleep(500000); // 0.5 second
            
        } catch (Exception $e) {
            error_log("Meta description generation error: " . $e->getMessage());
            continue;
        }
    }
    
    // Log activity
    log_admin_activity(
        $_SESSION['admin_id'], 
        'meta_descriptions_generated', 
        "Generated meta descriptions for $count pages"
    );
    
    echo json_encode([
        'success' => true,
        'count' => $count,
        'message' => "Generated meta descriptions for $count pages"
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
