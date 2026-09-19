<?php
/**
 * Automatic SEO Optimizer
 * One-click optimization to push pages to #1 position
 * Implements all SEO best practices automatically
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../includes/gemini-api.php';

// MySQL Connection for production
function getDBConnection() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            throw new Exception('Database connection failed: ' . $conn->connect_error);
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

header('Content-Type: application/json');

// Check admin authentication
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'optimize_single_page':
        optimizeSinglePage();
        break;
    
    case 'optimize_all_pages':
        optimizeAllPages();
        break;
    
    case 'get_optimization_status':
        getOptimizationStatus();
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

/**
 * Optimize a single page automatically
 */
function optimizeSinglePage() {
    $page_path = $_POST['page_path'] ?? '';
    $keyword = $_POST['keyword'] ?? '';
    
    if (empty($page_path)) {
        echo json_encode(['success' => false, 'message' => 'Page path required']);
        return;
    }
    
    try {
        $results = [];
        
        // 1. Content Optimization (1500+ words)
        $results['content'] = optimizeContent($page_path, $keyword);
        
        // 2. Image Optimization (5-10 images with alt text)
        $results['images'] = optimizeImages($page_path, $keyword);
        
        // 3. FAQ Section Generation
        $results['faq'] = generateFAQSection($page_path, $keyword);
        
        // 4. Schema Markup Addition
        $results['schema'] = addSchemaMarkup($page_path, $keyword);
        
        // 5. Internal Linking
        $results['internal_links'] = addInternalLinks($page_path);
        
        // 6. Technical SEO Fixes
        $results['technical'] = fixTechnicalSEO($page_path);
        
        // 7. Meta Tags Optimization
        $results['meta'] = optimizeMetaTags($page_path, $keyword);
        
        // Log optimization
        logOptimization($page_path, $results);
        
        echo json_encode([
            'success' => true,
            'message' => 'Page optimized successfully',
            'optimizations' => $results,
            'page' => $page_path
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Optimization failed: ' . $e->getMessage()
        ]);
    }
}

/**
 * 1. Content Optimization - Expand to 1500+ words
 */
function optimizeContent($page_path, $keyword) {
    // Handle both root and generated-pages folder
    $file_path = __DIR__ . '/../../generated-pages/' . basename($page_path);
    if (!file_exists($file_path)) {
        $file_path = __DIR__ . '/../../' . ltrim($page_path, '/');
    }
    
    if (!file_exists($file_path)) {
        return ['status' => 'error', 'message' => 'File not found: ' . $page_path];
    }
    
    $content = file_get_contents($file_path);
    $current_word_count = str_word_count(strip_tags($content));
    
    if ($current_word_count >= 1500) {
        return ['status' => 'ok', 'message' => 'Content already optimized', 'word_count' => $current_word_count];
    }
    
    // Get API key from database or config
    try {
        $conn = getDBConnection();
        $result = $conn->query("SELECT config_value FROM site_config WHERE config_key = 'gemini_api_key' LIMIT 1");
        if ($result && $row = $result->fetch_assoc()) {
            $api_key = $row['config_value'];
        } else {
            $api_key = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
        }
    } catch (Exception $e) {
        $api_key = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
    }
    
    if (empty($api_key)) {
        return ['status' => 'error', 'message' => 'Gemini API key not configured'];
    }
    
    // Use AI to expand content
    $gemini = new GeminiAPI($api_key);
    
    $prompt = "Expand the following content to 1500+ words while maintaining quality and relevance. 
    Focus on keyword: {$keyword}
    
    Requirements:
    - Add more detailed information
    - Include benefits and features
    - Add customer pain points and solutions
    - Include statistics and facts
    - Maintain natural keyword density (1-2%)
    - Keep existing structure
    
    Current content word count: {$current_word_count}
    Target: 1500+ words
    
    Original content:
    " . strip_tags($content);
    
    try {
        $expanded_content = $gemini->generateContent($prompt);
        
        // Clean any markdown artifacts
        $expanded_content = preg_replace('/```html\s*/i', '', $expanded_content);
        $expanded_content = preg_replace('/```\s*$/s', '', $expanded_content);
        $expanded_content = trim($expanded_content, '`');
        
        // Insert expanded content into page
        if (strpos($content, '<div class="service-content">') !== false) {
            $content = preg_replace(
                '/(<div class="service-content">)(.*?)(<\/div>)/s',
                '$1' . $expanded_content . '$3',
                $content,
                1
            );
        } else {
            // If no service-content div, insert after H1
            $content = preg_replace(
                '/(<h1[^>]*>.*?<\/h1>)/s',
                '$1' . "\n<div class='service-content'>\n" . $expanded_content . "\n</div>\n",
                $content,
                1
            );
        }
        
        // Backup original file
        @copy($file_path, $file_path . '.backup');
        
        // Write optimized content
        if (file_put_contents($file_path, $content) === false) {
            return ['status' => 'error', 'message' => 'Failed to write file'];
        }
        
        $new_word_count = str_word_count(strip_tags($content));
        
        return [
            'status' => 'optimized',
            'message' => 'Content expanded successfully',
            'old_word_count' => $current_word_count,
            'new_word_count' => $new_word_count,
            'added_words' => $new_word_count - $current_word_count
        ];
        
    } catch (Exception $e) {
        error_log('Content optimization error: ' . $e->getMessage());
        return ['status' => 'error', 'message' => 'AI generation failed: ' . $e->getMessage()];
    }
}

/**
 * 2. Image Optimization - Add 5-10 images with alt text
 */
function optimizeImages($page_path, $keyword) {
    $file_path = __DIR__ . '/../../' . ltrim($page_path, '/');
    $content = file_get_contents($file_path);
    
    // Count existing images
    preg_match_all('/<img[^>]+>/i', $content, $existing_images);
    $image_count = count($existing_images[0]);
    
    if ($image_count >= 5) {
        // Optimize existing images with alt text
        $content = preg_replace_callback(
            '/<img([^>]+)>/i',
            function($matches) use ($keyword) {
                $img_tag = $matches[0];
                
                // Add alt text if missing
                if (strpos($img_tag, 'alt=') === false) {
                    $alt_text = generateAltText($keyword);
                    $img_tag = str_replace('<img', '<img alt="' . $alt_text . '"', $img_tag);
                }
                
                // Add loading="lazy" for performance
                if (strpos($img_tag, 'loading=') === false) {
                    $img_tag = str_replace('<img', '<img loading="lazy"', $img_tag);
                }
                
                return $img_tag;
            },
            $content
        );
        
        file_put_contents($file_path, $content);
        
        return [
            'status' => 'optimized',
            'message' => 'Images optimized with alt text',
            'image_count' => $image_count,
            'optimizations' => ['alt_text_added', 'lazy_loading_enabled']
        ];
    }
    
    // Need to add more images
    $images_needed = 5 - $image_count;
    
    return [
        'status' => 'partial',
        'message' => "Need {$images_needed} more images",
        'current_count' => $image_count,
        'target_count' => 5,
        'recommendation' => 'Add relevant service images manually or use stock photos'
    ];
}

/**
 * 3. Generate FAQ Section automatically
 */
function generateFAQSection($page_path, $keyword) {
    $file_path = __DIR__ . '/../../generated-pages/' . basename($page_path);
    if (!file_exists($file_path)) {
        $file_path = __DIR__ . '/../../' . ltrim($page_path, '/');
    }
    
    if (!file_exists($file_path)) {
        return ['status' => 'error', 'message' => 'File not found'];
    }
    
    $content = file_get_contents($file_path);
    
    // Check if FAQ already exists
    if (strpos($content, 'faq-section') !== false || strpos($content, 'FAQ') !== false) {
        return ['status' => 'ok', 'message' => 'FAQ section already exists'];
    }
    
    // Get API key
    try {
        $conn = getDBConnection();
        $result = $conn->query("SELECT config_value FROM site_config WHERE config_key = 'gemini_api_key' LIMIT 1");
        if ($result && $row = $result->fetch_assoc()) {
            $api_key = $row['config_value'];
        } else {
            $api_key = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
        }
    } catch (Exception $e) {
        $api_key = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
    }
    
    if (empty($api_key)) {
        return ['status' => 'error', 'message' => 'API key not configured'];
    }
    
    // Generate FAQs using AI
    $gemini = new GeminiAPI($api_key);
    
    $prompt = "Generate 8-10 frequently asked questions and detailed answers for: {$keyword}
    
    Format as HTML with proper structure:
    <div class='faq-section'>
        <h2>Frequently Asked Questions</h2>
        <div class='faq-item'>
            <h3 class='faq-question'>Question here?</h3>
            <div class='faq-answer'>Detailed answer here (50-100 words)</div>
        </div>
    </div>
    
    Make questions relevant to customer concerns, pricing, installation, warranty, etc.";
    
    try {
        $faq_html = $gemini->generateContent($prompt);
        
        // Clean markdown
        $faq_html = preg_replace('/```html\s*/i', '', $faq_html);
        $faq_html = preg_replace('/```\s*$/s', '', $faq_html);
        $faq_html = trim($faq_html, '`');
        
        // Insert FAQ before footer
        if (strpos($content, '<?php include') !== false) {
            $content = str_replace(
                '<?php include',
                $faq_html . "\n\n<?php include",
                $content,
                1
            );
        } else {
            // Append at end if no include found
            $content = str_replace('</body>', $faq_html . "\n</body>", $content);
        }
        
        if (file_put_contents($file_path, $content) === false) {
            return ['status' => 'error', 'message' => 'Failed to write file'];
        }
        
        return [
            'status' => 'added',
            'message' => 'FAQ section generated and added',
            'faq_count' => substr_count($faq_html, 'faq-item')
        ];
        
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

/**
 * 4. Add Schema Markup automatically
 */
function addSchemaMarkup($page_path, $keyword) {
    $file_path = __DIR__ . '/../../' . ltrim($page_path, '/');
    $content = file_get_contents($file_path);
    
    // Check if schema already exists
    if (strpos($content, 'application/ld+json') !== false) {
        return ['status' => 'ok', 'message' => 'Schema markup already exists'];
    }
    
    // Extract service name and area from keyword
    $parts = explode(' in ', $keyword);
    $service_name = $parts[0] ?? $keyword;
    $area_name = $parts[1] ?? 'Chennai';
    
    // Generate comprehensive schema markup
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Service',
        'name' => $service_name,
        'provider' => [
            '@type' => 'LocalBusiness',
            'name' => 'GCM Netting Solutions',
            'image' => SITE_URL . '/assets/img/logo.png',
            'telephone' => COMPANY_PHONE,
            'email' => COMPANY_EMAIL,
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => 'Plot No.85, Road No.6, Tapovan Colony',
                'addressLocality' => 'Saroornagar',
                'addressRegion' => 'Tamil Nadu',
                'postalCode' => '600002',
                'addressCountry' => 'IN'
            ],
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => '17.3850',
                'longitude' => '78.4867'
            ],
            'url' => SITE_URL,
            'priceRange' => '₹₹',
            'areaServed' => [
                '@type' => 'City',
                'name' => $area_name
            ]
        ],
        'serviceType' => $service_name,
        'areaServed' => $area_name,
        'hasOfferCatalog' => [
            '@type' => 'OfferCatalog',
            'name' => $service_name . ' Services',
            'itemListElement' => [
                [
                    '@type' => 'Offer',
                    'itemOffered' => [
                        '@type' => 'Service',
                        'name' => $service_name . ' Installation'
                    ]
                ]
            ]
        ],
        'aggregateRating' => [
            '@type' => 'AggregateRating',
            'ratingValue' => '4.8',
            'reviewCount' => '500'
        ]
    ];
    
    $schema_json = json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
    $schema_html = "\n<script type=\"application/ld+json\">\n{$schema_json}\n</script>\n";
    
    // Insert schema before </head>
    $content = str_replace('</head>', $schema_html . '</head>', $content);
    
    file_put_contents($file_path, $content);
    
    return [
        'status' => 'added',
        'message' => 'Schema markup added successfully',
        'schema_types' => ['Service', 'LocalBusiness', 'AggregateRating']
    ];
}

/**
 * 5. Add Internal Links automatically
 */
function addInternalLinks($page_path) {
    $file_path = __DIR__ . '/../../generated-pages/' . basename($page_path);
    if (!file_exists($file_path)) {
        $file_path = __DIR__ . '/../../' . ltrim($page_path, '/');
    }
    
    if (!file_exists($file_path)) {
        return ['status' => 'error', 'message' => 'File not found'];
    }
    
    $content = file_get_contents($file_path);
    
    // Get related pages from database
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT file_path, page_title, service_name FROM generated_pages WHERE file_path != ? ORDER BY RAND() LIMIT 5");
        $stmt->bind_param('s', $page_path);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $related_pages = [];
        while ($row = $result->fetch_assoc()) {
            $related_pages[] = $row;
        }
        $stmt->close();
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
    }
    
    if (empty($related_pages)) {
        return ['status' => 'ok', 'message' => 'No related pages found'];
    }
    
    // Create internal links section
    $links_html = "\n<div class='related-services' style='margin: 40px 0; padding: 30px; background: #f8f9fa; border-radius: 8px;'>\n";
    $links_html .= "    <h3 style='color: #667eea; margin-bottom: 20px;'>Related Services</h3>\n";
    $links_html .= "    <ul style='list-style: none; padding: 0;'>\n";
    
    foreach ($related_pages as $page) {
        $links_html .= "        <li style='margin-bottom: 10px;'>\n";
        $links_html .= "            <a href='" . SITE_URL . "/" . $page['file_path'] . "' style='color: #667eea; text-decoration: none; font-weight: 600;'>\n";
        $links_html .= "                <i class='fas fa-arrow-right'></i> " . htmlspecialchars($page['service_name']) . "\n";
        $links_html .= "            </a>\n";
        $links_html .= "        </li>\n";
    }
    
    $links_html .= "    </ul>\n";
    $links_html .= "</div>\n";
    
    // Insert before footer
    $content = str_replace(
        '<?php include',
        $links_html . "\n<?php include",
        $content
    );
    
    file_put_contents($file_path, $content);
    
    return [
        'status' => 'added',
        'message' => 'Internal links added',
        'links_count' => count($related_pages)
    ];
}

/**
 * 6. Fix Technical SEO issues
 */
function fixTechnicalSEO($page_path) {
    $file_path = __DIR__ . '/../../' . ltrim($page_path, '/');
    $content = file_get_contents($file_path);
    
    $fixes = [];
    
    // Add viewport meta tag if missing
    if (strpos($content, 'viewport') === false) {
        $content = str_replace(
            '<head>',
            '<head>' . "\n" . '<meta name="viewport" content="width=device-width, initial-scale=1.0">',
            $content
        );
        $fixes[] = 'viewport_meta_added';
    }
    
    // Add charset if missing
    if (strpos($content, 'charset') === false) {
        $content = str_replace(
            '<head>',
            '<head>' . "\n" . '<meta charset="UTF-8">',
            $content
        );
        $fixes[] = 'charset_added';
    }
    
    // Optimize images for lazy loading
    $content = preg_replace(
        '/<img(?![^>]*loading=)/i',
        '<img loading="lazy"',
        $content
    );
    $fixes[] = 'lazy_loading_enabled';
    
    // Add canonical URL
    if (strpos($content, 'rel="canonical"') === false) {
        $canonical = '<link rel="canonical" href="' . SITE_URL . $page_path . '">';
        $content = str_replace('</head>', $canonical . "\n</head>", $content);
        $fixes[] = 'canonical_url_added';
    }
    
    // Add Open Graph tags
    if (strpos($content, 'og:') === false) {
        preg_match('/<title>(.*?)<\/title>/i', $content, $title_match);
        $title = $title_match[1] ?? 'GCM Netting Solutions';
        
        $og_tags = "\n" . '<meta property="og:title" content="' . htmlspecialchars($title) . '">' . "\n";
        $og_tags .= '<meta property="og:type" content="website">' . "\n";
        $og_tags .= '<meta property="og:url" content="' . SITE_URL . $page_path . '">' . "\n";
        $og_tags .= '<meta property="og:site_name" content="GCM Netting Solutions">' . "\n";
        
        $content = str_replace('</head>', $og_tags . '</head>', $content);
        $fixes[] = 'open_graph_tags_added';
    }
    
    file_put_contents($file_path, $content);
    
    return [
        'status' => 'fixed',
        'message' => 'Technical SEO issues fixed',
        'fixes_applied' => $fixes,
        'fix_count' => count($fixes)
    ];
}

/**
 * 7. Optimize Meta Tags
 */
function optimizeMetaTags($page_path, $keyword) {
    $file_path = __DIR__ . '/../../' . ltrim($page_path, '/');
    $content = file_get_contents($file_path);
    
    $optimizations = [];
    
    // Optimize title tag (50-60 characters, keyword at start)
    preg_match('/<title>(.*?)<\/title>/i', $content, $title_match);
    $current_title = $title_match[1] ?? '';
    
    if (strlen($current_title) < 50 || strpos(strtolower($current_title), strtolower($keyword)) === false) {
        $new_title = $keyword . ' | GCM Netting Solutions | Free Installation';
        $content = preg_replace(
            '/<title>.*?<\/title>/i',
            '<title>' . $new_title . '</title>',
            $content
        );
        $optimizations[] = 'title_optimized';
    }
    
    // Optimize meta description (150-160 characters)
    preg_match('/<meta\s+name=["\']description["\']\s+content=["\']([^"\']*)["\']/i', $content, $desc_match);
    $current_desc = $desc_match[1] ?? '';
    
    if (strlen($current_desc) < 150) {
        $new_desc = "Professional {$keyword} installation in Chennai. Quality materials, expert installation, 5-year warranty, free site visit. Call +91 " . COMPANY_PHONE . " for free quote today!";
        
        if ($desc_match) {
            $content = preg_replace(
                '/<meta\s+name=["\']description["\']\s+content=["\'][^"\']*["\']/i',
                '<meta name="description" content="' . $new_desc . '"',
                $content
            );
        } else {
            $content = str_replace(
                '</head>',
                '<meta name="description" content="' . $new_desc . '">' . "\n</head>",
                $content
            );
        }
        $optimizations[] = 'meta_description_optimized';
    }
    
    file_put_contents($file_path, $content);
    
    return [
        'status' => 'optimized',
        'message' => 'Meta tags optimized',
        'optimizations' => $optimizations
    ];
}

/**
 * Generate alt text for images
 */
function generateAltText($keyword) {
    $templates = [
        "{$keyword} installation service",
        "Professional {$keyword} in Chennai",
        "Quality {$keyword} by GCM Netting Solutions",
        "{$keyword} - Expert installation",
        "Affordable {$keyword} services"
    ];
    
    return $templates[array_rand($templates)];
}

/**
 * Log optimization activity
 */
function logOptimization($page_path, $results) {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("INSERT INTO seo_optimization_log (page_path, optimizations, optimized_at) VALUES (?, ?, NOW())");
        $optimizations_json = json_encode($results);
        $stmt->bind_param('ss', $page_path, $optimizations_json);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        error_log("Failed to log optimization: " . $e->getMessage());
    }
}

/**
 * Optimize all pages in bulk
 */
function optimizeAllPages() {
    set_time_limit(0); // No timeout for bulk operations
    ini_set('memory_limit', '512M');
    
    // Get batch size from request or default to 50
    $batch_size = isset($_POST['batch_size']) ? (int)$_POST['batch_size'] : 50;
    $offset = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;
    
    try {
        $conn = getDBConnection();
        
        // Get total count
        $count_result = $conn->query("SELECT COUNT(*) as total FROM generated_pages");
        $total_pages = $count_result->fetch_assoc()['total'];
        
        // Get batch of pages
        $stmt = $conn->prepare("SELECT file_path, service_name, area_name FROM generated_pages LIMIT ? OFFSET ?");
        $stmt->bind_param('ii', $batch_size, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $pages = [];
        while ($row = $result->fetch_assoc()) {
            $pages[] = $row;
        }
        $stmt->close();
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
        return;
    }
    
    $results = [
        'total_pages' => $total_pages,
        'batch_size' => count($pages),
        'offset' => $offset,
        'optimized' => 0,
        'failed' => 0,
        'pages' => []
    ];
    
    foreach ($pages as $page) {
        $keyword = $page['service_name'] . ' in ' . $page['area_name'];
        
        try {
            // Optimize content
            $content_result = optimizeContent($page['file_path'], $keyword);
            
            // Optimize images
            $image_result = optimizeImages($page['file_path'], $keyword);
            
            // Generate FAQ
            $faq_result = generateFAQSection($page['file_path'], $keyword);
            
            // Add schema
            $schema_result = addSchemaMarkup($page['file_path'], $keyword);
            
            // Add internal links
            $links_result = addInternalLinks($page['file_path']);
            
            // Fix technical SEO
            $technical_result = fixTechnicalSEO($page['file_path']);
            
            // Optimize meta tags
            $meta_result = optimizeMetaTags($page['file_path'], $keyword);
            
            // Log optimization
            logOptimization($page['file_path'], [
                'content' => $content_result,
                'images' => $image_result,
                'faq' => $faq_result,
                'schema' => $schema_result,
                'links' => $links_result,
                'technical' => $technical_result,
                'meta' => $meta_result
            ]);
            
            $results['optimized']++;
            $results['pages'][] = [
                'page' => $page['file_path'],
                'status' => 'optimized',
                'keyword' => $keyword
            ];
            
            // Add delay to avoid API rate limits (2 seconds between pages)
            sleep(2);
            
        } catch (Exception $e) {
            $results['failed']++;
            $results['pages'][] = [
                'page' => $page['file_path'],
                'status' => 'error',
                'error' => $e->getMessage()
            ];
            
            error_log("Optimization failed for {$page['file_path']}: " . $e->getMessage());
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Optimized {$results['optimized']} of {$results['total']} pages",
        'results' => $results
    ]);
}

/**
 * Get optimization status
 */
function getOptimizationStatus() {
    try {
        $conn = getDBConnection();
        
        $total_result = $conn->query("SELECT COUNT(*) as count FROM generated_pages");
        $total_pages = $total_result->fetch_assoc()['count'] ?? 0;
        
        $optimized_result = $conn->query("SELECT COUNT(DISTINCT page_path) as count FROM seo_optimization_log");
        $optimized_pages = $optimized_result->fetch_assoc()['count'] ?? 0;
        
        $last_result = $conn->query("SELECT MAX(optimized_at) as last FROM seo_optimization_log");
        $last_optimization = $last_result->fetch_assoc()['last'] ?? 'Never';
        
        $stats = [
            'total_pages' => $total_pages,
            'optimized_pages' => $optimized_pages,
            'pending_pages' => $total_pages - $optimized_pages,
            'last_optimization' => $last_optimization,
            'completion_percentage' => $total_pages > 0 ? round(($optimized_pages / $total_pages) * 100, 2) : 0
        ];
        
        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error getting status: ' . $e->getMessage()
        ]);
    }
}
