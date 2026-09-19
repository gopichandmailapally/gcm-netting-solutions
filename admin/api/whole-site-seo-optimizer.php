<?php
/**
 * Whole Site SEO Optimizer
 * Optimizes ALL pages: Homepage, Blogs, Reviews, FAQs, Service Pages, Static Pages
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../includes/gemini-api.php';

header('Content-Type: application/json');

// Check admin authentication
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// MySQL Connection
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

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'optimize_all_pages':
        optimizeAllPages();
        break;
    
    case 'optimize_homepage':
        optimizeHomepage();
        break;
    
    case 'optimize_blogs':
        optimizeBlogs();
        break;
    
    case 'optimize_static_pages':
        optimizeStaticPages();
        break;
    
    case 'get_status':
        getOptimizationStatus();
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

/**
 * Optimize entire website
 */
function optimizeAllPages() {
    set_time_limit(0);
    ini_set('memory_limit', '512M');
    
    $results = [
        'homepage' => optimizeHomepage(true),
        'static_pages' => optimizeStaticPages(true),
        'blogs' => optimizeBlogs(true),
        'service_pages' => optimizeServicePages(true)
    ];
    
    echo json_encode([
        'success' => true,
        'message' => 'Whole site optimization complete',
        'results' => $results
    ]);
}

/**
 * Optimize Homepage
 */
function optimizeHomepage($return_data = false) {
    $file_path = __DIR__ . '/../../index.php';
    
    if (!file_exists($file_path)) {
        $result = ['status' => 'error', 'message' => 'Homepage not found'];
        return $return_data ? $result : json_encode($result);
    }
    
    $content = file_get_contents($file_path);
    $optimizations = [];
    
    try {
        // 1. Add comprehensive schema markup for homepage
        if (strpos($content, 'application/ld+json') === false) {
            $schema = generateHomepageSchema();
            $content = str_replace('</head>', $schema . "\n</head>", $content);
            $optimizations[] = 'homepage_schema_added';
        }
        
        // 2. Optimize meta tags
        $content = optimizeMetaTags($content, 'GCM Netting Solutions - #1 Safety Nets Installation in Chennai');
        $optimizations[] = 'meta_tags_optimized';
        
        // 3. Add Open Graph tags
        if (strpos($content, 'og:title') === false) {
            $og_tags = generateOpenGraphTags('Homepage');
            $content = str_replace('</head>', $og_tags . "\n</head>", $content);
            $optimizations[] = 'open_graph_added';
        }
        
        // 4. Optimize images
        $content = optimizeAllImages($content);
        $optimizations[] = 'images_optimized';
        
        // 5. Add internal linking
        $content = addInternalLinksToContent($content);
        $optimizations[] = 'internal_links_added';
        
        // Backup and save
        @copy($file_path, $file_path . '.backup');
        file_put_contents($file_path, $content);
        
        $result = [
            'status' => 'optimized',
            'message' => 'Homepage optimized successfully',
            'optimizations' => $optimizations
        ];
        
        // Log to database
        logOptimization('index.php', $result);
        
        return $return_data ? $result : json_encode(['success' => true, 'result' => $result]);
        
    } catch (Exception $e) {
        $result = ['status' => 'error', 'message' => $e->getMessage()];
        return $return_data ? $result : json_encode(['success' => false, 'result' => $result]);
    }
}

/**
 * Optimize all blog posts
 */
function optimizeBlogs($return_data = false) {
    try {
        $conn = getDBConnection();
        $result = $conn->query("SELECT id, slug, title, content FROM blog_posts WHERE status = 'published' LIMIT 100");
        
        if (!$result) {
            throw new Exception('Failed to fetch blog posts');
        }
        
        $optimized = 0;
        $failed = 0;
        
        while ($blog = $result->fetch_assoc()) {
            try {
                // Optimize blog content
                $optimized_content = optimizeBlogContent($blog);
                
                // Update database
                $stmt = $conn->prepare("UPDATE blog_posts SET content = ?, updated_at = NOW() WHERE id = ?");
                $stmt->bind_param('si', $optimized_content, $blog['id']);
                $stmt->execute();
                $stmt->close();
                
                $optimized++;
                
                // Add delay
                sleep(1);
                
            } catch (Exception $e) {
                $failed++;
                error_log("Blog optimization failed for ID {$blog['id']}: " . $e->getMessage());
            }
        }
        
        $result_data = [
            'status' => 'optimized',
            'message' => "Optimized {$optimized} blog posts",
            'optimized' => $optimized,
            'failed' => $failed
        ];
        
        return $return_data ? $result_data : json_encode(['success' => true, 'result' => $result_data]);
        
    } catch (Exception $e) {
        $result_data = ['status' => 'error', 'message' => $e->getMessage()];
        return $return_data ? $result_data : json_encode(['success' => false, 'result' => $result_data]);
    }
}

/**
 * Optimize static pages (about, contact, etc.)
 */
function optimizeStaticPages($return_data = false) {
    $static_pages = [
        'about.php',
        'contact.php',
        'gallery.php',
        'reviews.php',
        'faqs.php',
        'videos.php',
        'privacy-policy.php',
        'terms-conditions.php'
    ];
    
    $optimized = 0;
    $failed = 0;
    
    foreach ($static_pages as $page) {
        $file_path = __DIR__ . '/../../' . $page;
        
        if (!file_exists($file_path)) {
            $failed++;
            continue;
        }
        
        try {
            $content = file_get_contents($file_path);
            
            // Add schema markup
            if (strpos($content, 'application/ld+json') === false) {
                $schema = generatePageSchema($page);
                $content = str_replace('</head>', $schema . "\n</head>", $content);
            }
            
            // Optimize images
            $content = optimizeAllImages($content);
            
            // Add Open Graph
            if (strpos($content, 'og:title') === false) {
                $og_tags = generateOpenGraphTags($page);
                $content = str_replace('</head>', $og_tags . "\n</head>", $content);
            }
            
            // Backup and save
            @copy($file_path, $file_path . '.backup');
            file_put_contents($file_path, $content);
            
            logOptimization($page, ['status' => 'optimized']);
            $optimized++;
            
        } catch (Exception $e) {
            $failed++;
            error_log("Static page optimization failed for {$page}: " . $e->getMessage());
        }
    }
    
    $result_data = [
        'status' => 'optimized',
        'message' => "Optimized {$optimized} static pages",
        'optimized' => $optimized,
        'failed' => $failed
    ];
    
    return $return_data ? $result_data : json_encode(['success' => true, 'result' => $result_data]);
}

/**
 * Optimize service pages (calls existing optimizer)
 */
function optimizeServicePages($return_data = false) {
    // This calls the existing auto-seo-optimizer for service pages
    require_once __DIR__ . '/auto-seo-optimizer.php';
    
    $_POST['batch_size'] = 50;
    $_POST['offset'] = 0;
    
    ob_start();
    optimizeAllPages();
    $output = ob_get_clean();
    
    $result = json_decode($output, true);
    
    return $return_data ? $result : $output;
}

/**
 * Generate homepage schema markup
 */
function generateHomepageSchema() {
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'LocalBusiness',
        'name' => 'GCM Netting Solutions',
        'image' => SITE_URL . '/assets/img/logo.png',
        'description' => 'Professional safety nets, pigeon nets, bird nets, invisible grills installation in Chennai. 15+ years experience, 10,000+ satisfied customers.',
        'telephone' => '+91-' . COMPANY_PHONE,
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
        'openingHours' => 'Mo-Su 08:00-20:00',
        'areaServed' => [
            ['@type' => 'City', 'name' => 'Chennai'],
            ['@type' => 'State', 'name' => 'Tamil Nadu']
        ],
        'aggregateRating' => [
            '@type' => 'AggregateRating',
            'ratingValue' => '4.8',
            'reviewCount' => '500',
            'bestRating' => '5',
            'worstRating' => '1'
        ],
        'sameAs' => [
            'https://www.facebook.com/gcmsafetynets',
            'https://www.instagram.com/gcmsafetynets',
            'https://twitter.com/gcmsafetynets'
        ]
    ];
    
    return "\n<script type=\"application/ld+json\">\n" . json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n</script>\n";
}

/**
 * Generate page-specific schema
 */
function generatePageSchema($page) {
    $page_name = str_replace(['.php', '-'], [' ', ' '], $page);
    $page_name = ucwords($page_name);
    
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'WebPage',
        'name' => $page_name . ' - GCM Netting Solutions',
        'description' => 'GCM Netting Solutions - ' . $page_name,
        'url' => SITE_URL . '/' . $page,
        'publisher' => [
            '@type' => 'Organization',
            'name' => 'GCM Netting Solutions',
            'logo' => SITE_URL . '/assets/img/logo.png'
        ]
    ];
    
    return "\n<script type=\"application/ld+json\">\n" . json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n</script>\n";
}

/**
 * Generate Open Graph tags
 */
function generateOpenGraphTags($page) {
    $title = is_string($page) ? str_replace(['.php', '-'], [' ', ' '], $page) : 'GCM Netting Solutions';
    $title = ucwords($title) . ' - GCM Netting Solutions';
    
    return "\n<meta property=\"og:title\" content=\"" . htmlspecialchars($title) . "\">\n" .
           "<meta property=\"og:type\" content=\"website\">\n" .
           "<meta property=\"og:url\" content=\"" . SITE_URL . "\">\n" .
           "<meta property=\"og:image\" content=\"" . SITE_URL . "/assets/img/logo.png\">\n" .
           "<meta property=\"og:description\" content=\"Professional safety nets installation in Chennai\">\n" .
           "<meta property=\"og:site_name\" content=\"GCM Netting Solutions\">\n";
}

/**
 * Optimize all images in content
 */
function optimizeAllImages($content) {
    $content = preg_replace_callback(
        '/<img([^>]+)>/i',
        function($matches) {
            $img_tag = $matches[0];
            
            // Add alt text if missing
            if (strpos($img_tag, 'alt=') === false) {
                $img_tag = str_replace('<img', '<img alt="GCM Netting Solutions - Professional Installation"', $img_tag);
            }
            
            // Add loading="lazy"
            if (strpos($img_tag, 'loading=') === false) {
                $img_tag = str_replace('<img', '<img loading="lazy"', $img_tag);
            }
            
            return $img_tag;
        },
        $content
    );
    
    return $content;
}

/**
 * Optimize meta tags
 */
function optimizeMetaTags($content, $title) {
    // Optimize title
    if (preg_match('/<title>(.*?)<\/title>/i', $content)) {
        $content = preg_replace(
            '/<title>.*?<\/title>/i',
            '<title>' . htmlspecialchars($title) . '</title>',
            $content
        );
    }
    
    return $content;
}

/**
 * Add internal links to content
 */
function addInternalLinksToContent($content) {
    // Add links to important pages
    $links = [
        'pigeon nets' => '/pigeon-nets-in-chennai.php',
        'safety nets' => '/safety-nets-in-chennai.php',
        'bird nets' => '/bird-nets-in-chennai.php',
        'invisible grills' => '/invisible-grills-in-chennai.php'
    ];
    
    foreach ($links as $keyword => $url) {
        // Add link only if keyword exists and not already linked
        if (stripos($content, $keyword) !== false && stripos($content, 'href="' . $url) === false) {
            $content = preg_replace(
                '/\b(' . preg_quote($keyword, '/') . ')\b/i',
                '<a href="' . $url . '">$1</a>',
                $content,
                1 // Only replace first occurrence
            );
        }
    }
    
    return $content;
}

/**
 * Optimize blog content
 */
function optimizeBlogContent($blog) {
    $content = $blog['content'];
    
    // Add internal links
    $content = addInternalLinksToContent($content);
    
    // Optimize images
    $content = optimizeAllImages($content);
    
    return $content;
}

/**
 * Log optimization
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
 * Get optimization status
 */
function getOptimizationStatus() {
    try {
        $conn = getDBConnection();
        
        $stats = [
            'homepage' => $conn->query("SELECT COUNT(*) as count FROM seo_optimization_log WHERE page_path = 'index.php'")->fetch_assoc()['count'] > 0,
            'static_pages' => $conn->query("SELECT COUNT(*) as count FROM seo_optimization_log WHERE page_path LIKE '%.php' AND page_path NOT LIKE '%generated-pages%'")->fetch_assoc()['count'],
            'service_pages' => $conn->query("SELECT COUNT(DISTINCT page_path) as count FROM seo_optimization_log WHERE page_path LIKE '%generated-pages%'")->fetch_assoc()['count'],
            'total_optimized' => $conn->query("SELECT COUNT(DISTINCT page_path) as count FROM seo_optimization_log")->fetch_assoc()['count']
        ];
        
        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
