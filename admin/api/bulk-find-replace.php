<?php
/**
 * Bulk Find & Replace API
 * Replace text across multiple pages
 */

define('ADMIN_ACCESS', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$find_text = sanitize_input($_POST['find_text'] ?? '');
$replace_text = sanitize_input($_POST['replace_text'] ?? '');
$apply_to = sanitize_input($_POST['apply_to'] ?? 'all_pages');
$case_sensitive = isset($_POST['case_sensitive']);

if (empty($find_text)) {
    echo json_encode(['success' => false, 'message' => 'Find text cannot be empty']);
    exit;
}

$db = Database::getInstance();
$count = 0;
$pages_affected = 0;

try {
    // Service Pages
    if ($apply_to == 'all_pages' || $apply_to == 'service_pages') {
        $pages = $db->fetchAll("SELECT id, content FROM generated_pages WHERE is_active = 1");
        
        foreach ($pages as $page) {
            $original = $page['content'];
            $modified = $case_sensitive 
                ? str_replace($find_text, $replace_text, $original, $matches)
                : str_ireplace($find_text, $replace_text, $original, $matches);
            
            if ($matches > 0) {
                $db->execute(
                    "UPDATE generated_pages SET content = ?, updated_at = NOW() WHERE id = ?",
                    [$modified, $page['id']],
                    'si'
                );
                $count += $matches;
                $pages_affected++;
            }
        }
    }
    
    // Blog Posts
    if ($apply_to == 'all_pages' || $apply_to == 'blog_posts') {
        $blogs = $db->fetchAll("SELECT id, content FROM blog_posts WHERE is_published = 1");
        
        foreach ($blogs as $blog) {
            $original = $blog['content'];
            $modified = $case_sensitive 
                ? str_replace($find_text, $replace_text, $original, $matches)
                : str_ireplace($find_text, $replace_text, $original, $matches);
            
            if ($matches > 0) {
                $db->execute(
                    "UPDATE blog_posts SET content = ?, updated_at = NOW() WHERE id = ?",
                    [$modified, $blog['id']],
                    'si'
                );
                $count += $matches;
                $pages_affected++;
            }
        }
    }
    
    // Log activity
    log_admin_activity(
        $_SESSION['admin_id'], 
        'bulk_find_replace', 
        "Replaced '$find_text' with '$replace_text' ($count occurrences in $pages_affected pages)"
    );
    
    echo json_encode([
        'success' => true,
        'count' => $count,
        'pages' => $pages_affected,
        'message' => "Replaced $count occurrences in $pages_affected pages"
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
