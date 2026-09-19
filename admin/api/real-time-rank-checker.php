<?php
/**
 * Real-Time Google Rank Checker
 * Checks actual Google search positions for your pages
 * Uses web scraping to get real positions (no API needed)
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

// Check admin authentication
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'check_single':
        checkSingleKeyword();
        break;
    
    case 'check_bulk':
        checkBulkKeywords();
        break;
    
    case 'get_saved_rankings':
        getSavedRankings();
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

/**
 * Check single keyword ranking in real-time
 */
function checkSingleKeyword() {
    $keyword = $_POST['keyword'] ?? '';
    $page_url = $_POST['page_url'] ?? '';
    
    if (empty($keyword)) {
        echo json_encode(['success' => false, 'message' => 'Keyword required']);
        return;
    }
    
    try {
        $position = checkGooglePosition($keyword, SITE_URL);
        
        // Save to database
        $db = Database::getInstance();
        $db->execute(
            "INSERT INTO seo_rankings (page_url, keyword, position, clicks, impressions, ctr, last_updated, is_real_data)
             VALUES (?, ?, ?, 0, 0, 0, NOW(), 1)
             ON DUPLICATE KEY UPDATE position=VALUES(position), last_updated=NOW(), is_real_data=1",
            [$page_url ?: '/', $keyword, $position]
        );
        
        echo json_encode([
            'success' => true,
            'keyword' => $keyword,
            'position' => $position,
            'search_page' => ceil($position / 10),
            'message' => $position > 0 ? "Found at position #{$position}" : "Not found in top 100",
            'recommendations' => getPositionRecommendations($position, $keyword)
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error checking position: ' . $e->getMessage()
        ]);
    }
}

/**
 * Check multiple keywords in bulk
 */
function checkBulkKeywords() {
    $keywords = json_decode($_POST['keywords'] ?? '[]', true);
    
    if (empty($keywords)) {
        echo json_encode(['success' => false, 'message' => 'No keywords provided']);
        return;
    }
    
    $results = [];
    $db = Database::getInstance();
    
    foreach ($keywords as $item) {
        $keyword = $item['keyword'];
        $page_url = $item['page_url'] ?? '/';
        
        try {
            $position = checkGooglePosition($keyword, SITE_URL);
            
            // Save to database
            $db->execute(
                "INSERT INTO seo_rankings (page_url, keyword, position, clicks, impressions, ctr, last_updated, is_real_data)
                 VALUES (?, ?, ?, 0, 0, 0, NOW(), 1)
                 ON DUPLICATE KEY UPDATE position=VALUES(position), last_updated=NOW(), is_real_data=1",
                [$page_url, $keyword, $position]
            );
            
            $results[] = [
                'keyword' => $keyword,
                'page_url' => $page_url,
                'position' => $position,
                'search_page' => ceil($position / 10),
                'status' => $position > 0 ? 'found' : 'not_found'
            ];
            
            // Add delay to avoid rate limiting
            sleep(2);
            
        } catch (Exception $e) {
            $results[] = [
                'keyword' => $keyword,
                'position' => 0,
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'results' => $results,
        'total_checked' => count($results)
    ]);
}

/**
 * Check actual Google position for a keyword
 * Uses web scraping to get real positions
 */
function checkGooglePosition($keyword, $site_url) {
    $site_domain = parse_url($site_url, PHP_URL_HOST);
    $position = 0;
    
    // Search through first 10 pages (100 results)
    for ($page = 0; $page < 10; $page++) {
        $start = $page * 10;
        
        // Build Google search URL
        $search_url = 'https://www.google.com/search?' . http_build_query([
            'q' => $keyword,
            'start' => $start,
            'num' => 10,
            'hl' => 'en'
        ]);
        
        // Fetch search results
        $html = fetchGoogleResults($search_url);
        
        if (!$html) {
            continue;
        }
        
        // Parse results and find our domain
        preg_match_all('/<a\s+href="([^"]+)"[^>]*>/i', $html, $matches);
        
        $result_position = 1 + ($page * 10);
        
        foreach ($matches[1] as $url) {
            // Check if this is a real result (not Google's own links)
            if (strpos($url, '/url?q=') === 0) {
                // Extract actual URL
                parse_str(parse_url($url, PHP_URL_QUERY), $params);
                $actual_url = $params['q'] ?? '';
                
                // Check if it's our domain
                if (strpos($actual_url, $site_domain) !== false) {
                    return $result_position;
                }
                
                $result_position++;
            }
        }
    }
    
    return 0; // Not found in top 100
}

/**
 * Fetch Google search results with proper headers
 */
function fetchGoogleResults($url) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9',
            'Accept-Encoding: gzip, deflate',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests: 1'
        ]
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        return false;
    }
    
    return $response;
}

/**
 * Get recommendations based on current position
 */
function getPositionRecommendations($position, $keyword) {
    if ($position === 0) {
        return [
            'status' => 'critical',
            'message' => 'Page not ranking in top 100',
            'actions' => [
                '1. Create high-quality content with 1500+ words',
                '2. Add keyword to title, H1, and first paragraph',
                '3. Build 10+ quality backlinks',
                '4. Optimize meta description with keyword',
                '5. Add internal links from other pages',
                '6. Improve page load speed',
                '7. Add schema markup',
                '8. Create content clusters around this keyword'
            ]
        ];
    } elseif ($position >= 1 && $position <= 3) {
        return [
            'status' => 'excellent',
            'message' => 'Great! You\'re in top 3',
            'actions' => [
                '1. Maintain current content quality',
                '2. Update content regularly (monthly)',
                '3. Monitor competitors in top 3',
                '4. Build more quality backlinks',
                '5. Improve user engagement metrics',
                '6. Add fresh statistics and data'
            ]
        ];
    } elseif ($position >= 4 && $position <= 10) {
        return [
            'status' => 'good',
            'message' => 'On first page! Push to top 3',
            'actions' => [
                '1. Expand content by 30-50%',
                '2. Add more images and videos',
                '3. Build 5+ quality backlinks',
                '4. Improve internal linking',
                '5. Optimize for featured snippets',
                '6. Improve page speed',
                '7. Add FAQ schema markup'
            ]
        ];
    } elseif ($position >= 11 && $position <= 20) {
        return [
            'status' => 'needs_work',
            'message' => 'On page 2 - needs improvement',
            'actions' => [
                '1. Rewrite content to be more comprehensive',
                '2. Add keyword variations naturally',
                '3. Build 8+ quality backlinks',
                '4. Improve title tag CTR',
                '5. Add more multimedia content',
                '6. Fix technical SEO issues',
                '7. Improve mobile experience',
                '8. Add customer reviews/testimonials'
            ]
        ];
    } else {
        return [
            'status' => 'poor',
            'message' => 'Ranking beyond page 2 - major work needed',
            'actions' => [
                '1. Complete content overhaul (2000+ words)',
                '2. Keyword research - target easier keywords first',
                '3. Build 15+ quality backlinks',
                '4. Fix all technical SEO issues',
                '5. Improve site architecture',
                '6. Add comprehensive FAQ section',
                '7. Create supporting blog posts',
                '8. Optimize for local SEO',
                '9. Improve E-A-T signals',
                '10. Monitor and fix crawl errors'
            ]
        ];
    }
}

/**
 * Get saved rankings from database
 */
function getSavedRankings() {
    $db = Database::getInstance();
    
    $filter = $_GET['filter'] ?? 'all';
    $limit = $_GET['limit'] ?? 50;
    
    $where = '';
    if ($filter === 'top10') {
        $where = 'WHERE position BETWEEN 1 AND 10';
    } elseif ($filter === 'page2') {
        $where = 'WHERE position BETWEEN 11 AND 20';
    } elseif ($filter === 'beyond') {
        $where = 'WHERE position > 20';
    } elseif ($filter === 'not_found') {
        $where = 'WHERE position = 0';
    }
    
    $rankings = $db->fetchAll(
        "SELECT *, 
         CASE 
            WHEN position = 0 THEN 'Not Found'
            WHEN position BETWEEN 1 AND 3 THEN 'Excellent'
            WHEN position BETWEEN 4 AND 10 THEN 'Good'
            WHEN position BETWEEN 11 AND 20 THEN 'Needs Work'
            ELSE 'Poor'
         END as status_label
         FROM seo_rankings 
         {$where} 
         ORDER BY position ASC, last_updated DESC 
         LIMIT ?",
        [$limit]
    );
    
    echo json_encode([
        'success' => true,
        'rankings' => $rankings ?? [],
        'total' => count($rankings ?? [])
    ]);
}
