<?php
/**
 * AUTOMATED SEO SCANNER & FIXER
 * Scans all PHP pages and identifies SEO issues
 * Can automatically fix common problems
 */

// Start session with proper settings (must match login.php)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_path', '/');
    session_name('GCM_ADMIN_SESSION');
    session_start();
}

// Check if logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$base_dir = dirname(dirname(__DIR__));
$action = $_POST['action'] ?? 'scan';

// SEO Rules and Checks
class SEOScanner {
    private $errors = [];
    private $warnings = [];
    private $total_pages = 0;
    
    public function scanAllPages($base_dir) {
        $results = [
            'critical' => [],
            'high' => [],
            'medium' => [],
            'low' => [],
            'summary' => []
        ];
        
        // Get all PHP files
        $php_files = glob($base_dir . '/*.php');
        
        $skip_patterns = [
            'admin', 'test-', 'check-', 'cleanup-', 'find-', 'fix-',
            'regenerate-', 'show-', 'create-', 'prepare-', 'setup-',
            'update-', 'generate-', '-old.', 'redirect', 'config'
        ];
        
        foreach ($php_files as $file) {
            $filename = basename($file);
            
            // Skip utility files
            $should_skip = false;
            foreach ($skip_patterns as $pattern) {
                if (strpos($filename, $pattern) !== false) {
                    $should_skip = true;
                    break;
                }
            }
            
            if ($should_skip) continue;
            
            $this->total_pages++;
            $issues = $this->scanFile($file, $filename);
            
            if (!empty($issues)) {
                foreach ($issues as $issue) {
                    $results[$issue['priority']][] = $issue;
                }
            }
        }
        
        // Generate summary
        $results['summary'] = [
            'total_pages_scanned' => $this->total_pages,
            'critical_issues' => count($results['critical']),
            'high_issues' => count($results['high']),
            'medium_issues' => count($results['medium']),
            'low_issues' => count($results['low']),
            'total_issues' => count($results['critical']) + count($results['high']) + count($results['medium']) + count($results['low'])
        ];
        
        return $results;
    }
    
    private function scanFile($filepath, $filename) {
        $issues = [];
        $content = file_get_contents($filepath);
        
        // Check 1: Missing or short meta description
        if (!preg_match('/\$meta_description\s*=\s*["\'](.+?)["\']/s', $content, $matches)) {
            $issues[] = [
                'page' => $filename,
                'type' => 'missing_meta_description',
                'priority' => 'critical',
                'issue' => 'Missing meta description',
                'recommendation' => 'Add $meta_description variable with 150-160 characters',
                'auto_fixable' => true
            ];
        } elseif (strlen($matches[1]) < 120) {
            $issues[] = [
                'page' => $filename,
                'type' => 'short_meta_description',
                'priority' => 'high',
                'issue' => 'Meta description too short (' . strlen($matches[1]) . ' characters)',
                'recommendation' => 'Expand meta description to 150-160 characters',
                'auto_fixable' => false
            ];
        }
        
        // Check 2: Missing or short page title
        if (!preg_match('/\$page_title\s*=\s*["\'](.+?)["\']/s', $content, $matches)) {
            $issues[] = [
                'page' => $filename,
                'type' => 'missing_title',
                'priority' => 'critical',
                'issue' => 'Missing page title',
                'recommendation' => 'Add $page_title variable with descriptive title',
                'auto_fixable' => true
            ];
        } elseif (strlen($matches[1]) < 30) {
            $issues[] = [
                'page' => $filename,
                'type' => 'short_title',
                'priority' => 'high',
                'issue' => 'Page title too short (' . strlen($matches[1]) . ' characters)',
                'recommendation' => 'Expand title to 50-60 characters',
                'auto_fixable' => false
            ];
        }
        
        // Check 3: Missing meta keywords
        if (!preg_match('/\$meta_keywords\s*=\s*["\'](.+?)["\']/s', $content, $matches)) {
            $issues[] = [
                'page' => $filename,
                'type' => 'missing_keywords',
                'priority' => 'medium',
                'issue' => 'Missing meta keywords',
                'recommendation' => 'Add $meta_keywords with relevant keywords',
                'auto_fixable' => true
            ];
        }
        
        // Check 4: Missing H1 tag
        if (!preg_match('/<h1[^>]*>/i', $content)) {
            $issues[] = [
                'page' => $filename,
                'type' => 'missing_h1',
                'priority' => 'high',
                'issue' => 'Missing H1 heading tag',
                'recommendation' => 'Add at least one H1 tag for the main heading',
                'auto_fixable' => false
            ];
        }
        
        // Check 5: Multiple H1 tags
        if (preg_match_all('/<h1[^>]*>/i', $content) > 1) {
            $issues[] = [
                'page' => $filename,
                'type' => 'multiple_h1',
                'priority' => 'medium',
                'issue' => 'Multiple H1 tags found',
                'recommendation' => 'Use only one H1 tag per page',
                'auto_fixable' => false
            ];
        }
        
        // Check 6: Images without alt text
        if (preg_match_all('/<img[^>]+>/i', $content, $img_matches)) {
            $images_without_alt = 0;
            foreach ($img_matches[0] as $img_tag) {
                if (!preg_match('/alt\s*=\s*["\'](.+?)["\']/i', $img_tag)) {
                    $images_without_alt++;
                }
            }
            
            if ($images_without_alt > 0) {
                $issues[] = [
                    'page' => $filename,
                    'type' => 'images_no_alt',
                    'priority' => 'medium',
                    'issue' => $images_without_alt . ' images missing alt text',
                    'recommendation' => 'Add descriptive alt text to all images',
                    'auto_fixable' => false
                ];
            }
        }
        
        // Check 7: Thin content (less than 300 words)
        $text_content = strip_tags($content);
        $word_count = str_word_count($text_content);
        
        if ($word_count < 300 && $word_count > 0) {
            $issues[] = [
                'page' => $filename,
                'type' => 'thin_content',
                'priority' => 'low',
                'issue' => 'Thin content (' . $word_count . ' words)',
                'recommendation' => 'Add more content (minimum 300 words recommended)',
                'auto_fixable' => false
            ];
        }
        
        // Check 8: Missing or broken links
        if (preg_match_all('/<a\s+href\s*=\s*["\']([^"\']+)["\']/i', $content, $link_matches)) {
            $external_links = 0;
            foreach ($link_matches[1] as $href) {
                if (strpos($href, 'http') === 0 && strpos($href, 'gcmsafetynets.in') === false) {
                    $external_links++;
                }
            }
            
            if ($external_links === 0) {
                $issues[] = [
                    'page' => $filename,
                    'type' => 'no_external_links',
                    'priority' => 'low',
                    'issue' => 'No external links found',
                    'recommendation' => 'Consider adding relevant external links for credibility',
                    'auto_fixable' => false
                ];
            }
        }
        
        return $issues;
    }
    
    public function autoFix($filepath, $filename, $issue_type) {
        $content = file_get_contents($filepath);
        $fixed = false;
        
        switch ($issue_type) {
            case 'missing_meta_description':
                // Generate basic meta description from filename
                $title = ucwords(str_replace(['-', '.php', '_'], ' ', $filename));
                $meta_desc = "Professional " . $title . " services in Chennai. Get expert installation, best quality products, and affordable prices. Call +91 91213 99234 for free consultation.";
                
                // Find where to insert
                if (preg_match('/(define\(.*?GCM_INIT.*?\);)/s', $content, $matches)) {
                    $insert_after = $matches[0];
                    $new_code = $insert_after . "\n\n\$meta_description = '" . $meta_desc . "';";
                    $content = str_replace($insert_after, $new_code, $content);
                    $fixed = true;
                }
                break;
                
            case 'missing_title':
                // Generate title from filename
                $title = ucwords(str_replace(['-', '.php', '_'], ' ', $filename)) . ' | GCM Netting Solutions Chennai';
                
                if (preg_match('/(define\(.*?GCM_INIT.*?\);)/s', $content, $matches)) {
                    $insert_after = $matches[0];
                    $new_code = $insert_after . "\n\n\$page_title = '" . $title . "';";
                    $content = str_replace($insert_after, $new_code, $content);
                    $fixed = true;
                }
                break;
                
            case 'missing_keywords':
                // Generate keywords from filename
                $keywords = str_replace(['-in-', '-', '.php'], [' ', ', ', ''], $filename);
                $keywords .= ', chennai, safety nets, installation';
                
                if (preg_match('/(define\(.*?GCM_INIT.*?\);)/s', $content, $matches)) {
                    $insert_after = $matches[0];
                    $new_code = $insert_after . "\n\n\$meta_keywords = '" . $keywords . "';";
                    $content = str_replace($insert_after, $new_code, $content);
                    $fixed = true;
                }
                break;
        }
        
        if ($fixed) {
            file_put_contents($filepath, $content);
            return true;
        }
        
        return false;
    }
}

// Handle requests
if ($action === 'scan') {
    $scanner = new SEOScanner();
    $results = $scanner->scanAllPages($base_dir);
    
    echo json_encode([
        'success' => true,
        'results' => $results
    ]);
    
} elseif ($action === 'auto_fix') {
    $page = $_POST['page'] ?? '';
    $issue_type = $_POST['issue_type'] ?? '';
    
    if (empty($page) || empty($issue_type)) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
        exit;
    }
    
    $filepath = $base_dir . '/' . $page;
    
    if (!file_exists($filepath)) {
        echo json_encode(['success' => false, 'message' => 'File not found']);
        exit;
    }
    
    $scanner = new SEOScanner();
    $fixed = $scanner->autoFix($filepath, $page, $issue_type);
    
    if ($fixed) {
        echo json_encode(['success' => true, 'message' => 'Issue fixed automatically']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Could not auto-fix this issue']);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
